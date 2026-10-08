<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use Illuminate\Routing\Route as RouteDefinition;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Notre barre latérale mène à chaque écran de booking.
 *
 * Elle recopie la navigation du paquet, puisque nos écrans et les siens vivent
 * dans la même coquille. **Un écran ajouté au paquet n'y paraît pas tout
 * seul** · il existe, son adresse répond, et personne ne le trouve.
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

    public function test_the_practitioners_follow_the_establishment(): void
    {
        $page = Blade::render('<x-layout.admin></x-layout.admin>');

        $establishment = strpos($page, 'href="'.route('booking.admin.settings').'"');
        $practitioners = strpos($page, 'href="'.route('booking.admin.settings.practitioners').'"');
        $hours = strpos($page, 'href="'.route('booking.admin.schedule').'"');

        $this->assertNotFalse($establishment);
        $this->assertNotFalse($practitioners);
        $this->assertNotFalse($hours);
        $this->assertTrue($establishment < $practitioners && $practitioners < $hours, 'Praticiens se range entre Établissement et Horaires, comme dans la barre du paquet.');
    }

    public function test_the_clients_follow_the_planning(): void
    {
        $page = Blade::render('<x-layout.admin></x-layout.admin>');

        $planning = strpos($page, 'href="'.route('booking.admin.agenda').'"');
        $clients = strpos($page, 'href="'.route('booking.admin.clients').'"');
        $journal = strpos($page, 'href="'.route('booking.admin.journal').'"');

        $this->assertNotFalse($planning);
        $this->assertNotFalse($clients);
        $this->assertNotFalse($journal);
        $this->assertTrue($planning < $clients && $clients < $journal, 'Clients se range juste après Planning, dans l’Agenda, comme dans la barre du paquet.');
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
