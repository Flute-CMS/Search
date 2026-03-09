<?php

namespace Flute\Modules\Search\Controllers;

use Flute\Core\Modules\Icons\Icon;
use Flute\Core\Modules\Icons\Services\IconFinder;
use Flute\Core\Router\Annotations\Middleware;
use Flute\Core\Router\Annotations\Route;
use Flute\Core\Support\BaseController;
use Flute\Core\Support\FluteRequest;
use Flute\Modules\Search\Services\SearchRegistry;
use Flute\Modules\Search\Services\SearchService;
use Throwable;

#[Middleware('throttle')]
class GlobalSearchController extends BaseController
{
    private const MAX_QUERY_LENGTH = 256;

    private const MAX_PROVIDERS = 20;

    private const MAX_LIMIT = 100;

    public function __construct(
        private readonly SearchService $searchService,
        private readonly SearchRegistry $searchRegistry
    ) {
    }

    #[Route('/api/search/providers', name: 'search.providers', methods: ['GET'])]
    public function providers()
    {
        $providers = [];

        foreach ($this->searchRegistry->enabled() as $provider) {
            $providers[] = [
                'key' => $provider->getKey(),
                'title' => $provider->getTitle(),
                'icon' => $provider->getIcon(),
                'iconHtml' => $this->renderIconHtml($provider->getIcon() ?? 'ph.regular.magnifying-glass'),
            ];
        }

        return response()->json([
            'providers' => $providers,
        ]);
    }

    #[Route('/api/search/global', name: 'search.global', methods: ['GET'])]
    public function search(FluteRequest $request)
    {
        $q = $this->extractQuery($request);

        if ($q === null) {
            return response()->json([
                'query' => '',
                'results' => [],
                'error' => 'Invalid query',
            ], 400);
        }

        $providers = $this->normalizeProviders($request->input('providers'));
        $limit = $this->normalizeLimit($request->input('limit'));

        try {
            $results = $this->searchService->search($q, $providers, $limit);

            return response()->json([
                'query' => mb_substr(trim($q), 0, 128, 'UTF-8'),
                'results' => $results,
            ]);
        } catch (Throwable $e) {
            logs('modules')->error("Search failed: {$e->getMessage()}");

            return response()->json([
                'query' => mb_substr(trim($q), 0, 128, 'UTF-8'),
                'results' => [],
                'error' => 'Search temporarily unavailable',
            ], 500);
        }
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

    private function extractQuery(FluteRequest $request): ?string
    {
        $q = $request->input('q') ?? $request->input('query');

        if ($q === null) {
            return '';
        }

        if (!is_string($q) && !is_numeric($q)) {
            return null;
        }

        $q = (string) $q;

        if (mb_strlen($q, 'UTF-8') > self::MAX_QUERY_LENGTH) {
            $q = mb_substr($q, 0, self::MAX_QUERY_LENGTH, 'UTF-8');
        }

        return $q;
    }

    private function normalizeProviders(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            if (mb_strlen($value, 'UTF-8') > 500) {
                return null;
            }
            $value = array_map('trim', explode(',', $value));
        }

        if (!is_array($value)) {
            return null;
        }

        $normalized = [];
        foreach ($value as $v) {
            if (!is_string($v)) {
                continue;
            }

            $v = trim($v);

            if ($v === '' || mb_strlen($v, 'UTF-8') > 64) {
                continue;
            }

            if (!preg_match('/^[a-zA-Z0-9_-]+$/', $v)) {
                continue;
            }

            $normalized[] = $v;

            if (count($normalized) >= self::MAX_PROVIDERS) {
                break;
            }
        }

        $normalized = array_values(array_unique($normalized));

        return $normalized === [] ? null : $normalized;
    }

    private function normalizeLimit(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        $limit = (int) $value;

        if ($limit < 1) {
            return 1;
        }

        if ($limit > self::MAX_LIMIT) {
            return self::MAX_LIMIT;
        }

        return $limit;
    }
}
