<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Navigation;

use App\Analytics\Roles as AnalyticsRoles;
use App\Booking\Roles as BookingRoles;
use App\Enums\Account\Role;
use App\Models\Admin;
use Falcon\Analytics\DTOs\Authorization\RoleData as AnalyticsRoleData;
use Falcon\Booking\Data\Authorization\RoleData as BookingRoleData;
use Falcon\Booking\Enums\Authorization\Ability as BookingAbility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * The site declares its roles where each package expects them, the packages
 * read them there, and an account the site opens no screen to is told so.
 */
final class TheRolesAreDeclaredToThePackagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_is_offered_every_role_and_names_them_as_the_site_does(): void
    {
        $this->assertSame(
            array_map(static fn (Role $role): array => [$role->value, $role->label()], [Role::Collaborator, Role::Owner]),
            array_map(static fn (BookingRoleData $role): array => [$role->value, $role->label], (new BookingRoles)->roles()),
        );
    }

    /** A collaborator is declared to analytics, and opens nothing of it · declared, the diagnostic says nothing of it. */
    public function test_analytics_is_declared_every_role_and_opens_to_the_owner_alone(): void
    {
        $this->assertSame(
            [[Role::Owner->value, ['analytics']], [Role::Collaborator->value, []]],
            array_map(static fn (AnalyticsRoleData $role): array => [$role->value, array_keys($role->abilities)], (new AnalyticsRoles)->roles()),
        );
    }

    public function test_both_diagnostics_read_the_roles_of_the_site(): void
    {
        Admin::factory()->create();
        Admin::factory()->collaborator()->create();

        Artisan::call('booking:check');
        $this->assertMatchesRegularExpression('/Rôles\s+\S*\s*OK\s+\S*\s*App\\\\Booking\\\\Roles déclare 2 rôles · Collaborateur, Propriétaire\./u', Artisan::output());

        Artisan::call('analytics:check');
        $this->assertMatchesRegularExpression('/\| Rôles\s+\| OK\s+\| App\\\\Analytics\\\\Roles déclare 2 rôles · owner, collaborator\./u', Artisan::output());
    }

    /** No screen to arrive on · the dashboard refuses, and the refusal offers no way back. */
    public function test_an_account_that_opens_no_screen_is_refused_without_a_way_back(): void
    {
        Gate::define(BookingAbility::Agenda->value, static fn (Admin $admin): bool => false);

        $this->actingAs(Admin::factory()->collaborator()->create(), 'admin')
            ->get(route('admin.dashboard'))
            ->assertForbidden()
            ->assertSee('Cet écran ne fait pas partie de votre accès')
            ->assertDontSee('Revenir à l’écran');
    }
}
