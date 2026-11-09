<?php

declare(strict_types=1);

namespace App\Services\Shared\Cache;

use Illuminate\Support\Facades\DB;

/**
 * The expired rows of the cache table · the framework forgets a row only when
 * its own key is read again, so a key asked once would stay for good.
 */
final readonly class ExpiredCacheService
{
    /**
     * Forgets every expired row of the default store when it lives in the
     * database, and says how many went · a row kept forever never expires.
     */
    public function forgetExpired(): int
    {
        $store = config()->string('cache.default');

        if (config("cache.stores.{$store}.driver") !== 'database') {
            return 0;
        }

        $connection = config("cache.stores.{$store}.connection");

        return DB::connection(is_string($connection) ? $connection : null)
            ->table(config()->string("cache.stores.{$store}.table", 'cache'))
            ->where('expiration', '<=', now()->getTimestamp())
            ->delete();
    }
}
