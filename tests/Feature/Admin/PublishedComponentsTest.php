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
 * D'où la règle : **un paquet qui modifie un composant l'emporte avec lui**, en
 * déclarant sa pile (`UiKit::componentsFor()`). L'hôte ne publie que ce dont il
 * est lui-même le consommateur.
 *
 * Le **toast** est l'exception, et pour la raison même qui fonde la règle : ce
 * qu'on lui a fait — centré, plus grand sur desktop, une barre qui dit le temps
 * qui reste — **n'a rien de propre à la réservation**. L'hôte en est consommateur
 * de plein droit (l'écran de connexion, la coquille du back-office), et le
 * tableau de bord de falcon/analytics l'appelle aussi. Il vit donc **des deux
 * côtés**, identique au caractère près, et le test ci-dessous tient les deux
 * copies ensemble.
 */
final class PublishedComponentsTest extends TestCase
{
    /** La seule copie qui vaille pour tout le monde, et ce qui la justifie. */
    private const SHARED = 'toast.blade.php';

    private function publishedDirectory(): string
    {
        return resource_path('views/components/ui');
    }

    private function packageCopy(string $component): string
    {
        return base_path('packages/falcon-booking/resources/views/components/ui/'.$component);
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
     * La barre latérale, et le toast. `button` et `modal` sont partis dans
     * falcon/booking, qui est le seul à porter leurs variantes.
     */
    public function test_only_the_sidebar_and_the_toast_are_published(): void
    {
        foreach ($this->published() as $component) {
            if ($component === self::SHARED) {
                continue;
            }

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

    /**
     * Le toast vit des deux côtés, et les deux copies ne font qu'un.
     *
     * Deux fichiers rédigés séparément dérivent sans que rien ne le montre, un
     * seul des deux étant à l'écran à la fois : ici c'est le site qu'on voit au
     * quotidien, et la copie du paquet ne se réveillerait que chez le client
     * suivant.
     */
    public function test_the_toast_is_the_same_on_both_sides(): void
    {
        $published = $this->publishedDirectory().'/'.self::SHARED;
        $package = $this->packageCopy(self::SHARED);

        $this->assertFileExists($package, 'Le paquet doit emporter le toast avec lui.');

        $this->assertSame(
            file_get_contents($package),
            file_get_contents($published),
            'Les deux copies du toast ont divergé.',
        );
    }

    /**
     * Le toast centre, et aucun appelant ne peut le ramener dans un coin.
     *
     * Le tableau de bord de falcon/analytics passe `position="top-right"` et vit
     * dans `vendor/`, hors de portée : lire ce réglage rendrait au back-office
     * les deux positions qu'on vient de lui retirer. La propriété reste déclarée
     * — sinon elle atterrirait en attribut HTML — et n'est jamais lue.
     */
    public function test_the_toast_centres_and_obeys_no_corner(): void
    {
        $toast = (string) file_get_contents($this->publishedDirectory().'/'.self::SHARED);

        // Pleine largeur avec un retrait, et non un centrage par transformation :
        // `100vw` compte la barre de défilement là où le centrage ne la compte
        // pas, ce qui décalait la boîte de six pixels sur un écran étroit.
        $this->assertStringContainsString('inset-x-0 top-4', $toast);
        $this->assertStringContainsString('items-center', $toast);
        $this->assertStringNotContainsString('100vw', $toast);

        foreach (['right-4', 'left-4', 'bottom-4', '$position'] as $corner) {
            $this->assertStringNotContainsString(
                $corner,
                $toast,
                "« {$corner} » remettrait le message dans un coin, ou ferait dépendre sa place de l’appelant.",
            );
        }
    }

    /**
     * Plus grand sur desktop, et pas sur téléphone, où la place est comptée.
     *
     * La paire complète : sans l'assertion positive, la négative passerait sur
     * un fichier qui ne dit rien de sa hauteur.
     */
    public function test_the_toast_grows_on_desktop_alone(): void
    {
        $toast = (string) file_get_contents($this->publishedDirectory().'/'.self::SHARED);

        $this->assertMatchesRegularExpression('/sm:min-h-\[\d+px\]/', $toast);
        $this->assertDoesNotMatchRegularExpression(
            '/(?<!sm:)min-h-\[\d+px\]/',
            $toast,
            'Une hauteur minimale sans palier prendrait la place du téléphone.',
        );
    }

    /** La barre porte la couleur de son type, les quatre que l'icône porte déjà. */
    public function test_the_countdown_bar_carries_the_colour_of_its_type(): void
    {
        $toast = (string) file_get_contents($this->publishedDirectory().'/'.self::SHARED);

        foreach (['emerald', 'red', 'amber', 'blue'] as $hue) {
            $this->assertStringContainsString("bg-{$hue}-500 dark:bg-{$hue}-400", $toast);
        }

        $this->assertStringContainsString('fb-toast-countdown', $toast);
    }

    /**
     * Un seul endroit dit combien de temps un message reste.
     *
     * La durée alimente **deux** choses : le minuteur qui retire le message et
     * l'animation qui vide la barre. Deux littéraux dériveraient, et la barre
     * annoncerait un temps qui n'est plus celui du minuteur — sans que rien à
     * l'écran ne le dise.
     */
    public function test_the_countdown_and_the_timer_read_the_same_duration(): void
    {
        $toast = (string) file_get_contents($this->publishedDirectory().'/'.self::SHARED);

        $this->assertStringContainsString('const life = toast.action ? 10000 : 4000;', $toast);
        $this->assertStringContainsString('setTimeout(() => this.remove(id), life)', $toast);
        $this->assertStringContainsString('${toast.life}ms', $toast);

        foreach (['4000', '10000'] as $duration) {
            $this->assertSame(
                1,
                substr_count($toast, $duration),
                "La durée {$duration} est écrite deux fois : les deux copies finiront par ne plus dire la même chose.",
            );
        }
    }
}
