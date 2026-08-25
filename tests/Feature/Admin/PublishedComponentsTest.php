<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use Falcon\UiKit\UiKit;
use Tests\TestCase;

/**
 * Ce que cette application publie du kit, et pour qui.
 *
 * Une copie posée dans `resources/views/components/ui/` vaut pour **tout le
 * monde** : le kit ajoute son dossier au chercheur de vues global, donc
 * `<x-ui.button>` est un seul nom pour l'hôte comme pour chaque paquet qui
 * s'installe dessus. C'est ainsi que falcon/analytics a rendu ses trente-neuf
 * boutons et sa confirmation avec un dessin fait pour falcon/booking.
 *
 * Un paquet qui veut modifier un composant déclare sa pile
 * (`UiKit::componentsFor()`) et l'emporte avec lui. L'hôte ne publie que ce
 * dont il est lui-même le consommateur.
 */
final class PublishedComponentsTest extends TestCase
{
    private function publishedDirectory(): string
    {
        return resource_path('views/components/ui');
    }

    /**
     * Les composants publiés, chemin relatif au dossier.
     *
     * @return list<string>
     */
    private function published(): array
    {
        $found = str_replace(
            '\\',
            '/',
            (array) glob($this->publishedDirectory().'/{,*/}*.blade.php', GLOB_BRACE),
        );

        sort($found);

        return array_map(
            fn (string $path): string => substr($path, strlen(str_replace('\\', '/', $this->publishedDirectory())) + 1),
            $found,
        );
    }

    /**
     * La barre latérale seule. `button` et `modal` sont partis dans
     * falcon/booking, qui est le seul à porter leurs variantes.
     */
    public function test_only_the_sidebar_is_published(): void
    {
        foreach ($this->published() as $component) {
            $this->assertStringStartsWith(
                'sidebar/',
                $component,
                $component.' est publié pour tout le monde : le déplacer dans la pile du paquet qui le modifie.',
            );
        }
    }

    /** Une copie identique à l'amont ne change rien et fige la version. */
    public function test_nothing_published_is_identical_to_the_kit(): void
    {
        $this->assertNotSame([], $this->published());

        foreach ($this->published() as $component) {
            $upstream = UiKit::viewsPath().'/components/ui/'.$component;

            $this->assertFileExists($upstream, $component.' n’existe pas dans le kit.');

            $this->assertNotSame(
                file_get_contents($upstream),
                file_get_contents($this->publishedDirectory().'/'.$component),
                $component.' est identique au kit : le dépublier plutôt que le figer.',
            );
        }
    }
}
