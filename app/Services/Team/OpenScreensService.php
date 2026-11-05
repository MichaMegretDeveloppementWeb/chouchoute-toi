<?php

declare(strict_types=1);

namespace App\Services\Team;

use App\Models\Admin;
use App\Providers\GateServiceProvider;
use Falcon\Analytics\Enums\Authorization\Ability as AnalyticsAbility;
use Falcon\Booking\Enums\Authorization\Ability as BookingAbility;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

/**
 * The screens of the back-office an account opens, section by section of its
 * bar · each one asked of the ability its route asks for.
 */
final readonly class OpenScreensService
{
    /** The screens of each section, in the order the bar shows them, with the ability their route asks for. */
    private const array SECTIONS = [
        'dashboard' => [
            'admin.dashboard' => GateServiceProvider::DASHBOARD,
        ],
        'agenda' => [
            'booking.admin.agenda' => BookingAbility::Agenda,
            'booking.admin.clients' => BookingAbility::Clients,
            'booking.admin.catalogue' => BookingAbility::Catalogue,
            'booking.admin.categories' => BookingAbility::Catalogue,
            'booking.admin.settings.practitioners' => BookingAbility::Practitioners,
            'booking.admin.journal' => BookingAbility::Journal,
            'booking.admin.messages' => BookingAbility::Messages,
        ],
        'settings' => [
            'booking.admin.settings' => BookingAbility::Business,
            'booking.admin.schedule' => BookingAbility::Schedule,
            'booking.admin.settings.slots' => BookingAbility::Slots,
            'booking.admin.settings.booking-window' => BookingAbility::BookingWindow,
            'booking.admin.settings.notifications' => BookingAbility::Notifications,
        ],
        'audience' => [
            'analytics.admin.overview' => AnalyticsAbility::Overview,
            'analytics.admin.realtime' => AnalyticsAbility::Realtime,
            'analytics.admin.visitors' => AnalyticsAbility::Visitors,
            'analytics.admin.sessions' => AnalyticsAbility::Sessions,
            'analytics.admin.events' => AnalyticsAbility::Events,
            'analytics.admin.funnels' => AnalyticsAbility::Funnels,
            'analytics.admin.integrations' => AnalyticsAbility::Integrations,
        ],
        'marketing' => [
            'analytics.admin.marketing.dashboard' => AnalyticsAbility::MarketingDashboard,
            'analytics.admin.marketing.campaigns' => AnalyticsAbility::Campaigns,
            'analytics.admin.marketing.ads' => AnalyticsAbility::Ads,
        ],
    ];

    /**
     * The route names this account opens, section by section, in the order of the bar.
     *
     * @return array<string, list<string>>
     */
    public function screensOpenTo(?Admin $account): array
    {
        $gate = $account === null ? null : Gate::forUser($account);
        $open = [];

        foreach (self::SECTIONS as $section => $screens) {
            $open[$section] = array_keys(array_filter(
                $screens,
                static fn (string|UnitEnum $ability): bool => $gate?->allows($ability) === true,
            ));
        }

        return $open;
    }
}
