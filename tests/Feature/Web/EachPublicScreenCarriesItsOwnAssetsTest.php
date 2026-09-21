<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Vite;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Chaque page publique se rend, et porte ses propres assets.
 *
 * Les deux moities repondent a deux pannes qui n'ont rien a voir.
 *
 * **Se rendre** est ce que `socle/tests.md` §1 exige de tout ecran, et ce qui
 * attrape un gabarit introuvable, un composant absent, une variable non passee
 * a la vue, ou une entree de build absente du manifeste — `@vite` levant alors
 * plutot que de servir une page.
 *
 * **Porter ses assets** est ce qu'aucun essai ne voyait. Un `@vite` supprime ne
 * casse rien : la page repond, elle est simplement sans style, et seul l'oeil
 * le remarque. Cette moitie-la vit dans le tableau ci-dessous, qui dit quelle
 * page porte quelle entree ; c'est un contrat, et le deplacer est une decision.
 */
final class EachPublicScreenCarriesItsOwnAssetsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Les six pages publiques, et l'entree que chacune porte en propre.
     *
     * `legal` n'en porte aucune, et c'est normal : une page sans style propre
     * n'ecrit pas de section d'assets du tout.
     *
     * @return array<string, array{0: string, 1: list<string>}>
     */
    public static function pages(): array
    {
        return [
            'home' => ['home', ['resources/css/web/home/index.css', 'resources/js/web/home/index.js']],
            'prestations' => ['prestations', ['resources/css/web/prestations/index.css']],
            'about' => ['about', ['resources/css/web/about/index.css']],
            'contact' => ['contact', ['resources/css/web/contact/index.css', 'resources/js/web/contact/index.js']],
            'reviews' => ['reviews', ['resources/css/web/reviews/index.css', 'resources/js/web/reviews/index.js']],
            'legal' => ['legal', []],
        ];
    }

    /**
     * Un rouge ici dit qu'une page a perdu son `@section('assets')`.
     *
     * Le jeu de donnees nomme la page, et l'URL manquante nomme l'entree : la
     * page repond sans erreur et s'affiche sans style, et rien d'autre dans la
     * suite ne le voit.
     *
     * @param  list<string>  $entries
     */
    #[DataProvider('pages')]
    public function test_the_screen_renders_and_carries_its_entries(string $route, array $entries): void
    {
        $screen = $this->get(route($route))->assertOk();

        foreach ($entries as $entry) {
            $screen->assertSee(Vite::asset($entry), escape: false);
        }
    }

    /**
     * La page sans style propre n'en charge aucun.
     *
     * L'assertion negative de la paire : sans elle, un motif qui ne trouverait
     * jamais rien passerait pour un site entierement habille.
     */
    public function test_the_page_without_a_style_of_its_own_loads_none(): void
    {
        $screen = $this->get(route('legal'))->assertOk();

        foreach (self::pages() as [, $entries]) {
            foreach ($entries as $entry) {
                $screen->assertDontSee(Vite::asset($entry), escape: false);
            }
        }
    }
}
