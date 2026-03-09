<?php

namespace Flute\Modules\Search\Contracts;

use Flute\Modules\Search\DTO\SearchQuery;
use Flute\Modules\Search\DTO\SearchResult;

interface SearchProviderInterface
{
    public function getKey(): string;

    public function getTitle(): string;

    public function getDescription(): string;

    public function getIcon(): string;

    public function isEnabledByDefault(): bool;

    /**
     * @return SearchResult[]
     */
    public function search(SearchQuery $query): array;
}
