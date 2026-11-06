<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

/**
 * What the sign-in space adds to Laravel's · the accounts of the back-office,
 * less the suspended ones.
 */
final class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Laravel's own `eloquent` provider, a suspended account being nobody · signing
        // in, a session already open, a device kept signed in and a forgotten password
        // all ask it, so one suspension shuts them all at the next click.
        Auth::provider('active_admins', static fn (Application $app, array $config): EloquentUserProvider => (new EloquentUserProvider($app->make('hash'), $config['model']))
            ->withQuery(static function (Builder $query): void {
                $query->getQuery()->whereNull('suspended_at');
            }));
    }
}
