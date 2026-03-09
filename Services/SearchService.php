<?php

namespace Flute\Modules\Search\Services;

use Flute\Core\Modules\Icons\Icon;
use Flute\Core\Modules\Icons\Services\IconFinder;
use Flute\Modules\Search\DTO\SearchQuery;
use Flute\Modules\Search\DTO\SearchResult;
use Throwable;

final class SearchService
{
    private const MAX_QUERY_LENGTH = 128;

    private const MAX_RESULTS = 100;

    private const DEFAULT_LIMIT = 20;

    public function __construct(
        private readonly SearchRegistry $registry
    ) {
    }

    /**
     * @param string[]|null $onlyProviders
     */
    public function search(string $query, ?array $onlyProviders = null, ?int $limit = null): array
    {
        if (!filter_var(config('search.enabled', true), FILTER_VALIDATE_BOOLEAN)) {
            return [];
        }

        $value = $this->sanitize($query);

        if ($value === '') {
            return [];
        }

        $minLength = (int) config('search.min_length', 2);
        if ($minLength > 0 && mb_strlen($value, 'UTF-8') < $minLength) {
            return [];
        }

        $cfgLimit = (int) config('search.limit', self::DEFAULT_LIMIT);
        $limit ??= $cfgLimit;
        $limit = max(1, min(self::MAX_RESULTS, (int) $limit));

        $dto = new SearchQuery(
            raw: $query,
            value: $value,
            valueLower: mb_strtolower($value, 'UTF-8'),
            limit: $limit,
            onlyProviders: $onlyProviders
        );

        $results = [];
        $seen = [];

        foreach ($this->registry->enabled($onlyProviders) as $provider) {
            try {
                $providerResults = $provider->search($dto);

                if (!is_array($providerResults) && !is_iterable($providerResults)) {
                    continue;
                }

                foreach ($providerResults as $result) {
                    if (!$result instanceof SearchResult) {
                        continue;
                    }

                    $key = $result->uniqueKey();
                    if (isset($seen[$key])) {
                        continue;
                    }

                    $seen[$key] = true;
                    $results[] = $result;

                    if (count($results) >= self::MAX_RESULTS * 2) {
                        break 2;
                    }
                }
            } catch (Throwable $e) {
                logs('modules')->warning("Search provider {$provider->getKey()} failed: {$e->getMessage()}");
            }
        }

        usort($results, static function (SearchResult $a, SearchResult $b) {
            if ($a->relevance !== $b->relevance) {
                return $b->relevance <=> $a->relevance;
            }

            return strcasecmp($a->title, $b->title);
        });

        $results = array_slice($results, 0, $limit);

        return array_map(function (SearchResult $r) {
            $arr = $r->toArray();
            $arr['iconHtml'] = $this->renderIconHtml($r->icon ?? 'ph.regular.magnifying-glass');

            return $arr;
        }, $results);
    }

    private function renderIconHtml(?string $path): ?string
    {
        $path = is_string($path) ? trim($path) : '';
        if ($path === '') {
            return null;
        }

        try {
            /** @var IconFinder $finder */
            $finder = app(IconFinder::class);
            $svg = $finder->loadFile($path);
            if (!$svg) {
                return null;
            }

            $icon = new Icon($svg);
            $icon->setAttributes([
                'width' => '1em',
                'height' => '1em',
                'fill' => 'currentColor',
                'role' => 'img',
            ]);

            return (string) $icon;
        } catch (Throwable $e) {
            return null;
        }
    }

    private function sanitize(string $input): string
    {
        if (mb_strlen($input, 'UTF-8') > self::MAX_QUERY_LENGTH * 2) {
            $input = mb_substr($input, 0, self::MAX_QUERY_LENGTH * 2, 'UTF-8');
        }

        $value = trim($input);

        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);

        $value = preg_replace('/\s+/', ' ', $value);

        $value = str_replace(["\0", "\x00"], '', $value);

        $value = preg_replace('/[^\p{L}\p{N}\p{P}\p{S}\p{Z}]/u', '', $value);

        if (mb_strlen($value, 'UTF-8') > self::MAX_QUERY_LENGTH) {
            $value = mb_substr($value, 0, self::MAX_QUERY_LENGTH, 'UTF-8');
        }

        return $value;
    }
}
