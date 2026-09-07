<?php

declare(strict_types=1);

namespace Tests\Feature\Seo;

use App\Services\SiteGraphService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Le site declare une entreprise, une seule, et chaque page la designe.
 *
 * Les cinq pages publiques reecrivaient chacune un `BeautySalon` complet avec
 * sa propre adresse postale, souvent ses coordonnees geographiques et ses
 * horaires. Six copies, **aucun `@id`** : un moteur y lisait six entreprises
 * sans rapport la ou il n'y en a qu'une, et une correction en oubliait
 * toujours une.
 *
 * Ce fichier lit les blocs `ld+json` comme un moteur les lirait · en les
 * decodant, jamais en cherchant une chaine dans la page.
 */
final class TheSiteSaysWhoItIsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Les six pages publiques, par leur nom de route.
     *
     * @return list<array{string}>
     */
    public static function pages(): array
    {
        return [
            ['home'],
            ['prestations'],
            ['about'],
            ['contact'],
            ['reviews'],
            ['legal'],
        ];
    }

    /**
     * Tous les nœuds d'une page, blocs confondus.
     *
     * Le gabarit en pose un et la page un second · un moteur fusionne les
     * blocs d'une meme page, et c'est bien un seul graphe qu'on relit.
     *
     * @return list<array<string, mixed>>
     */
    private function nodesOf(string $route): array
    {
        $page = $this->get(route($route))->assertOk()->getContent() ?: '';
        $nodes = [];
        $offset = 0;

        while (($opens = mb_strpos($page, '<script type="application/ld+json">', $offset)) !== false) {
            $from = $opens + mb_strlen('<script type="application/ld+json">');
            $to = mb_strpos($page, '</script>', $from);

            $this->assertIsInt($to, 'Un bloc JSON-LD est ouvert et jamais ferme.');

            $decoded = json_decode(mb_substr($page, $from, $to - $from), associative: true);

            $this->assertIsArray($decoded, "Le bloc JSON-LD de « {$route} » devrait etre du JSON valide.");
            $this->assertArrayHasKey('@graph', $decoded, 'Chaque bloc porte un @graph.');

            $nodes = [...$nodes, ...$decoded['@graph']];
            $offset = $to;
        }

        $this->assertNotSame([], $nodes, "La page « {$route} » ne porte aucun nœud.");

        return $nodes;
    }

    /**
     * Les nœuds **declares**, a n'importe quelle profondeur.
     *
     * En JSON-LD, un objet qui ne porte qu'un `@id` est un renvoi ; un objet
     * qui porte autre chose en plus **declare** l'entite. La distinction est
     * tout le mecanisme du decoupage entre le gabarit et la page, et un nœud
     * imbrique declare aussi surement qu'un nœud de premier rang.
     *
     * @param  array<mixed>  $nodes
     * @return list<string>
     */
    private function declaredIn(array $nodes): array
    {
        $declared = [];

        $walk = static function (array $value) use (&$walk, &$declared): void {
            if (isset($value['@id']) && is_string($value['@id']) && count($value) > 1) {
                $declared[] = $value['@id'];
            }

            foreach ($value as $child) {
                if (is_array($child)) {
                    $walk($child);
                }
            }
        };

        $walk($nodes);

        return $declared;
    }

    /**
     * Les nœuds de premier rang, fusionnes par `@id`.
     *
     * C'est ce que fait un moteur · le gabarit declare l'entreprise, la page y
     * ajoute une propriete en ne repetant que son identifiant, et les deux ne
     * font qu'une entite.
     *
     * @param  list<array<string, mixed>>  $nodes
     * @return array<string, array<string, mixed>>
     */
    private function merged(array $nodes): array
    {
        $merged = [];

        foreach ($nodes as $node) {
            $id = $node['@id'] ?? null;

            if (is_string($id)) {
                $merged[$id] = array_merge($merged[$id] ?? [], $node);
            }
        }

        return $merged;
    }

    #[DataProvider('pages')]
    public function test_every_page_carries_a_readable_graph(string $route): void
    {
        $this->nodesOf($route);
    }

    /**
     * **L'entreprise est decrite une fois, et son adresse ecrite une fois.**
     *
     * C'est la preuve directe que les recopies ont disparu. La page peut
     * ajouter a l'entreprise — c'est meme le but — mais un seul nœud la
     * **decrit** : les autres ne portent que son `@id` et ce qu'ils lui
     * ajoutent.
     */
    #[DataProvider('pages')]
    public function test_the_business_is_described_once_per_page(string $route): void
    {
        $nodes = $this->nodesOf($route);

        $descriptions = array_filter(
            $nodes,
            static fn (array $node): bool => ($node['@id'] ?? null) === SiteGraphService::id('business')
                && isset($node['@type']),
        );

        $this->assertCount(1, $descriptions, 'Un seul nœud doit decrire l’entreprise.');

        $this->assertSame(
            1,
            $this->countKey($nodes, 'streetAddress'),
            'La rue ne doit etre ecrite qu’une fois dans le graphe.',
        );
    }

    /**
     * Combien de fois une cle parait dans le graphe, a n'importe quelle
     * profondeur.
     *
     * @param  array<mixed>  $nodes
     */
    private function countKey(array $nodes, string $needle): int
    {
        $count = 0;

        array_walk_recursive($nodes, static function (mixed $value, string|int $key) use (&$count, $needle): void {
            if ($key === $needle) {
                $count++;
            }
        });

        return $count;
    }

    /**
     * **Aucune reference ne pend.**
     *
     * Un `@id` cite sans etre declare est un graphe casse. Le decoupage entre
     * le gabarit et la page repose entierement la-dessus · c'est l'essai qui
     * le tient.
     */
    #[DataProvider('pages')]
    public function test_no_reference_dangles(string $route): void
    {
        $nodes = $this->nodesOf($route);

        $declared = $this->declaredIn($nodes);
        $cited = [];

        array_walk_recursive($nodes, static function (mixed $value, string|int $key) use (&$cited): void {
            if ($key === '@id' && is_string($value)) {
                $cited[] = $value;
            }
        });

        $this->assertSame(
            [],
            array_values(array_unique(array_diff($cited, $declared))),
            "La page « {$route} » cite un @id qu’elle ne declare pas.",
        );
    }

    /** Chaque page se nomme, et se rattache au site. */
    #[DataProvider('pages')]
    public function test_every_page_names_itself(string $route): void
    {
        $page = $this->nodeAt($this->nodesOf($route), url()->to(route($route, absolute: false)).'#webpage');

        $this->assertContains($page['@type'], ['WebPage', 'AboutPage', 'ContactPage']);
        $this->assertSame(SiteGraphService::ref('website'), $page['isPartOf']);
        $this->assertSame(SiteGraphService::ref('business'), $page['about']);
        $this->assertNotSame('', $page['name']);
    }

    /**
     * La page « A propos » se dit `AboutPage`, et parle de la fondatrice.
     *
     * Lu sur les nœuds fusionnes · le gabarit declare la page, celle-ci lui
     * ajoute son sujet, et c'est bien une seule entite qu'un moteur voit.
     */
    public function test_the_about_page_says_what_it_is(): void
    {
        $page = $this->merged($this->nodesOf('about'))[url()->to('/a-propos').'#webpage'];

        $this->assertSame('AboutPage', $page['@type']);
        $this->assertSame(SiteGraphService::ref('founder'), $page['mainEntity']);
    }

    public function test_the_contact_page_says_what_it_is(): void
    {
        $page = $this->merged($this->nodesOf('contact'))[url()->to('/contact').'#webpage'];

        $this->assertSame('ContactPage', $page['@type']);
    }

    /**
     * L'entreprise porte ce que `config/entreprise.php` dit, et rien d'ecrit
     * a la main.
     */
    public function test_the_business_reads_the_configuration(): void
    {
        $business = $this->nodeAt($this->nodesOf('home'), SiteGraphService::id('business'));

        $this->assertSame(config('entreprise.nom'), $business['name']);
        $this->assertSame(config('entreprise.telephone'), $business['telephone']);
        $this->assertSame(config('entreprise.adresse.rue'), $business['address']['streetAddress']);
        $this->assertCount(count((array) config('entreprise.villes_desservies')), $business['areaServed']);
    }

    /**
     * Le nœud d'une meme entite se complete d'un bloc a l'autre.
     *
     * C'est le mecanisme sur lequel repose tout le decoupage · la page ajoute
     * une propriete a l'entreprise en ne repetant que son `@id`.
     */
    public function test_a_page_completes_the_business_without_repeating_it(): void
    {
        $nodes = $this->nodesOf('contact');

        $addition = null;

        foreach ($nodes as $node) {
            if (($node['@id'] ?? null) === SiteGraphService::id('business') && ! isset($node['@type'])) {
                $addition = $node;
            }
        }

        $this->assertIsArray($addition, 'La page contact devrait completer l’entreprise.');
        $this->assertSame(config('entreprise.telephone'), $addition['contactPoint'][0]['telephone']);

        // Et elle ne redit rien de ce que le gabarit a deja dit.
        $this->assertArrayNotHasKey('address', $addition);
        $this->assertArrayNotHasKey('name', $addition);
    }

    /**
     * Un « </script> » dans un avis ne ferme pas la balise.
     *
     * `JSON_HEX_TAG` n'est pas decoratif · les avis viennent de Google, donc
     * de dehors, et rien ne garantit ce qu'ils contiennent.
     */
    public function test_nothing_can_close_the_script_tag(): void
    {
        $rendered = SiteGraphService::render([
            ['@type' => 'Review', 'reviewBody' => 'Parfait </script><b>casse'],
        ]);

        $this->assertStringNotContainsString('</script>', $rendered);
        $this->assertSame(
            'Parfait </script><b>casse',
            json_decode($rendered, true)['@graph'][0]['reviewBody'],
            'Le texte doit revenir entier apres decodage.',
        );
    }

    /**
     * Le nœud portant un `@id` donne.
     *
     * @param  list<array<string, mixed>>  $nodes
     * @return array<string, mixed>
     */
    private function nodeAt(array $nodes, string $id): array
    {
        foreach ($nodes as $node) {
            if (($node['@id'] ?? null) === $id && isset($node['@type'])) {
                return $node;
            }
        }

        $this->fail("Le graphe ne porte aucun nœud « {$id} ».");
    }
}
