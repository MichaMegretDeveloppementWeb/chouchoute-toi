<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Admin;
use Dom\HTMLDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\Support\ReadsTheSheet;
use Tests\TestCase;

/**
 * La barre latérale du back-office est dessinée par ses lignes · sur toute la
 * largeur de la barre, et la page courante pleine de la couleur d'action.
 *
 * Le dessin vit dans `resources/css/admin/sidebar.css`, qui vise les classes
 * crochets du kit · une feuille oubliée à l'import laisse la barre du kit telle
 * qu'il la livre, sans rien qui le dise.
 */
final class TheSidebarIsDrawnByItsRowsTest extends TestCase
{
    use ReadsTheSheet;
    use RefreshDatabase;

    private function sheet(string $path): string
    {
        return (string) file_get_contents(resource_path('css/'.$path));
    }

    public function test_the_back_office_sheet_imports_it(): void
    {
        $this->assertStringContainsString("@import './admin/sidebar.css';", $this->sheet('admin.css'));
    }

    public function test_the_current_page_is_filled_with_the_action_colour(): void
    {
        $sheet = $this->sheet('admin/sidebar.css');

        $this->assertMatchesRegularExpression("/\\.ui-sidebar-link\\[aria-current='page'\\] \\{\\s*background: var\\(--admin-sidebar-action\\);\\s*color: #fff;/", $sheet);
        $this->assertStringContainsString('--admin-sidebar-action: var(--fb-primary, #178079);', $sheet, 'La couleur d’action ne lit plus celle de l’établissement, ou n’a plus de valeur là où booking n’est pas chargé.');
    }

    public function test_a_row_runs_across_the_whole_bar(): void
    {
        $sheet = $this->sheet('admin/sidebar.css');

        $this->assertMatchesRegularExpression('/\.ui-sidebar-group \{\s*margin-inline: -20px;/', $sheet);
        $this->assertMatchesRegularExpression('/\.ui-sidebar-link \{[^}]*min-height: 48px;[^}]*border-radius: 0;[^}]*border-bottom: 1px solid var\(--ui-border\);/s', $sheet);
        $this->assertMatchesRegularExpression('/\.ui-sidebar-submenu \.ui-sidebar-link \{[^}]*min-height: 40px;[^}]*padding: 10px 22px 10px 52px;/s', $sheet, 'Les pages d’une section ne sont plus alignées sur son libellé.');
    }

    public function test_an_unrolled_section_inside_a_section_has_a_rule_under_its_name(): void
    {
        $sheet = $this->sheet('admin/sidebar.css');
        $rule = $this->blockOf($sheet, ".ui-sidebar-submenu .ui-sidebar-collapsible:has(.ui-sidebar-chevron[aria-expanded='true'])::after");

        foreach (["content: ''", 'position: absolute', 'bottom: 0', 'right: 0', 'left: 52px', 'height: 1px', 'background: var(--ui-border)'] as $declaration) {
            $this->assertStringContainsString($declaration.';', $rule, "Le trait sous une section dans une section a perdu « {$declaration} » · il part de la colonne de son libellé, du gris des traits.");
        }

        $this->assertStringContainsString('position: relative;', $this->blockOf($sheet, '.ui-sidebar-submenu .ui-sidebar-collapsible'), 'Le trait ne se pose plus sur la ligne de la section.');
    }

    /**
     * Chaque trait de la barre est du gris de son bord · ceux que la feuille pose,
     * et les trois que le kit dessine lui-même, sous la marque, au-dessus du compte
     * et au-dessus d'une section imbriquée dans le panneau du rail.
     */
    public function test_every_rule_of_the_bar_is_in_the_grey_of_its_edge(): void
    {
        $sheet = $this->sheet('admin/sidebar.css');

        $this->assertStringNotContainsString('--ui-border-subtle', $sheet, 'Un trait de la barre est resté au gris le plus pâle.');

        $this->assertMatchesRegularExpression('/\.ui-sidebar-canvas > \.ui-sidebar-gap,\s*\.ui-sidebar-drawer > div > div:first-child \{\s*border-bottom-color: var\(--ui-border\);\s*\}/', $sheet, 'Le trait sous la marque reste celui du kit, dans la barre ou dans le tiroir.');
        $this->assertMatchesRegularExpression('/\.ui-sidebar li:has\(\.admin-account\),\s*\.ui-sidebar-drawer li:has\(\.admin-account\) \{\s*border-top-color: var\(--ui-border\);\s*\}/', $sheet, 'Le trait au-dessus du compte reste celui du kit, dans la barre ou dans le tiroir.');
        $this->assertSame(['border-top-color' => 'var(--ui-border)'], $this->declarationsOf($sheet, '.ui-sidebar-panel .ui-sidebar-section'), 'Le trait au-dessus d’une section du panneau reste celui du kit.');
    }

    /**
     * Les trois traits du kit se reprennent par sa structure autant que par ses
     * classes · chaque sélecteur de la feuille atteint, sur la barre rendue,
     * l'élément même qui porte le trait du kit · une vue du kit qui change de
     * forme laisserait sinon un trait pâle, sans rien qui le dise.
     */
    public function test_each_rule_the_sheet_takes_over_reaches_the_kits_rule_on_the_bar(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin');

        $bar = HTMLDocument::createFromString('<!DOCTYPE html><html><body>'.Blade::render('<x-layout.admin></x-layout.admin>').'</body></html>', LIBXML_NOERROR);

        $rules = [
            '.ui-sidebar-canvas > .ui-sidebar-gap' => 'ui:border-b',
            '.ui-sidebar-drawer > div > div:first-child' => 'ui:border-b',
            '.ui-sidebar li:has(.admin-account)' => 'ui:border-t',
            '.ui-sidebar-drawer li:has(.admin-account)' => 'ui:border-t',
        ];

        foreach ($rules as $selector => $side) {
            $reached = $bar->querySelectorAll($selector);

            $this->assertSame(1, $reached->length, "La règle « {$selector} » n’atteint pas un seul élément de la barre.");

            $classes = explode(' ', (string) $reached->item(0)?->getAttribute('class'));

            $this->assertContains($side, $classes, "La règle « {$selector} » n’atteint plus l’élément qui porte le trait du kit.");
            $this->assertContains('ui:border-subtle', $classes, "La règle « {$selector} » n’atteint plus l’élément qui porte le trait du kit.");
        }

        // The rail's panel is teleported, so drawn inside a template, whose content the parser keeps apart.
        $nested = 0;

        foreach ($bar->querySelectorAll('template') as $template) {
            $panel = HTMLDocument::createFromString('<!DOCTYPE html><html><body>'.$template->innerHTML.'</body></html>', LIBXML_NOERROR);
            $nested += $panel->querySelectorAll('.ui-sidebar-panel .ui-sidebar-section')->length;
        }

        $this->assertGreaterThan(0, $nested, 'Aucune section n’est rangée dans le panneau d’une autre · la règle du panneau ne vise plus rien.');

        $kit = (string) file_get_contents(base_path('vendor/falcon/ui-kit/resources/css/components/sidebar.css'));

        $this->assertSame('1px solid var(--ui-border-subtle)', $this->declarationsOf($kit, '.ui-sidebar-panel .ui-sidebar-section')['border-top'] ?? null, 'Le kit ne trace plus le trait du panneau sur ce sélecteur · la feuille reprend un trait qui n’existe plus.');
    }
}
