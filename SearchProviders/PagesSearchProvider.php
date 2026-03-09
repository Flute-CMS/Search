<?php

namespace Flute\Modules\Search\SearchProviders;

use Flute\Core\Database\Entities\Page;
use Flute\Core\Database\Entities\Permission;
use Flute\Modules\Search\Contracts\SearchProviderInterface;
use Flute\Modules\Search\DTO\SearchQuery;
use Flute\Modules\Search\DTO\SearchResult;

class PagesSearchProvider implements SearchProviderInterface
{
    public function getKey(): string
    {
        return 'pages';
    }

    public function getTitle(): string
    {
        return __('search_module.providers.pages');
    }

    public function getDescription(): string
    {
        return __('search_module.providers.pages_desc');
    }

    public function getIcon(): string
    {
        return 'ph.regular.file-text';
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

        $like = '%' . $this->escapeLike($search) . '%';

        $pages = Page::query()
            ->load('permissions')
            ->where(static function ($q) use ($like) {
                $q->where('title', 'LIKE', $like)
                    ->orWhere('route', 'LIKE', $like)
                    ->orWhere('description', 'LIKE', $like);
            })
            ->orderBy('title', 'ASC')
            ->limit(min(20, $query->limit))
            ->fetchAll();

        $results = [];

        foreach ($pages as $page) {
            if (str_starts_with($page->route, '/admin')) {
                continue;
            }

            if (!$this->canAccessPage($page->permissions ?? [])) {
                continue;
            }

            $titleLower = mb_strtolower($page->title ?? '', 'UTF-8');
            $routeLower = mb_strtolower($page->route ?? '', 'UTF-8');

            $relevance = 1;
            if ($titleLower === $searchLower || $routeLower === $searchLower) {
                $relevance = 4;
            } elseif (str_starts_with($titleLower, $searchLower) || str_starts_with($routeLower, $searchLower)) {
                $relevance = 3;
            } elseif (mb_strpos($titleLower, $searchLower) !== false || mb_strpos($routeLower, $searchLower) !== false) {
                $relevance = 2;
            }

            $results[] = new SearchResult(
                provider: $this->getKey(),
                providerTitle: $this->getTitle(),
                id: $page->id,
                title: $page->title,
                url: (string) url($page->route),
                subtitle: $page->route,
                icon: 'ph.regular.file-text',
                image: $page->og_image ? (string) url($page->og_image) : null,
                relevance: $relevance,
                meta: [
                    'description' => $page->description,
                ]
            );
        }

        return $results;
    }

    /**
     * @param Permission[] $permissions
     */
    private function canAccessPage(array $permissions): bool
    {
        $permissions = array_filter($permissions);

        if ($permissions === []) {
            return true;
        }

        if (user()->can('admin.boss')) {
            return true;
        }

        foreach ($permissions as $permission) {
            if ($permission instanceof Permission && user()->can($permission->name)) {
                return true;
            }
        }

        return false;
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $value);
    }
}
