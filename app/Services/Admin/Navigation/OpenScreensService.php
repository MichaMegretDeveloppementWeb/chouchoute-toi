<?php

declare(strict_types=1);

namespace App\Services\Admin\Navigation;

use App\Models\Admin;
use App\Providers\GateServiceProvider;
use Falcon\Analytics\Enums\Authorization\Ability as AnalyticsAbility;
use Falcon\Booking\Enums\Authorization\Ability as BookingAbility;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

/**
 * The screens of the back-office an account opens, section by section of its
 * bar · each one asked of the ability its route asks for, and drawn from this
 * one list.
 */
final readonly class OpenScreensService
{
    /**
     * The screens of each section, in the order the bar shows them · the ability
     * their route asks for, their title, and the route names that light their link.
     *
     * @var array<string, list<array{route: string, ability: string|UnitEnum, title: string, lights: list<string>}>>
     */
    private const array SECTIONS = [
        'dashboard' => [
            ['route' => 'admin.dashboard', 'ability' => GateServiceProvider::DASHBOARD, 'title' => 'Tableau de bord', 'lights' => ['admin.dashboard']],
        ],
        'agenda' => [
            ['route' => 'booking.admin.agenda', 'ability' => BookingAbility::Agenda, 'title' => 'Planning', 'lights' => ['booking.admin.agenda']],
            ['route' => 'booking.admin.clients', 'ability' => BookingAbility::Clients, 'title' => 'Clients', 'lights' => ['booking.admin.clients']],
            ['route' => 'booking.admin.catalogue', 'ability' => BookingAbility::Catalogue, 'title' => 'Prestations', 'lights' => ['booking.admin.catalogue']],
            ['route' => 'booking.admin.categories', 'ability' => BookingAbility::Catalogue, 'title' => 'Catégories', 'lights' => ['booking.admin.categories']],
            ['route' => 'booking.admin.settings.practitioners', 'ability' => BookingAbility::Practitioners, 'title' => 'Praticiens', 'lights' => ['booking.admin.settings.practitioners']],
            ['route' => 'booking.admin.journal', 'ability' => BookingAbility::Journal, 'title' => 'Journal', 'lights' => ['booking.admin.journal']],
            ['route' => 'booking.admin.messages', 'ability' => BookingAbility::Messages, 'title' => 'Envois', 'lights' => ['booking.admin.messages']],
        ],
        'settings' => [
            ['route' => 'booking.admin.settings', 'ability' => BookingAbility::Business, 'title' => 'Établissement', 'lights' => ['booking.admin.settings']],
            ['route' => 'booking.admin.schedule', 'ability' => BookingAbility::Schedule, 'title' => 'Horaires', 'lights' => ['booking.admin.schedule']],
            ['route' => 'booking.admin.settings.slots', 'ability' => BookingAbility::Slots, 'title' => 'Créneaux', 'lights' => ['booking.admin.settings.slots']],
            ['route' => 'booking.admin.settings.booking-window', 'ability' => BookingAbility::BookingWindow, 'title' => 'Réservation', 'lights' => ['booking.admin.settings.booking-window']],
            ['route' => 'booking.admin.settings.notifications', 'ability' => BookingAbility::Notifications, 'title' => 'Notifications', 'lights' => ['booking.admin.settings.notifications']],
        ],
        'audience' => [
            ['route' => 'analytics.admin.overview', 'ability' => AnalyticsAbility::Overview, 'title' => 'Vue d’ensemble', 'lights' => ['analytics.admin.overview']],
            ['route' => 'analytics.admin.realtime', 'ability' => AnalyticsAbility::Realtime, 'title' => 'Temps réel', 'lights' => ['analytics.admin.realtime']],
            ['route' => 'analytics.admin.visitors', 'ability' => AnalyticsAbility::Visitors, 'title' => 'Visiteurs', 'lights' => ['analytics.admin.visitors*']],
            ['route' => 'analytics.admin.sessions', 'ability' => AnalyticsAbility::Sessions, 'title' => 'Sessions', 'lights' => ['analytics.admin.sessions*']],
            ['route' => 'analytics.admin.events', 'ability' => AnalyticsAbility::Events, 'title' => 'Événements', 'lights' => ['analytics.admin.events']],
            ['route' => 'analytics.admin.funnels', 'ability' => AnalyticsAbility::Funnels, 'title' => 'Tunnels', 'lights' => ['analytics.admin.funnels']],
            ['route' => 'analytics.admin.integrations', 'ability' => AnalyticsAbility::Integrations, 'title' => 'Intégrations', 'lights' => ['analytics.admin.integrations*']],
        ],
        'marketing' => [
            ['route' => 'analytics.admin.marketing.dashboard', 'ability' => AnalyticsAbility::MarketingDashboard, 'title' => 'Vue d’ensemble', 'lights' => ['analytics.admin.marketing.dashboard']],
            ['route' => 'analytics.admin.marketing.campaigns', 'ability' => AnalyticsAbility::Campaigns, 'title' => 'Campagnes', 'lights' => ['analytics.admin.marketing.campaigns*']],
            ['route' => 'analytics.admin.marketing.ads', 'ability' => AnalyticsAbility::Ads, 'title' => 'Publicités', 'lights' => ['analytics.admin.marketing.ads*']],
        ],
    ];

    /**
     * The screens this account opens, section by section, in the order of the bar.
     *
     * @return array<string, list<array{route: string, title: string, lights: list<string>}>>
     */
    public function screensOpenTo(?Admin $account): array
    {
        $gate = $account === null ? null : Gate::forUser($account);
        $open = [];

        foreach (self::SECTIONS as $section => $screens) {
            $open[$section] = [];

            foreach ($screens as $screen) {
                if ($gate?->allows($screen['ability']) === true) {
                    $open[$section][] = ['route' => $screen['route'], 'title' => $screen['title'], 'lights' => $screen['lights']];
                }
            }
        }

        return $open;
    }

    /**
     * The route names that light a section, whichever of its screens the account opens.
     *
     * @return list<string>
     */
    public function lightsOf(string $section): array
    {
        return array_merge(...array_map(
            static fn (array $screen): array => $screen['lights'],
            self::SECTIONS[$section] ?? [],
        ));
    }
}
