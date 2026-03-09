<?php

namespace Flute\Modules\Search\DTO;

final class SearchQuery
{
    public function __construct(
        public readonly string $raw,
        public readonly string $value,
        public readonly string $valueLower,
        public readonly int $limit,
        public readonly ?array $onlyProviders = null,
    ) {
    }
}
