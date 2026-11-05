<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Admin;
use Falcon\Analytics\Enums\Authorization\Ability as AnalyticsAbility;
use Falcon\Booking\Enums\Authorization\Ability as BookingAbility;
use Falcon\Booking\Models\Appointment;
use Falcon\Booking\Models\Location;
use Falcon\Booking\Models\Practitioner;
use Falcon\Booking\Models\Unavailability;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * What each role opens. The packages name their abilities and leave every one
 * open to a signed-in account · an owner has them all, and what follows is what
 * a practitioner does not.
 */
final class GateServiceProvider extends ServiceProvider
{
    /** The ability of the site's own dashboard, whose cards lead only to what an owner opens. */
    public const string DASHBOARD = 'admin.dashboard';

    public function boot(): void
    {
        $this->closeToPractitioners();
        $this->leaveEachOneTheirOwnAgenda();
    }

    private function closeToPractitioners(): void
    {
        $ownerOnly = static fn (Admin $admin): bool => $admin->isOwner();

        Gate::define(self::DASHBOARD, $ownerOnly);
        Gate::define(AnalyticsAbility::Analytics, $ownerOnly);
        Gate::define(BookingAbility::Journal, $ownerOnly);
        Gate::define(BookingAbility::Messages, $ownerOnly);
        Gate::define(BookingAbility::Catalogue, $ownerOnly);
        Gate::define(BookingAbility::Settings, $ownerOnly);
        Gate::define(BookingAbility::ClientsErase, $ownerOnly);
    }

    /** The whole planning shows, and a practitioner changes only what he performs. */
    private function leaveEachOneTheirOwnAgenda(): void
    {
        Gate::define(
            BookingAbility::AppointmentsEdit,
            static fn (Admin $admin, ?Appointment $visit = null): bool => $admin->isOwner()
                || $visit === null
                || ($admin->practitioner_id !== null && $visit->isPerformedBy($admin->practitioner_id)),
        );

        Gate::define(
            BookingAbility::AppointmentsAssign,
            static fn (Admin $admin, ?Practitioner $named = null): bool => $admin->isOwner()
                || $named === null
                || $named->id === $admin->practitioner_id,
        );

        Gate::define(
            BookingAbility::UnavailabilitiesEdit,
            static fn (Admin $admin, ?Unavailability $block = null): bool => $admin->isOwner()
                || $block === null
                || ($admin->practitioner_id !== null && $block->holdsOnly($admin->practitioner_id)),
        );

        Gate::define(
            BookingAbility::UnavailabilitiesAssign,
            static fn (Admin $admin, Practitioner|Location|null $named = null): bool => $admin->isOwner()
                || $named === null
                || ($named instanceof Practitioner && $named->id === $admin->practitioner_id),
        );
    }
}
