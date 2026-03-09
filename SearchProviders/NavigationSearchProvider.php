<?php

namespace Flute\Modules\Search\SearchProviders;

use Flute\Core\Services\NavbarService;
use Flute\Modules\Search\Contracts\SearchProviderInterface;
use Flute\Modules\Search\DTO\SearchQuery;
use Flute\Modules\Search\DTO\SearchResult;

class NavigationSearchProvider implements SearchProviderInterface
{
    public function getKey(): string
    {
        return 'navigation';
    }

    public function getTitle(): string
    {
        return __('search_module.providers.navigation');
    }

    public function getDescription(): string
    {
        return __('search_module.providers.navigation_desc');
    }

    public function getIcon(): string
    {
        return 'ph.regular.navigation-arrow';
    }

    public function isEnabledByDefault(): bool
    {
        return true;
    }

    public function search(SearchQuery $query): array
    {
        $search = $query->value;
        $searchLower = $query->valueLower;

        if ($search === '') {
            return [];
        }

        $navbar = app(NavbarService::class)->all();

        $items = [];
        $this->flatten($navbar, $items);

        $results = [];

        foreach ($items as $item) {
            $title = trim((string) ($item['title'] ?? ''));
            $desc = trim((string) ($item['description'] ?? ''));
            $url = isset($item['url']) ? (string) $item['url'] : '';

            if ($title === '' || $url === '') {
                continue;
            }

            $haystack = mb_strtolower($title . ' ' . $desc . ' ' . $url, 'UTF-8');
            if (!str_contains($haystack, $searchLower)) {
                continue;
            }

            $titleLower = mb_strtolower($title, 'UTF-8');
            $urlLower = mb_strtolower($url, 'UTF-8');

            $relevance = 1;
            if ($titleLower === $searchLower || $urlLower === $searchLower) {
                $relevance = 4;
            } elseif (str_starts_with($titleLower, $searchLower) || str_starts_with($urlLower, $searchLower)) {
                $relevance = 3;
            } elseif (mb_strpos($titleLower, $searchLower) !== false) {
                $relevance = 2;
            }

            $results[] = new SearchResult(
                provider: $this->getKey(),
                providerTitle: $this->getTitle(),
                id: $item['id'] ?? $url,
                title: $title,
                url: $url,
                subtitle: $desc !== '' ? $desc : $url,
                icon: $item['icon'] ?? 'ph.regular.navigation-arrow',
                image: null,
                relevance: $relevance,
                meta: [
                    'new_tab' => (bool) ($item['new_tab'] ?? false),
                ]
            );
        }

        return array_slice($results, 0, min(50, $query->limit));
    }

    private function flatten(array $items, array &$out): void
    {
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $out[] = $item;

            if (!empty($item['children']) && is_array($item['children'])) {
                $this->flatten($item['children'], $out);
            }
        }
    }
}
