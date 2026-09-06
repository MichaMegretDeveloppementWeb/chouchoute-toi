<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use Tests\TestCase;

/**
 * Ce que cette application compile, et ce qu'elle se contente d'afficher.
 *
 * **Celui qui écrit du HTML fabrique le CSS de ce HTML.** falcon/ui-kit écrit
 * ses composants et livre leur feuille ; falcon/booking écrit ses écrans et
 * livre la sienne ; nous ne fabriquons que les classes de nos propres vues. Nos
 * gabarits posent leurs directives — `@bookingStyles` entraîne celle du kit —
 * et rien de plus.
 *
 * Le retour en arrière est facile et silencieux · on rajoute une vue de paquet
 * dans un `@source` « pour que ça marche », et l'application se remet à porter
 * quatre-vingt-dix kilo-octets qui ne lui appartiennent pas, périmés dès la
 * prochaine version du paquet. Ces essais tiennent la frontière.
 *
 * Le poids des règles, qui est l'autre moitié de la cohabitation, est tenu à
 * part par {@see VariantsOutrankPlainRulesTest}.
 */
final class PackagesShipTheirOwnAssetsTest extends TestCase
{
    public function test_the_layout_posts_the_package_directives(): void
    {
        $layout = $this->contentsOf('resources/views/components/layout/admin.blade.php');

        $this->assertStringContainsString('@bookingStyles', $layout);
        $this->assertStringContainsString('@bookingScripts', $layout);

        $guest = $this->contentsOf('resources/views/components/layout/admin-guest.blade.php');

        $this->assertStringContainsString('@uiKitStyles', $guest);
        $this->assertStringContainsString('@uiKitScripts', $guest);
    }

    /**
     * Aucun gabarit ne compile un fichier de paquet.
     *
     * Un `@vite` qui nomme un chemin de `packages/` ou de `vendor/falcon/` veut
     * dire qu'on refabrique ce que le paquet livre déjà · deux exemplaires du
     * même CSS, dont un seul suit les mises à jour.
     */
    public function test_no_layout_compiles_a_package_file(): void
    {
        foreach ($this->layouts() as $path => $contents) {
            foreach (['packages/falcon-', 'vendor/falcon/'] as $foreign) {
                $this->assertStringNotContainsString(
                    $foreign,
                    $contents,
                    $path.' compile un fichier de paquet. Les paquets livrent leurs assets ; '
                    .'posez leur directive au lieu de les nommer.',
                );
            }
        }
    }

    /**
     * Notre feuille ne scanne que nos vues.
     *
     * L'exception est falcon/analytics, qui n'a pas encore migré · la ligne est
     * nommée ici pour qu'elle se remarque, et pour qu'on la retire le jour où il
     * livrera la sienne.
     */
    public function test_our_stylesheet_scans_our_views_alone(): void
    {
        $entry = $this->contentsOf('resources/css/ui-kit.css');

        preg_match_all('/@source\s+\'([^\']+)\'/', $entry, $matches);

        $this->assertNotSame([], $matches[1], 'La feuille ne scanne plus rien.');

        foreach ($matches[1] as $source) {
            if ($source === '../../vendor/falcon/analytics/resources/views/**/*.blade.php') {
                continue;
            }

            $this->assertStringStartsWith(
                '../views/',
                $source,
                "« {$source} » sort de nos vues. Un paquet fabrique le CSS de ses propres écrans.",
            );
        }
    }

    /**
     * Nos surcharges du kit sont scannées.
     *
     * Un composant qu'on réécrit devient notre fichier · ses classes n'existent
     * dans aucune feuille livrée, et personne d'autre ne les fabriquera.
     */
    public function test_the_components_we_override_are_scanned(): void
    {
        $this->assertStringContainsString(
            "@source '../views/components/ui/**/*.blade.php';",
            $this->contentsOf('resources/css/ui-kit.css'),
        );
    }

    /**
     * @return array<string, string>
     */
    private function layouts(): array
    {
        $found = [];

        foreach ((array) glob(base_path('resources/views/{layouts,components/layout}/*.blade.php'), GLOB_BRACE) as $path) {
            $found[str_replace(base_path().DIRECTORY_SEPARATOR, '', (string) $path)] = (string) file_get_contents((string) $path);
        }

        $this->assertNotSame([], $found, 'Aucun gabarit trouvé.');

        return $found;
    }

    private function contentsOf(string $path): string
    {
        $full = base_path($path);

        $this->assertFileExists($full);

        return (string) file_get_contents($full);
    }
}
