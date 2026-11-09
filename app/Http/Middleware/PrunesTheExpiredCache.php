<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Shared\Cache\ExpiredCacheService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Lottery;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/** Some requests, drawn by lot, forget the expired rows of the cache once their response has gone. */
final class PrunesTheExpiredCache
{
    /** The chances of a request to sweep, out of how many. */
    private const array ODDS = [2, 100];

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    /** After the response · a failed sweep is reported, never shown to a page already served. */
    public function terminate(Request $request, Response $response): void
    {
        Lottery::odds(...self::ODDS)
            ->winner(static function (): void {
                try {
                    app(ExpiredCacheService::class)->forgetExpired();
                } catch (Throwable $e) {
                    report($e);
                }
            })
            ->choose();
    }
}
