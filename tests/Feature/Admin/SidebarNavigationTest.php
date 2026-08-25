<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * The sidebar, which folds its sections away.
 *
 * What is covered here is the part a stylesheet cannot be trusted with: that
 * each section is a real button announcing whether it is open, that the two
 * renderings of the bar do not collide, and that the section holding the page
 * being read is open when it arrives.
 */
final class SidebarNavigationTest extends TestCase
{
    use RefreshDatabase;

    private function shell(string $route = 'admin.dashboard'): string
    {
        return $this->actingAs(Admin::factory()->create(), 'admin')
            ->get(route($route))
            ->assertOk()
            ->getContent();
    }

    public function test_each_section_says_whether_it_is_open(): void
    {
        $shell = $this->shell();

        foreach (['Agenda', 'Réglages', 'Audience', 'Marketing'] as $section) {
            $this->assertStringContainsString($section, $shell);
        }

        // Repliee, la barre ouvre un volet plutot que l'accordeon : le chevron
        // annonce celui des deux qui s'applique.
        $this->assertStringContainsString(':aria-expanded="rail() ? volet : ouvert"', $shell);
    }

    // ── L'en-tete mene, le chevron replie ─────────────────────────────────

    /** Le motif d'un en-tete de section rendu comme un lien vers `$url`. */
    private function headerLinkedTo(string $url): string
    {
        return '/<a href="'.preg_quote($url, '/').'"[^>]*x-on:click="mener\(\$event\)"/';
    }

    /**
     * Le motif d'un en-tete de section rendu comme une bascule.
     *
     * Les deux attributs, et dans cet ordre : le chevron porte aussi
     * `basculer()` mais pas cette classe, et le menu utilisateur porte cette
     * classe mais pas `basculer()`.
     */
    private const HEADER_THAT_ONLY_FOLDS = '/<button[^>]*x-on:click="basculer\(\)"[^>]*class="fb-sidebar-gap/';

    /**
     * Ouvrir une section ou l'on n'est pas ne menait nulle part : il fallait
     * ensuite viser un second lien. Son libelle mene desormais a sa premiere
     * page, deux fois, la barre etant rendue une fois par point de rupture.
     */
    public function test_a_section_one_is_not_in_leads_to_its_first_page(): void
    {
        $shell = $this->shell();
        $booking = config('booking.admin.route_name');

        $this->assertSame(2, preg_match_all(
            $this->headerLinkedTo(route($booking.'agenda')),
            $shell,
        ));

        // Aucune section n'est active sur le tableau de bord : les quatre menent.
        $this->assertSame(0, preg_match_all(self::HEADER_THAT_ONLY_FOLDS, $shell));
    }

    /** Une fois dedans, l'en-tete n'a plus rien a mener : il ne fait que replier. */
    public function test_the_section_one_is_in_only_folds(): void
    {
        $booking = config('booking.admin.route_name');
        $shell = $this->shell($booking.'agenda');

        $this->assertSame(0, preg_match_all(
            $this->headerLinkedTo(route($booking.'agenda')),
            $shell,
        ));

        // Agenda seule est active, et elle est rendue une fois par point de
        // rupture.
        $this->assertSame(2, preg_match_all(self::HEADER_THAT_ONLY_FOLDS, $shell));

        // Reglages, qui est dedans, mene toujours. Quatre fois : Agenda rend
        // ses enfants une fois dans son accordeon et une fois dans son volet.
        $this->assertSame(4, preg_match_all(
            $this->headerLinkedTo(route($booking.'settings')),
            $shell,
        ));
    }

    /**
     * Le chevron est une commande a part, et il ne mene jamais nulle part.
     *
     * C'est aussi lui qui annonce l'etat : deux commandes cote a cote, une qui
     * mene et une qui replie, et une seule des deux gouverne le panneau.
     */
    public function test_the_chevron_is_a_control_of_its_own(): void
    {
        preg_match_all(
            '/<button[^>]*aria-label="Sous-menu [^"]*"[^>]*>/',
            $this->shell(),
            $chevrons,
        );

        $this->assertCount(10, $chevrons[0], 'Cinq sections, deux rendus de la barre.');

        foreach ($chevrons[0] as $chevron) {
            $this->assertStringNotContainsString('href', $chevron);
            $this->assertStringContainsString(':aria-expanded=', $chevron);
        }
    }

    /**
     * Le tiroir du telephone n'est jamais un rail.
     *
     * L'attribut est pose sur `<html>` des que la fenetre fait moins de 1500 px,
     * donc toujours sur telephone, et il hérite jusque dans le tiroir. Toucher
     * une section y ouvrait un volet flottant au lieu de derouler l'accordeon.
     *
     * Asserte sur la feuille, qui est ce qui decide : Alpine ne fait que lire
     * `--fb-rail`.
     */
    public function test_the_phone_drawer_is_never_a_rail(): void
    {
        $this->assertMatchesRegularExpression(
            '/#mobile-sidebar\s*\{[^}]*--fb-rail:\s*0/',
            (string) file_get_contents(resource_path('css/ui-kit.css')),
        );
    }

    /**
     * Le rail ne se deploie plus au survol, donc chaque section doit pouvoir
     * montrer ses liens autrement : un volet flottant, ancre a son icone.
     *
     * Cinq sections par rendu, Reglages etant imbriquee dans Agenda, et deux
     * rendus de la barre : dix volets. Teleportes, la barre etant en
     * `overflow-clip`.
     */
    public function test_every_section_carries_a_flyout_for_the_rail(): void
    {
        $shell = $this->shell();

        $this->assertSame(10, substr_count($shell, 'fb-sidebar-panel'));
        $this->assertSame(11, substr_count($shell, 'x-teleport="body"'), 'Dix volets, plus l’infobulle de la barre.');
    }

    /**
     * Une seule infobulle pour toute la barre, et chaque lien porte son titre.
     *
     * Posee sur chaque lien, elle etait rendue quatre-vingt-quatorze fois : la
     * barre est rendue deux fois, et chaque section rend ses liens une fois
     * dans son accordeon et une fois dans son volet.
     */
    public function test_the_bar_carries_one_tooltip_for_all_its_links(): void
    {
        $shell = $this->shell();

        // Une seule : le tiroir du telephone montre ses libelles, il n'a rien a
        // faire dire par une infobulle.
        $this->assertSame(1, substr_count($shell, 'barreInfobulle()'));
        $this->assertStringContainsString('data-fb-title="Planning"', $shell);
    }

    /**
     * The bar renders twice, once per breakpoint, and the slot is compiled once
     * then echoed into both. Four sections, Reglages nested inside Agenda,
     * therefore have to appear eight times: anything else means one of the two
     * renderings has been lost.
     */
    public function test_the_bar_renders_its_sections_once_per_breakpoint(): void
    {
        $this->assertSame(10, substr_count($this->shell(), 'x-collapse'));
    }

    /**
     * A panel whose aria-controls names nothing is worse than no attribute at
     * all, and that is exactly what $id() produces without an x-id root.
     */
    public function test_every_section_controls_a_panel_that_exists(): void
    {
        $shell = $this->shell();

        $this->assertSame(10, substr_count($shell, "x-id=\"['ui-sidebar-sous-menu']\""));
        $this->assertSame(10, substr_count($shell, ':aria-controls="$id(\'ui-sidebar-sous-menu\')"'));
        $this->assertSame(10, substr_count($shell, ':id="$id(\'ui-sidebar-sous-menu\')"'));
    }

    public function test_the_section_holding_the_current_page_opens_by_itself(): void
    {
        $shell = $this->shell(config('booking.admin.route_name').'agenda');

        // Quotes come out escaped, the expression being echoed rather than
        // written in the template. The HTML parser decodes them before Alpine
        // ever reads the attribute, so this is what actually ships.
        //
        // Le second argument dit si la section porte la page courante. Agenda
        // est rendue une fois par rendu de la barre, donc deux fois.
        $this->assertSame(2, substr_count(
            $shell,
            'barreSection($persist(false).as(&#039;ui-sidebar-agenda&#039;), true)',
        ));

        $this->assertSame(8, substr_count($shell, ', false)'), 'Les quatre autres sections, deux fois chacune.');
    }

    /**
     * The order on screen is the order in the file, and a reordering that
     * silently reverts would otherwise go unnoticed. Horaires is no longer in
     * this list: it sits inside Reglages now.
     */
    public function test_the_agenda_section_lists_its_screens_in_order(): void
    {
        $shell = $this->shell();
        $booking = config('booking.admin.route_name');

        $rangs = [];

        foreach (['agenda', 'catalogue', 'categories', 'journal', 'settings'] as $ecran) {
            $rangs[$ecran] = strpos($shell, route($booking.$ecran));
        }

        $this->assertSame($rangs, array_filter($rangs, is_int(...)));

        $tries = $rangs;
        asort($tries);

        $this->assertSame(array_keys($rangs), array_keys($tries));
    }

    public function test_the_current_page_is_named_to_more_than_the_eye(): void
    {
        $this->assertSame(2, substr_count($this->shell(), 'aria-current="page"'));
    }

    // ── Le kit, dont les autres projets dependent ──────────────────────────

    /**
     * A section is a new component, never a mode of the old one: a project that
     * has not migrated keeps a plain labelled group.
     */
    public function test_a_labelled_group_still_renders_a_plain_section(): void
    {
        $rendu = Blade::render('<x-ui.sidebar.group label="Pilotage"><li>Rien</li></x-ui.sidebar.group>');

        $this->assertStringContainsString('Pilotage', $rendu);
        $this->assertStringNotContainsString('x-collapse', $rendu);
        $this->assertStringNotContainsString('aria-expanded', $rendu);
    }

    /**
     * The attribute used to fall into the bag of undeclared ones and never be
     * rendered, which is why this application carried a copy of the component.
     *
     * Asserted on the pill rather than on the icon name: blade-icons inlines
     * the svg and the name never reaches the markup.
     */
    public function test_a_brand_logo_replaces_the_icon_pill(): void
    {
        $avec = Blade::render('<x-ui.sidebar brand="Essai" brand-logo="/logo.svg" />');

        $this->assertSame(2, substr_count($avec, 'src="/logo.svg"'));
        $this->assertStringNotContainsString('bg-indigo-600', $avec);

        $sans = Blade::render('<x-ui.sidebar brand="Essai" />');

        $this->assertStringContainsString('bg-indigo-600', $sans);
        $this->assertStringNotContainsString('<img', $sans);
    }
}
