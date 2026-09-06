<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * Nos règles à variante pèsent-elles plus que nos règles nues ?
 *
 * Une page de back-office porte **trois feuilles** · celle de falcon/ui-kit,
 * celle de falcon/booking, et la nôtre. Chacune écrit les classes qu'elle a
 * vues, donc `.bg-white` et `.hidden` y figurent trois fois — à l'identique, ce
 * qui ne gêne personne.
 *
 * Ce qui gêne, c'est ce qui se glisse entre ces copies. Tailwind écrit la règle
 * nue d'abord et ses variantes ensuite : à l'intérieur d'une feuille, `sm:` et
 * `dark:` gagnent par l'ordre seul. **La nôtre est la dernière**, donc son
 * `.bg-white` tombe après le `dark:bg-gray-800` d'un paquet et son `.hidden`
 * après un `lg:block`. Même poids, position plus tardive, et l'écran d'un
 * paquet change de couleur ou de forme sans qu'aucune erreur ne le dise.
 *
 * Le kit répond par le poids, dans `variants.css`, que notre entrée lit par
 * `@reference`. Ces essais vérifient que la réponse arrive bien jusqu'au fichier
 * que Vite produit · c'est lui, et lui seul, que le navigateur reçoit, et notre
 * build ne se refait pas quand le kit change.
 *
 * Constaté le 2026-09-06 · le champ de recherche des prestations de
 * falcon/booking revenait blanc en mode sombre, à cause de notre `.bg-white`.
 */
final class VariantsOutrankPlainRulesTest extends TestCase
{
    /**
     * Les points de rupture que nos écrans emploient.
     *
     * Les cinq de Tailwind, plus `wide` que le kit ajoute pour le pli de la
     * barre latérale.
     *
     * @var list<string>
     */
    private const BREAKPOINTS = ['sm', 'md', 'lg', 'xl', '2xl', 'wide'];

    public function test_our_compiled_stylesheet_weights_its_breakpoint_rules(): void
    {
        $css = $this->compiledStylesheet();
        $found = 0;

        foreach (self::BREAKPOINTS as $breakpoint) {
            foreach ($this->selectorsFor($breakpoint, $css) as $offset => $selector) {
                // `space-*` et `divide-*` vivent dans un `:where()`, où aucun
                // poids ne compte. Les peser est impossible, et c'est l'essai
                // sur les vues, plus bas, qui interdit la seule configuration
                // où cela nuit.
                if (preg_match('/^\.[^\\\\]*\\\\:(space|divide)-/', $selector) === 1) {
                    continue;
                }

                // Seules les regles d'utilitaire nous interessent · une regle
                // ecrite a la main qui **vise** la classe, comme
                // `html[data-x] .lg\:pl-[62px]`, porte deja le poids de son
                // prefixe. Un selecteur d'utilitaire commence apres `{`, `}`
                // ou `,` ; partout ailleurs, la classe est un maillon d'un
                // selecteur plus long.
                if ($offset > 0 && ! in_array($css[$offset - 1], ['{', '}', ','], true)) {
                    continue;
                }

                $found++;

                $length = strlen($selector);
                $after = substr($css, $offset + $length, $length + 4);

                // Le minificateur réécrit `:is(&)` en répétant la classe · même
                // poids, moins de caractères. Les deux formes passent.
                $this->assertTrue(
                    str_starts_with($after, $selector)
                        || str_starts_with($after, ':is('),
                    "« {$selector} » ne pèse qu'une classe dans notre feuille.\n"
                    ."Un media n'ajoute aucun poids : chargée en dernier, elle écrasera la "
                    ."variante d'un paquet.\nMettez falcon/ui-kit à jour, puis lancez `npm run build`.",
                );
            }
        }

        $this->assertGreaterThan(0, $found, 'Aucune règle à point de rupture dans notre feuille.');
    }

    public function test_our_compiled_stylesheet_weights_its_dark_rules(): void
    {
        $this->assertStringNotContainsString(
            ':where(.dark,',
            $this->compiledStylesheet(),
            "Notre feuille écrit encore ses règles sombres en `:where`, qui ne pèse rien.\n"
            .'Chargée en dernier, elle repeindra en clair ce que les paquets voulaient sombre. '
            .'Mettez falcon/ui-kit à jour, puis lancez `npm run build`.',
        );
    }

    /**
     * Tout point de rupture que nous déclarons porte sa paire de variantes.
     *
     * L'un sans l'autre redonne une variante sans poids, en silence · rien à
     * l'écran ne le montre tant qu'on ne redimensionne pas la fenêtre.
     */
    public function test_every_breakpoint_we_declare_carries_its_pair_of_variants(): void
    {
        $entry = $this->contentsOf('resources/css/ui-kit.css');

        preg_match_all('/--breakpoint-([a-z0-9-]+)\s*:/', $entry, $matches);

        foreach ($matches[1] as $breakpoint) {
            foreach ([$breakpoint, 'max-'.$breakpoint] as $variant) {
                $this->assertMatchesRegularExpression(
                    '/@custom-variant\s+'.preg_quote($variant, '/').'\s*\{[^}]*&:is\(&\)/',
                    $entry,
                    "`--breakpoint-{$breakpoint}` est déclaré sans la variante « {$variant} » "
                    ."écrite avec `&:is(&)`.\nElle vivrait dans un media, qui n'ajoute aucun poids.",
                );
            }
        }

        $this->assertTrue(true);
    }

    /**
     * Aucune de nos vues n'emploie de variante à valeur libre.
     *
     * `min-[750px]:` et ses semblables portent leur valeur dans le nom de la
     * classe · aucun `@custom-variant` ne les atteint, Tailwind refusant
     * `@custom-variant min-*`. Elles resteraient du poids d'une classe nue.
     */
    public function test_no_view_uses_a_variant_we_cannot_weigh(): void
    {
        foreach ($this->views() as $path => $contents) {
            $this->assertDoesNotMatchRegularExpression(
                '/(?:min|max|supports)-\[[^\]]*\]:/',
                $contents,
                $path." emploie une variante à valeur libre.\n"
                .'Nommez le point de rupture dans `resources/css/ui-kit.css`, avec sa paire de '
                .'`@custom-variant`, puis employez son nom.',
            );

            // Le nom est coupe en deux · le scanner de Tailwind lit tous les
            // fichiers du depot, et la chaine entiere ecrite ici se retrouverait
            // fabriquee comme une vraie classe dans une feuille compilee.
            $this->assertStringNotContainsString(
                '@'.'container',
                $contents,
                $path.' emploie une requête de conteneur, dont la variante ne peut pas être pesée.',
            );
        }
    }

    /**
     * Aucun élément ne mélange `space-*` ou `divide-*` nu et sa variante.
     *
     * Tailwind écrit ces deux familles dans un `:where()`, où rien ne pèse · la
     * variante ne l'emporte que par l'ordre, et une autre feuille annule les
     * deux. Un `gap` sur un `flex` ou une `grid` fait la même chose, avec un
     * utilitaire ordinaire dont la variante pèse.
     */
    public function test_no_element_pairs_a_weightless_utility_with_its_variant(): void
    {
        foreach ($this->views() as $path => $contents) {
            foreach ($this->classLists($contents) as $classes) {
                $plain = [];
                $varied = [];

                foreach ($classes as $class) {
                    $hasVariant = str_contains($class, ':');
                    $utility = $hasVariant ? substr((string) strrchr($class, ':'), 1) : $class;
                    $family = $this->weightlessFamily($utility);

                    if ($family === null) {
                        continue;
                    }

                    $hasVariant ? $varied[$family] = $class : $plain[$family] = $class;
                }

                foreach (array_intersect_key($plain, $varied) as $family => $class) {
                    $this->fail(
                        "{$path} apparie « {$class} » et « {$varied[$family]} ».\n"
                        .'Tailwind écrit `space-*` et `divide-*` dans un `:where()`, où rien ne pèse : '
                        ."la variante ne l'emporte que par l'ordre, et une autre feuille annule les "
                        ."deux.\nUn `gap` sur un `flex` ou une `grid` fait la même chose ; pour un "
                        .'filet, `divide-subtle`.',
                    );
                }
            }
        }

        $this->assertTrue(true);
    }

    private function weightlessFamily(string $utility): ?string
    {
        foreach (['space', 'divide'] as $family) {
            if (! str_starts_with($utility, $family.'-')) {
                continue;
            }

            $axis = strtok(substr($utility, strlen($family) + 1), '-');

            return in_array($axis, ['x', 'y'], true) ? $family.'-'.$axis : $family.'-color';
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function selectorsFor(string $breakpoint, string $css): array
    {
        // Le nom d'une classe admet des échappements : `.sm\:mt-1\.5`. Le point
        // **non échappé** est exclu · c'est lui qui commence la répétition
        // qu'on veut voir.
        $pattern = '/\.'.preg_quote($breakpoint, '/').'\\\\:(?:\\\\.|[^{}(),:;.\\\\\s])+/';

        preg_match_all($pattern, $css, $matches, PREG_OFFSET_CAPTURE);

        $found = [];

        foreach ($matches[0] as [$selector, $offset]) {
            $found[$offset] = $selector;
        }

        return $found;
    }

    /**
     * Les listes de classes d'une vue.
     *
     * **Toutes les chaînes entre guillemets, et pas seulement `class="…"`.** Un
     * écran assemble souvent ses classes en PHP, et la règle vaut là aussi.
     *
     * @return list<list<string>>
     */
    private function classLists(string $contents): array
    {
        preg_match_all('/"([^"\n]*)"|\'([^\'\n]*)\'/', $contents, $matches, PREG_SET_ORDER);

        $found = [];

        foreach ($matches as $match) {
            $value = trim($match[2] ?? '') !== '' ? $match[2] : ($match[1] ?? '');

            if (trim((string) $value) === '') {
                continue;
            }

            $found[] = preg_split('/\s+/', trim((string) $value)) ?: [];
        }

        return $found;
    }

    /**
     * @return array<string, string>
     */
    private function views(): array
    {
        $root = resource_path('views');
        $found = [];

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($files as $file) {
            if (str_ends_with($file->getFilename(), '.blade.php')) {
                $name = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
                $found[$name] = (string) file_get_contents($file->getPathname());
            }
        }

        $this->assertNotSame([], $found, 'Aucune vue trouvée.');

        return $found;
    }

    /** La feuille que Vite a produite pour nos écrans, nommée par le manifeste. */
    private function compiledStylesheet(): string
    {
        /** @var array<string, array{file: string}> $manifest */
        $manifest = json_decode($this->contentsOf('public/build/manifest.json'), true);

        $this->assertArrayHasKey('resources/css/ui-kit.css', $manifest, 'Lancez `npm run build`.');

        return $this->contentsOf('public/build/'.$manifest['resources/css/ui-kit.css']['file']);
    }

    private function contentsOf(string $path): string
    {
        $full = base_path($path);

        $this->assertFileExists($full);

        return (string) file_get_contents($full);
    }
}
