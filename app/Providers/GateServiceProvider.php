<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Admin;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * What the site's own screens ask · the packages read the roles the site
 * declares to them, in `app/Booking/Roles.php` and `app/Analytics/Roles.php`.
 */
final class GateServiceProvider extends ServiceProvider
{
    /** The ability of the site's own dashboard, whose cards lead only to what an owner opens. */
    public const string DASHBOARD = 'admin.dashboard';

    public function boot(): void
    {
        Gate::define(self::DASHBOARD, static fn (Admin $admin): bool => $admin->isOwner());
    }
}
