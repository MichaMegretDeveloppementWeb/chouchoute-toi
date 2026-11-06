<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Navigation;

use App\Models\Admin;
use App\Services\Admin\Navigation\OpenScreensService;
use BackedEnum;
use Dom\HTMLDocument;
use Falcon\Analytics\Enums\Authorization\Ability as AnalyticsAbility;
use Falcon\Booking\Enums\Authorization\Ability as BookingAbility;
use Falcon\Booking\Models\OutgoingMessage;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use ReflectionClassConstant;
use Tests\TestCase;
use UnitEnum;

/**
 * The bar shows only what the account opens, the dashboard sends an account it
 * does not open to the first screen of its bar, and a refused screen says so
 * inside the office.
 */
final class TheBarShowsWhatTheRoleOpensTest extends TestCase
{
    use RefreshDatabase;

    /** The screens of the bar, by route, with the title of their link. */
    private const array SCREENS = [
        'admin.dashboard' => 'Tableau de bord',
        'booking.admin.agenda' => 'Planning',
        'booking.admin.clients' => 'Clients',
        'booking.admin.catalogue' => 'Prestations',
        'booking.admin.categories' => 'Catégories',
        'booking.admin.settings.practitioners' => 'Praticiens',
        'booking.admin.journal' => 'Journal',
        'booking.admin.messages' => 'Envois',
        'booking.admin.settings' => 'Établissement',
        'booking.admin.schedule' => 'Horaires',
        'booking.admin.settings.slots' => 'Créneaux',
        'booking.admin.settings.booking-window' => 'Réservation',
        'booking.admin.settings.notifications' => 'Notifications',
        'analytics.admin.overview' => 'Vue d’ensemble',
        'analytics.admin.realtime' => 'Temps réel',
        'analytics.admin.visitors' => 'Visiteurs',
        'analytics.admin.sessions' => 'Sessions',
        'analytics.admin.events' => 'Événements',
        'analytics.admin.funnels' => 'Tunnels',
        'analytics.admin.marketing.dashboard' => 'Vue d’ensemble',
        'analytics.admin.marketing.campaigns' => 'Campagnes',
        'analytics.admin.marketing.ads' => 'Publicités',
    ];

    public function test_an_owner_sees_every_screen_under_its_title(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin');

        $this->assertSame($this->linksTo(array_keys(self::SCREENS)), $this->theLinksOfTheBar());
    }

    public function test_a_collaborator_sees_the_planning_and_the_clients_and_nothing_else(): void
    {
        $this->actingAs($this->aCollaborator(), 'admin');

        $this->assertSame($this->linksTo(['booking.admin.agenda', 'booking.admin.clients']), $this->theLinksOfTheBar());
        $this->assertSame([], array_intersect(['Réglages', 'Audience', 'Marketing'], array_keys($this->theSectionsOfTheBar())));
    }

    /**
     * The ability the bar asks of each screen is the one its route asks · the
     * dashboard is left out, its controller sending a closed account elsewhere.
     */
    public function test_each_screen_of_the_bar_asks_what_its_route_asks(): void
    {
        /** @var array<string, list<array{route: string, ability: string|UnitEnum, title: string, lights: list<string>}>> $sections */
        $sections = (new ReflectionClassConstant(OpenScreensService::class, 'SECTIONS'))->getValue();
        $asked = 0;

        foreach (array_merge(...array_values($sections)) as $screen) {
            if ($screen['route'] === 'admin.dashboard') {
                continue;
            }

            $route = Route::getRoutes()->getByName($screen['route']);
            $this->assertNotNull($route, "No route is named {$screen['route']}.");

            $ability = match (true) {
                is_string($screen['ability']) => $screen['ability'],
                $screen['ability'] instanceof BackedEnum => (string) $screen['ability']->value,
                default => $screen['ability']->name,
            };

            $this->assertContains(
                Authorize::class.':'.$ability,
                Route::gatherRouteMiddleware($route),
                "The bar asks {$ability} of {$screen['route']}, which its route does not ask.",
            );

            $asked++;
        }

        $this->assertSame(count(self::SCREENS), $asked, 'Every screen of the bar but the dashboard, with Integrations, which the bar hides without the Search Console.');
    }

    public function test_a_bar_drawn_for_nobody_leads_nowhere(): void
    {
        $this->assertSame([], $this->theLinksOfTheBar());
    }

    /** A screen closed inside an open section leaves the rest, and the section leads to its first open screen. */
    public function test_each_screen_is_asked_on_its_own(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin');

        foreach ([BookingAbility::Business, BookingAbility::Notifications, AnalyticsAbility::Overview, AnalyticsAbility::Funnels, AnalyticsAbility::Campaigns] as $closed) {
            Gate::define($closed->value, static fn (): bool => false);
        }

        $closed = ['booking.admin.settings', 'booking.admin.settings.notifications', 'analytics.admin.overview', 'analytics.admin.funnels', 'analytics.admin.marketing.campaigns'];
        $sections = $this->theSectionsOfTheBar();

        $this->assertSame($this->linksTo(array_values(array_diff(array_keys(self::SCREENS), $closed))), $this->theLinksOfTheBar());
        $this->assertSame(route('booking.admin.schedule'), $sections['Réglages'] ?? null);
        $this->assertSame(route('analytics.admin.realtime'), $sections['Audience'] ?? null);
    }

    /** What Envois counts is read once for whoever opens Envois, and never for anybody else. */
    public function test_the_count_of_messages_not_sent_is_read_only_for_who_opens_them(): void
    {
        $table = (new OutgoingMessage)->getTable();

        $this->assertSame(1, $this->readsOf($table, Admin::factory()->create()));
        $this->assertSame(0, $this->readsOf($table, $this->aCollaborator()));
    }

    public function test_the_dashboard_leads_a_collaborator_to_the_first_screen_of_the_bar(): void
    {
        $this->actingAs($this->aCollaborator(), 'admin')
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('booking.admin.agenda'));

        $this->actingAs(Admin::factory()->create(), 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_a_refused_screen_is_said_inside_the_office_with_the_way_back(): void
    {
        $member = $this->aCollaborator();

        foreach (['booking.admin.journal', 'analytics.admin.overview', 'analytics.admin.marketing.dashboard'] as $route) {
            $this->actingAs($member, 'admin')
                ->get(route($route))
                ->assertForbidden()
                ->assertSee('Cet écran ne fait pas partie de votre accès')
                ->assertSee('Revenir à l’écran Planning')
                ->assertSee('data-ui-title="Clients"', false);
        }
    }

    public function test_a_refusal_outside_the_office_or_asked_as_data_keeps_the_ordinary_answer(): void
    {
        Route::middleware('web')->get('/refused-for-the-test', static fn () => abort(403));
        $member = $this->aCollaborator();

        $this->actingAs($member, 'admin')->get('/refused-for-the-test')
            ->assertForbidden()
            ->assertDontSee('Cet écran ne fait pas partie de votre accès');

        $this->actingAs($member, 'admin')->getJson(route('booking.admin.journal'))
            ->assertForbidden()
            ->assertDontSee('Cet écran ne fait pas partie de votre accès');
    }

    private function aCollaborator(): Admin
    {
        return Admin::factory()->collaborator()->create();
    }

    /**
     * What each of these screens is called in the bar, by address.
     *
     * @param  list<string>  $routes
     * @return array<string, string>
     */
    private function linksTo(array $routes): array
    {
        $links = [];

        foreach ($routes as $route) {
            $links[route($route)] = self::SCREENS[$route];
        }

        return $links;
    }

    /**
     * The links of the bar, by address, with their title.
     *
     * @return array<string, string>
     */
    private function theLinksOfTheBar(): array
    {
        $links = [];

        foreach ($this->theBar()->querySelectorAll('a[data-ui-title]') as $anchor) {
            $links[(string) $anchor->getAttribute('href')] = (string) $anchor->getAttribute('data-ui-title');
        }

        return $links;
    }

    /**
     * The sections of the bar, by label, with where their own link leads.
     *
     * @return array<string, string>
     */
    private function theSectionsOfTheBar(): array
    {
        $sections = [];

        foreach ($this->theBar()->querySelectorAll('.ui-sidebar-collapsible > a') as $anchor) {
            $sections[trim((string) $anchor->querySelector('.ui-sidebar-label')?->textContent)] = (string) $anchor->getAttribute('href');
        }

        return $sections;
    }

    private function theBar(): HTMLDocument
    {
        return HTMLDocument::createFromString('<!DOCTYPE html><html><body>'.Blade::render('<x-layout.admin></x-layout.admin>').'</body></html>', LIBXML_NOERROR);
    }

    /** How many statements a page of the office sends to this table, for this account. */
    private function readsOf(string $table, Admin $account): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($account, 'admin')->get(route('admin.profile'))->assertOk();

        $reads = collect(DB::getQueryLog())->filter(static fn (array $query): bool => str_contains($query['query'], $table))->count();
        DB::disableQueryLog();

        return $reads;
    }
}
