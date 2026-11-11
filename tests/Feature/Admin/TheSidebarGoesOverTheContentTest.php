<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use Tests\TestCase;

/**
 * Entre 1 024 et 1 400 px, la barre latérale passe par-dessus le contenu · le
 * contenu garde la largeur de la colonne d'icônes, la barre est repliée tant que
 * le ☰ ne l'ouvre pas, et ouverte, un voile prend le clic à côté.
 *
 * La feuille vit dans `resources/css/admin/sidebar.css`, le geste dans
 * `resources/js/admin/sidebar-over.js` · le geste lui-même (ouvrir, les quatre
 * fermetures, rien de retenu) se vérifie au navigateur, le site n'ayant pas
 * d'outil d'essai de script.
 */
final class TheSidebarGoesOverTheContentTest extends TestCase
{
    private const string BAND = '@media (64rem <= width < 87.5rem)';

    private const string FOLDED_BY_US = ".ui-sidebar-frame[data-ui-sidebar='expanded']:not([data-admin-sidebar-over])";

    private function band(): string
    {
        $sheet = (string) file_get_contents(resource_path('css/admin/sidebar.css'));
        $start = strpos($sheet, self::BAND);

        $this->assertNotFalse($start, 'La feuille n’a plus de bande entre 1 024 et 1 400 px.');

        return substr($sheet, $start);
    }

    /**
     * The declarations of the first block a selector opens in a sheet.
     *
     * @return array<string, string>
     */
    private function declarationsOf(string $sheet, string $selector): array
    {
        $start = strpos($sheet, $selector.' {');

        $this->assertNotFalse($start, "Aucun bloc pour {$selector}.");

        $body = substr($sheet, $start + strlen($selector) + 2);
        $body = substr($body, 0, (int) strpos($body, '}'));
        $declarations = [];

        foreach (array_filter(array_map('trim', explode(';', $body)), static fn (string $line): bool => $line !== '') as $line) {
            [$property, $value] = array_map('trim', explode(':', $line, 2));
            $declarations[$property] = $value;
        }

        return $declarations;
    }

    public function test_the_content_keeps_the_rails_width_in_the_band(): void
    {
        $this->assertSame(
            ['padding-left' => 'var(--ui-sidebar-width-rail)'],
            $this->declarationsOf($this->band(), '.ui-sidebar-frame .ui-sidebar-inset'),
        );
    }

    /** The bar the kit would draw open is folded the way the kit folds it, word for word. */
    public function test_the_bar_is_folded_as_the_kit_folds_it(): void
    {
        $kit = (string) file_get_contents(base_path('vendor/falcon/ui-kit/resources/css/ui.css'));

        $this->assertSame(
            $this->declarationsOf($kit, ".ui-sidebar-frame[data-ui-sidebar='collapsed']"),
            $this->declarationsOf($this->band(), self::FOLDED_BY_US),
        );
    }

    /** The rows of the folded bar are drawn as the sheet draws them when the kit folds it. */
    public function test_its_rows_are_drawn_as_when_the_kit_folds_it(): void
    {
        $sheet = (string) file_get_contents(resource_path('css/admin/sidebar.css'));

        foreach ([' .ui-sidebar .ui-sidebar-chevron', ' .ui-sidebar .ui-sidebar-collapsible', ' .ui-sidebar .ui-sidebar-badge', ' .ui-sidebar .ui-sidebar-badge .ui-icon'] as $row) {
            $this->assertSame(
                $this->declarationsOf($sheet, ".ui-sidebar-frame[data-ui-sidebar='collapsed']".$row),
                $this->declarationsOf($this->band(), self::FOLDED_BY_US.$row),
                $row,
            );
        }
    }

    public function test_open_over_the_content_a_veil_takes_the_click_beside_it(): void
    {
        $veil = $this->declarationsOf($this->band(), '.ui-sidebar-frame[data-admin-sidebar-over] .ui-sidebar-inset::before');

        $this->assertSame('fixed', $veil['position']);
        $this->assertSame('0', $veil['inset']);
        $this->assertLessThan(30, (int) $veil['z-index'], 'Le voile passerait devant la barre, posée à 30 par le kit.');

        $trigger = $this->declarationsOf($this->band(), '.ui-sidebar-frame[data-admin-sidebar-over] .ui-sidebar-trigger');

        $this->assertGreaterThan(30, (int) $trigger['z-index'], 'Le ☰ resterait sous la barre ouverte, et ne la refermerait plus.');
        $this->assertSame('translateX(calc(var(--ui-sidebar-width-open) - var(--ui-sidebar-width-rail) - 4.375rem))', $trigger['transform'], 'Le ☰ ne vient plus au bout de la ligne du nom, dans le haut de la barre ouverte.');
    }

    public function test_the_script_holds_the_same_band_and_the_back_office_runs_it(): void
    {
        $script = (string) file_get_contents(resource_path('js/admin/sidebar-over.js'));
        $entry = (string) file_get_contents(resource_path('js/admin.js'));

        $this->assertStringContainsString("const BAND = '(64rem <= width < 87.5rem)';", $script);
        $this->assertStringContainsString("import { holdTheBarOverTheContent } from './admin/sidebar-over.js';", $entry);
        $this->assertMatchesRegularExpression('/^holdTheBarOverTheContent\(\);$/m', $entry);
    }
}
