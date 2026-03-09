<?php

namespace Flute\Modules\Search\Services;

use Flute\Modules\Search\Contracts\SearchProviderInterface;

final class SearchRegistry
{
    /**
     * @var array<string, SearchProviderInterface>
     */
    private array $providers = [];

    public function registerProvider(SearchProviderInterface $provider): void
    {
        $this->providers[$provider->getKey()] = $provider;
    }

    /**
     * @return SearchProviderInterface[]
     */
    public function all(): array
    {
        return array_values($this->providers);
    }

    public function get(string $key): ?SearchProviderInterface
    {
        return $this->providers[$key] ?? null;
    }

    public function isProviderEnabled(string $key): bool
    {
        $provider = $this->get($key);
        if (!$provider) {
            return false;
        }

        $map = config('search.providers', []);
        if (is_array($map) && array_key_exists($key, $map)) {
            return filter_var($map[$key], FILTER_VALIDATE_BOOLEAN);
        }

        return $provider->isEnabledByDefault();
    }

    /**
     * @param string[]|null $onlyProviders
     *
     * @return SearchProviderInterface[]
     */
    public function enabled(?array $onlyProviders = null): array
    {
        $providers = $this->all();

        if ($onlyProviders !== null) {
            $onlyProviders = array_values(array_unique(array_filter(array_map(static fn ($v) => is_string($v) ? trim($v) : '', $onlyProviders))));
            $providers = array_values(array_filter($providers, static fn (SearchProviderInterface $p) => in_array($p->getKey(), $onlyProviders, true)));
        }

        return array_values(array_filter($providers, fn (SearchProviderInterface $p) => $this->isProviderEnabled($p->getKey())));
    }
}
