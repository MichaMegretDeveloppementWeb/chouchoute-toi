<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

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
        $this->assertMatchesRegularExpression('/\.ui-sidebar-link \{[^}]*min-height: 48px;[^}]*border-radius: 0;[^}]*border-bottom: 1px solid var\(--ui-border-subtle\);/s', $sheet);
        $this->assertMatchesRegularExpression('/\.ui-sidebar-submenu \.ui-sidebar-link \{[^}]*min-height: 40px;[^}]*padding: 10px 22px 10px 52px;/s', $sheet, 'Les pages d’une section ne sont plus alignées sur son libellé.');
    }
}
