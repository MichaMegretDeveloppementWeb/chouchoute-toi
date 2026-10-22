<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use Dom\HTMLDocument;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as RouteDefinition;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Notre barre latérale mène à chaque écran de booking.
 *
 * Elle mène aux écrans du paquet, rangés à notre façon, puisque nos écrans et
 * les siens vivent dans la même coquille. **Un écran ajouté au paquet n'y paraît
 * pas tout seul** · il existe, son adresse répond, et personne ne le trouve.
 *
 * Lu sur les routes nommées du paquet plutôt que sur une liste écrite ici ·
 * c'est ce qui attrapera le prochain écran.
 */
final class TheSidebarLeadsToEveryBookingScreenTest extends TestCase
{
    /** Les adresses du paquet qui ne sont pas des écrans de menu. */
    private const NOT_IN_THE_MENU = ['booking.admin.attachment', 'booking.admin.missing'];

    public function test_every_screen_of_booking_is_in_the_sidebar(): void
    {
        $page = Blade::render('<x-layout.admin></x-layout.admin>');
        $screens = $this->screensOfBooking();

        $this->assertContains('booking.admin.settings.practitioners', $screens, 'La lecture des routes ne trouve pas l’écran Praticiens · elle ne prouverait rien.');

        foreach ($screens as $name) {
            $this->assertStringContainsString('href="'.route($name).'"', $page, "La barre latérale ne mène pas à {$name}.");
        }
    }

    /**
     * L'Agenda · Planning, Clients, Prestations, Catégories, Praticiens, puis
     * Journal · et les Réglages à la fin, sans les Praticiens.
     */
    public function test_the_agenda_holds_the_practitioners_and_the_settings_follow_it(): void
    {
        $page = Blade::render('<x-layout.admin></x-layout.admin>');

        $order = array_map(
            fn (string $name): int|false => strpos($page, 'href="'.route('booking.admin.'.$name).'"'),
            ['agenda', 'clients', 'catalogue', 'categories', 'settings.practitioners', 'journal', 'settings', 'schedule', 'settings.slots', 'settings.booking-window', 'settings.notifications'],
        );

        $this->assertNotContains(false, $order, 'Un écran manque à la barre.');

        $sorted = $order;
        sort($sorted);

        $this->assertSame($sorted, $order, 'L’Agenda range Praticiens après Catégories et avant Journal, et les Réglages, d’Établissement à Notifications, viennent après lui.');
        $this->assertLessThan(strpos($page, '>Réglages<'), strpos($page, 'href="'.route('booking.admin.settings.practitioners').'"'), 'Praticiens est encore rangé dans les Réglages.');
    }

    /**
     * Les Réglages sont un sous-menu de l'Agenda · ils ne règlent que lui, et au
     * niveau d'Audience et de Marketing ils laissaient croire qu'ils les règlent
     * aussi.
     */
    public function test_the_settings_are_a_submenu_of_the_agenda(): void
    {
        $document = HTMLDocument::createFromString('<!DOCTYPE html><html><body>'.Blade::render('<x-layout.admin></x-layout.admin>').'</body></html>', LIBXML_NOERROR);
        $settings = null;

        foreach ($document->querySelectorAll('.ui-sidebar-section') as $section) {
            if (trim((string) $section->querySelector('.ui-sidebar-label')?->textContent) === 'Réglages') {
                $settings = $section;

                break;
            }
        }

        $this->assertNotNull($settings, 'La section Réglages est introuvable dans la barre.');

        $holder = $settings->parentElement?->closest('.ui-sidebar-section');

        $this->assertNotNull($holder, 'Les Réglages sont encore une section à part, au niveau de l’Agenda.');
        $this->assertSame('Agenda', trim((string) $holder->querySelector('.ui-sidebar-label')?->textContent));
    }

    /**
     * La page des Praticiens allume l'Agenda, bien que son adresse soit sous
     * `settings` · celle des Horaires allume l'Agenda et, dedans, les Réglages.
     */
    public function test_each_page_lights_its_own_section(): void
    {
        $practitioners = $this->pageAt('booking.admin.settings.practitioners');

        $this->assertTrue($this->lights($practitioners, 'Agenda'), 'Sur la page des Praticiens, la section Agenda n’est pas allumée.');
        $this->assertFalse($this->lights($practitioners, 'Réglages'), 'Sur la page des Praticiens, la section Réglages s’allume encore.');

        $hours = $this->pageAt('booking.admin.schedule');

        $this->assertTrue($this->lights($hours, 'Réglages'), 'Sur la page des Horaires, la section Réglages n’est pas allumée.');
        $this->assertTrue($this->lights($hours, 'Agenda'), 'Sur la page des Horaires, la section Agenda qui porte les Réglages n’est pas allumée.');
        $this->assertFalse($this->lights($this->pageAt('admin.dashboard'), 'Agenda'), 'Sur le tableau de bord, la section Agenda s’allume.');
    }

    /** La barre rendue comme sur la page que la route nomme. */
    private function pageAt(string $name): string
    {
        $request = Request::create(route($name));
        $request->setRouteResolver(fn (): RouteDefinition => Route::getRoutes()->match($request));
        $this->app->instance('request', $request);

        return Blade::render('<x-layout.admin></x-layout.admin>');
    }

    /** Si la section qui porte ce libellé dit qu'une de ses pages est ouverte. */
    private function lights(string $page, string $label): bool
    {
        $found = preg_match('/class="ui-sidebar-collapsible([^"]*)"(?:(?!ui-sidebar-collapsible).)*?<span class="ui-sidebar-label">'.preg_quote($label, '/').'<\/span>/s', $page, $section);

        $this->assertSame(1, $found, "La section {$label} est introuvable dans la barre.");

        return str_contains($section[1], 'ui-sidebar-badge');
    }

    /**
     * Les écrans de l'administration du paquet · ses routes nommées, sans
     * paramètre, et qui ne sont pas un repli.
     *
     * @return list<string>
     */
    private function screensOfBooking(): array
    {
        $screens = [];

        /** @var RouteDefinition $route */
        foreach (Route::getRoutes()->getRoutes() as $route) {
            $name = (string) $route->getName();

            if (str_starts_with($name, 'booking.admin.') && ! in_array($name, self::NOT_IN_THE_MENU, true) && $route->parameterNames() === []) {
                $screens[] = $name;
            }
        }

        return $screens;
    }
}
