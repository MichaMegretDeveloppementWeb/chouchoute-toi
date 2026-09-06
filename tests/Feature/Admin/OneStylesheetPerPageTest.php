<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use Tests\TestCase;

/**
 * Une page, une feuille Tailwind.
 *
 * Le kit et falcon/booking ne compilent pas de CSS. Ils **déclarent** — leurs
 * écrans à lire, leurs couleurs, leurs règles — et `resources/css/admin.css` les
 * importe. Notre build en produit une seule, où chaque classe n'est écrite
 * qu'une fois.
 *
 * **Pourquoi cette contrainte mérite des essais.** Deux feuilles Tailwind sur
 * une même page écrivent les mêmes noms de classes, et à poids égal la dernière
 * chargée gagne. La nôtre arrive en dernier : son `.bg-white` tombait après le
 * `dark:bg-gray-800` de booking et l'annulait, sans la moindre erreur. Constaté
 * le 2026-09-06 sur le champ de recherche des prestations, blanc sur blanc en
 * mode sombre.
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
     * Aucun gabarit ne charge la feuille compilée d'un paquet.
     *
     * Un paquet peut livrer du CSS **quand ses sélecteurs n'appartiennent qu'à
     * lui** — FullCalendar et ses `.fc-`, Livewire et ses `[wire:loading]`.
     * Jamais une compilation Tailwind, dont les classes sont celles de tout le
     * monde.
     */
    public function test_no_layout_loads_a_compiled_package_stylesheet(): void
    {
        foreach ($this->layouts() as $path => $contents) {
            foreach (['packages/falcon-', 'vendor/falcon/', 'vendor/ui-kit/', '@uiKitStyles'] as $foreign) {
                $this->assertStringNotContainsString(
                    $foreign,
                    $contents,
                    $path.' charge un CSS de paquet. Les paquets déclarent le leur ; '
                    .'importez-le dans `resources/css/admin.css` et compilez.',
                );
            }
        }
    }

    /**
     * Notre entrée importe chaque fournisseur, plutôt que d'aller lire ses vues.
     *
     * Un `@source` qui pointe dans `vendor/` veut dire qu'on devine ce qu'un
     * paquet contient · le jour où il ajoute un dossier de vues, on ne le sait
     * pas et les classes manquent. Son point d'entrée, lui, le déclare pour
     * nous.
     */
    public function test_our_entry_imports_each_provider(): void
    {
        $entry = $this->contentsOf('resources/css/admin.css');

        foreach ([
            '../../vendor/falcon/ui-kit/resources/css/preset.css',
            '../../vendor/falcon/booking/resources/css/booking-admin.css',
        ] as $provider) {
            $this->assertStringContainsString("@import '{$provider}';", $entry);
        }

        preg_match_all('/@source\s+\'([^\']+)\'/', $entry, $matches);

        foreach ($matches[1] as $source) {
            // falcon/analytics n'a pas encore de point d'entrée · la ligne est
            // nommée ici pour qu'elle se remarque, et pour qu'elle devienne un
            // `@import` le jour où il en déclarera un.
            if ($source === '../../vendor/falcon/analytics/resources/views/**/*.blade.php') {
                continue;
            }

            $this->assertStringStartsWith(
                '../views/',
                $source,
                "« {$source} » va lire les vues d'un paquet à sa place. Importez son point d'entrée.",
            );
        }
    }

    /**
     * Nos surcharges de composants ne débordent pas sur les paquets.
     *
     * Un fichier posé dans `resources/views/components/ui/` remplace le
     * composant pour **tout le monde**, écrans de paquets compris · c'est ainsi
     * que falcon/analytics a un jour rendu ses boutons avec le dessin de
     * falcon/booking. La pile déclarée par `UiKit::componentsFor('app', …)` les
     * réserve à nos écrans, et `<x-app-ui::…>` est le nom qui la lit.
     */
    public function test_our_overrides_are_called_through_our_own_stack(): void
    {
        $overridden = [];

        foreach ((array) glob(resource_path('views/components/ui/{,*/}*.blade.php'), GLOB_BRACE) as $path) {
            $name = str_replace('\\', '/', substr((string) $path, strlen(resource_path('views/components/ui')) + 1));
            $overridden[] = str_replace('/', '.', substr($name, 0, -strlen('.blade.php')));
        }

        $this->assertNotSame([], $overridden, 'Aucune surcharge trouvée.');

        foreach ($this->views() as $path => $contents) {
            foreach ($overridden as $component) {
                // `sidebar.index` s'appelle `sidebar`, et ses enfants portent
                // leur segment · on compare sur la racine du nom.
                $root = explode('.', $component)[0];

                $this->assertStringNotContainsString(
                    '<x-ui.'.$root,
                    $contents,
                    $path." appelle « {$root} » par le créneau global, alors que nous le "
                    ."surchargeons.\nNotre copie s'appliquerait aussi aux écrans des paquets. "
                    .'Écrivez `<x-app-ui::'.$root.'>`.',
                );
            }
        }
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

    /**
     * @return array<string, string>
     */
    private function views(): array
    {
        $root = resource_path('views');
        $found = [];

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($files as $file) {
            if (str_ends_with($file->getFilename(), '.blade.php')) {
                $name = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
                $found[$name] = (string) file_get_contents($file->getPathname());
            }
        }

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
