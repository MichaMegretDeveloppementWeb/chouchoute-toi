<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use Tests\TestCase;

/**
 * Une page, une feuille Tailwind **de nous**.
 *
 * Le kit et les paquets compilent la leur et la livrent déjà compilée ; cette
 * application en publie une copie et la sert, et c'est `@falconStyles` qui la
 * pose dans la page. Notre build ne produit que la nôtre, une par espace, où
 * chaque classe n'est écrite qu'une fois.
 *
 * **Pourquoi cette contrainte mérite des essais.** Deux compilations Tailwind
 * sans préfixe sur une même page écrivent les mêmes noms de classes, et à poids
 * égal la dernière chargée gagne · une règle claire tombe alors après une règle
 * sombre et l'annule, sans la moindre erreur. Les feuilles de la suite ne se
 * heurtent pas à la nôtre pour cette raison précise : chacune porte son propre
 * préfixe, et l'ordre des huit couches règle le reste.
 *
 * Le retour en arrière est facile et silencieux · on rajoute un `@vite` « pour
 * que ça marche », et le défaut revient sur un écran qu'on ne regarde pas tous
 * les jours en mode sombre. Ces essais tiennent la frontière.
 */
final class OneStylesheetPerPageTest extends TestCase
{
    /**
     * Nos gabarits ne chargent qu'une entrée Tailwind.
     *
     * `@vite` peut nommer plusieurs fichiers, et plusieurs `@vite` peuvent
     * cohabiter · ce qui compte est qu'un seul des fichiers nommés soit une
     * compilation Tailwind. Les autres sont du CSS écrit à la main, dont les
     * sélecteurs n'appartiennent qu'à nous et ne croisent rien.
     */
    public function test_each_layout_loads_a_single_tailwind_entry(): void
    {
        foreach ($this->layouts() as $path => $contents) {
            preg_match_all('/@vite\(\[?(.*?)\]?\)/s', $contents, $matches);

            $tailwind = [];

            foreach ($matches[1] as $call) {
                preg_match_all("/'([^']+\.css)'/", $call, $files);

                foreach ($files[1] as $file) {
                    if ($this->isATailwindEntry($file)) {
                        $tailwind[] = $file;
                    }
                }
            }

            $this->assertLessThanOrEqual(
                1,
                count($tailwind),
                $path.' charge '.count($tailwind).' compilations Tailwind : '.implode(', ', $tailwind)."\n"
                .'Deux feuilles écrivent les mêmes classes, et la dernière chargée gagne. '
                .'Une seule entrée par page ; les fichiers par page ne portent que du CSS écrit à la main.',
            );
        }
    }

    /**
     * Aucun gabarit ne va chercher lui-même la feuille d'un paquet.
     *
     * Un paquet compile la sienne et la livre ; elle arrive dans la page par
     * `@falconStyles`, que le kit rend, et non par notre build. Un gabarit qui
     * la nommerait dans un `@vite` la ferait recompiler ici — c'est-à-dire
     * remettrait à cette application la charge que le paquet a reprise.
     */
    public function test_no_layout_loads_a_compiled_package_stylesheet(): void
    {
        foreach ($this->layouts() as $path => $contents) {
            foreach (['packages/falcon-', 'vendor/falcon/', 'vendor/ui-kit/'] as $foreign) {
                $this->assertStringNotContainsString(
                    $foreign,
                    $contents,
                    $path.' va chercher lui-même la feuille d’un paquet. '
                    .'Le paquet la compile et la livre ; publiez-la avec '
                    .'`php artisan vendor:publish --tag=laravel-assets --force`, '
                    .'et laissez `@falconStyles` la poser.',
                );
            }
        }
    }

    /**
     * `app.css` porte le commun, et n'est pas une entrée.
     *
     * **Lui donner Tailwind en ferait une seconde feuille sur chaque page.**
     * `admin.css` et `web.css` l'importent chacun dans sa compilation : ce qui
     * y est écrit part donc dans les deux, une seule fois dans chacune.
     *
     * Le piège est facile · on ajoute `@import 'tailwindcss'` en haut « pour
     * que l'éditeur comprenne le fichier », et le `.bg-white` du commun tombe
     * après tout le reste sur toutes les pages.
     */
    public function test_the_common_fragment_is_imported_and_carries_no_tailwind(): void
    {
        $common = $this->contentsOf('resources/css/app.css');

        // En début de ligne · le fichier a le droit de nommer la directive dans
        // sa prose pour expliquer pourquoi il ne la porte pas, et une recherche
        // de sous-chaîne prendrait ce commentaire pour la directive elle-même.
        $this->assertDoesNotMatchRegularExpression(
            "/^\s*@import\s+'tailwindcss'/m",
            $common,
            "resources/css/app.css est le commun, pas une entrée : il ne compile pas Tailwind.\n"
            .'Deux feuilles écrivent les mêmes classes, et la dernière chargée gagne.',
        );

        foreach (['resources/css/admin.css', 'resources/css/web.css'] as $entry) {
            $this->assertStringContainsString(
                "@import './app.css';",
                $this->contentsOf($entry),
                $entry." n'importe pas le commun : ce qui y sera écrit ne l'atteindra jamais.",
            );
        }

        $this->assertStringNotContainsString(
            "'resources/css/app.css'",
            (string) file_get_contents(base_path('vite.config.js')),
            "app.css est déclaré comme entrée Vite alors qu'il est importé : le build en ferait un fichier orphelin.",
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

    /** Un fichier qui déclare le générateur d'utilitaires, par opposition à du CSS écrit. */
    private function isATailwindEntry(string $file): bool
    {
        $path = base_path($file);

        if (! is_file($path)) {
            return false;
        }

        return str_contains((string) file_get_contents($path), "@import 'tailwindcss'");
    }

    private function contentsOf(string $path): string
    {
        $full = base_path($path);

        $this->assertFileExists($full);

        return (string) file_get_contents($full);
    }
}
