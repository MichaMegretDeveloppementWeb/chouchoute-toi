<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

/**
 * What the sign-in space adds to Laravel's · the accounts of the back-office,
 * less the suspended ones, a session closed once its password changes, and
 * the limit on opening a password link.
 */
final class AuthServiceProvider extends ServiceProvider
{
    /** The visits of the password links a visitor makes in a minute. */
    public const int LINK_VISITS_PER_MINUTE = 30;

    public function boot(): void
    {
        // Laravel's own `eloquent` provider, a suspended account being nobody · signing
        // in, a session already open, a device kept signed in and a forgotten password
        // all ask it, so one suspension shuts them all at the next click.
        Auth::provider('active_admins', static fn (Application $app, array $config): EloquentUserProvider => (new EloquentUserProvider($app->make('hash'), $config['model']))
            ->withQuery(static function (Builder $query): void {
                $query->getQuery()->whereNull('suspended_at');
            }));

        // A click replays the check of the page · a password changed elsewhere closes the session there too.
        Livewire::addPersistentMiddleware([AuthenticateSession::class]);

        // Opening a link checks its secret, which costs a hash · a visitor opens a few, not hundreds.
        RateLimiter::for('password-links', static fn (Request $request): Limit => Limit::perMinute(self::LINK_VISITS_PER_MINUTE)->by((string) $request->ip()));
    }
}
