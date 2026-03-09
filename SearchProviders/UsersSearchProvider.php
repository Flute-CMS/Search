<?php

namespace Flute\Modules\Search\SearchProviders;

use Flute\Core\Database\Entities\User;
use Flute\Core\Database\Entities\UserSocialNetwork;
use Flute\Modules\Search\Contracts\SearchProviderInterface;
use Flute\Modules\Search\DTO\SearchQuery;
use Flute\Modules\Search\DTO\SearchResult;
use Throwable;

class UsersSearchProvider implements SearchProviderInterface
{
    private const STEAM_ID_PATTERNS = [
        '/^STEAM_[0-5]:[01]:\d+$/i',
        '/^\[U:1:\d+\]$/i',
        '/^7656119\d{10}$/',
        '/^\d{1,10}$/',
    ];

    public function getKey(): string
    {
        return 'users';
    }

    public function getTitle(): string
    {
        return __('search_module.providers.users');
    }

    public function getDescription(): string
    {
        return __('search_module.providers.users_desc');
    }

    public function getIcon(): string
    {
        return 'ph.regular.users';
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

        $results = [];
        $seenUserIds = [];

        $steamId64 = $this->tryConvertToSteam64($search);
        if ($steamId64 !== null) {
            $socialUsers = $this->findUsersBySteamId($steamId64, $query->limit);
            foreach ($socialUsers as $user) {
                if (isset($seenUserIds[$user->id])) {
                    continue;
                }
                $seenUserIds[$user->id] = true;
                $results[] = $this->createResult($user, $searchLower, 5);
            }
        }

        $like = '%' . $this->escapeLike($search) . '%';

        $users = User::query()
            ->where('hidden', false)
            ->where('deletedAt', null)
            ->where(static function ($q) use ($like) {
                $q->where('name', 'LIKE', $like)
                    ->orWhere('login', 'LIKE', $like)
                    ->orWhere('uri', 'LIKE', $like);
            })
            ->orderBy('name', 'ASC')
            ->limit(min(20, $query->limit))
            ->fetchAll();

        foreach ($users as $user) {
            if (isset($seenUserIds[$user->id])) {
                continue;
            }
            $seenUserIds[$user->id] = true;

            $titleLower = mb_strtolower($user->name ?? '', 'UTF-8');
            $loginLower = mb_strtolower($user->login ?? '', 'UTF-8');
            $uriLower = mb_strtolower($user->uri ?? '', 'UTF-8');

            $relevance = 1;
            if ($titleLower === $searchLower || $loginLower === $searchLower || $uriLower === $searchLower) {
                $relevance = 4;
            } elseif (str_starts_with($titleLower, $searchLower) || str_starts_with($loginLower, $searchLower) || str_starts_with($uriLower, $searchLower)) {
                $relevance = 3;
            }

            $results[] = $this->createResult($user, $searchLower, $relevance);
        }

        if ($steamId64 !== null && count($results) < $query->limit) {
            $socialResults = $this->searchBySocialNetworkValue($steamId64, $seenUserIds, $query->limit - count($results));
            foreach ($socialResults as $result) {
                $results[] = $result;
            }
        }

        return array_slice($results, 0, $query->limit);
    }

    private function tryConvertToSteam64(string $input): ?string
    {
        $input = trim($input);

        if ($input === '' || mb_strlen($input) > 64) {
            return null;
        }

        $isSteamLike = false;
        foreach (self::STEAM_ID_PATTERNS as $pattern) {
            if (preg_match($pattern, $input)) {
                $isSteamLike = true;

                break;
            }
        }

        if (!$isSteamLike) {
            return null;
        }

        try {
            if (!class_exists(\xPaw\SteamID\SteamID::class)) {
                return null;
            }

            $steamId = new \xPaw\SteamID\SteamID($input);
            $steam64 = $steamId->ConvertToUInt64();

            if ($steam64 && preg_match('/^7656119\d{10}$/', $steam64)) {
                return $steam64;
            }
        } catch (Throwable) {
        }

        return null;
    }

    private function findUsersBySteamId(string $steam64, int $limit): array
    {
        $socialLinks = UserSocialNetwork::query()
            ->load('user')
            ->load('socialNetwork')
            ->where('value', $steam64)
            ->limit($limit)
            ->fetchAll();

        $users = [];
        foreach ($socialLinks as $link) {
            if (isset($link->user) && !$link->user->hidden && $link->user->deletedAt === null) {
                $users[] = $link->user;
            }
        }

        return $users;
    }

    private function searchBySocialNetworkValue(string $value, array &$seenUserIds, int $limit): array
    {
        if ($limit <= 0) {
            return [];
        }

        $like = '%' . $this->escapeLike($value) . '%';

        $socialLinks = UserSocialNetwork::query()
            ->load('user')
            ->where('value', 'LIKE', $like)
            ->limit($limit * 2)
            ->fetchAll();

        $results = [];
        foreach ($socialLinks as $link) {
            if (!isset($link->user) || $link->user->hidden || $link->user->deletedAt !== null) {
                continue;
            }

            if (isset($seenUserIds[$link->user->id])) {
                continue;
            }

            $seenUserIds[$link->user->id] = true;
            $results[] = $this->createResult($link->user, mb_strtolower($value, 'UTF-8'), 2);

            if (count($results) >= $limit) {
                break;
            }
        }

        return $results;
    }

    private function createResult(User $user, string $searchLower, int $relevance): SearchResult
    {
        return new SearchResult(
            provider: $this->getKey(),
            providerTitle: $this->getTitle(),
            id: $user->id,
            title: $user->name,
            url: (string) url('profile/' . $user->id),
            subtitle: __('def.profile'),
            icon: 'ph.regular.user',
            image: $user->avatar ? (string) url($user->avatar) : null,
            relevance: $relevance,
            meta: [
                'login' => $user->login,
                'uri' => $user->uri,
            ]
        );
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $value);
    }
}
