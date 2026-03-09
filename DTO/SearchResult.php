<?php

namespace Flute\Modules\Search\DTO;

final class SearchResult
{
    public function __construct(
        public readonly string $provider,
        public readonly string $providerTitle,
        public readonly string|int $id,
        public readonly string $title,
        public readonly string $url,
        public readonly ?string $subtitle = null,
        public readonly ?string $icon = null,
        public readonly ?string $image = null,
        public readonly int $relevance = 0,
        public readonly array $meta = [],
    ) {
    }

    public function uniqueKey(): string
    {
        return $this->provider . ':' . (string) $this->id . ':' . $this->url;
    }

    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'providerTitle' => $this->providerTitle,
            'id' => $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'url' => $this->url,
            'icon' => $this->icon,
            'image' => $this->image,
            'relevance' => $this->relevance,
            'meta' => $this->meta,
        ];
    }
}
