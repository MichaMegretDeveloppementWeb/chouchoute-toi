<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Le graphe Schema.org du site, compose depuis `config/entreprise.php`.
 *
 * **Une entreprise, declaree une fois, designee partout.** Chacune des cinq
 * pages publiques reecrivait un `BeautySalon` complet avec sa propre adresse
 * postale, ses coordonnees et ses horaires : six copies, aucune ne portant
 * d'`@id`. Un moteur y lisait six entreprises sans rapport la ou il n'y en a
 * qu'une, et une correction en oubliait toujours une.
 *
 * Le gabarit emet desormais l'entreprise, le site et la page courante ; chaque
 * page n'ajoute que ses propres nœuds, et designe l'entreprise par
 * {@see self::ref()}.
 *
 * **Deux balises `<script>` par page**, celle du gabarit et celle de la page ·
 * un moteur fusionne les blocs d'une meme page et resout les `@id` entre eux.
 * N'en avoir qu'une obligerait le gabarit a recevoir les nœuds de la page, et
 * Blade n'offre aucun mecanisme propre pour cela.
 */
final class SiteGraphService
{
    /**
     * Un renvoi vers un nœud declare ailleurs sur la page.
     *
     * C'est ce qui remplace la recopie · `SiteGraphService::ref('business')`
     * pese trois mots la ou l'entreprise en pesait quarante.
     *
     * @return array<string, string>
     */
    public static function ref(string $fragment): array
    {
        return ['@id' => self::id($fragment)];
    }

    /**
     * L'identifiant d'un nœud · toujours sur la racine du site, jamais sur la
     * page courante.
     *
     * L'entreprise ne change pas selon la page qui la mentionne, et un `@id`
     * qui porterait l'adresse de la page en ferait autant d'entites qu'il y a
     * de pages · c'est exactement ce qu'on repare ici.
     */
    public static function id(string $fragment): string
    {
        return url('/').'#'.$fragment;
    }

    /**
     * Les quatre nœuds que le gabarit pose sur chaque page.
     *
     * La fondatrice en fait partie bien qu'aucune page ne la reclame · elle est
     * le `founder` de l'entreprise, et une reference qui pend dans le vide est
     * un graphe casse.
     *
     * @return list<array<string, mixed>>
     */
    public static function siteNodes(string $title, string $description, string $pageType = 'WebPage'): array
    {
        return [
            self::business(),
            self::founder(),
            self::website(),
            self::webPage($title, $description, $pageType),
        ];
    }

    /**
     * L'entreprise · le nœud que tout le reste designe.
     *
     * @return array<string, mixed>
     */
    public static function business(): array
    {
        $address = (array) config('entreprise.adresse');

        return [
            '@type' => 'BeautySalon',
            '@id' => self::id('business'),
            'name' => config('entreprise.nom'),
            'alternateName' => config('entreprise.nom_court'),
            'description' => config('entreprise.description'),
            'slogan' => config('entreprise.slogan'),
            'url' => url('/'),
            'telephone' => config('entreprise.telephone'),
            'email' => config('entreprise.email'),
            'image' => asset((string) config('entreprise.image')),
            'founder' => self::ref('founder'),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $address['rue'],
                'addressLocality' => $address['ville'],
                'postalCode' => $address['code_postal'],
                'addressRegion' => $address['region'],
                'addressCountry' => $address['pays'],
            ],
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => config('entreprise.coordonnees.latitude'),
                'longitude' => config('entreprise.coordonnees.longitude'),
            ],
            'openingHoursSpecification' => self::openingHours(),
            'areaServed' => self::areaServed(),
            'sameAs' => config('entreprise.reseaux'),
            'priceRange' => config('entreprise.gamme_de_prix'),
            'paymentAccepted' => config('entreprise.paiements'),
            'currenciesAccepted' => config('entreprise.devise'),
            'knowsAbout' => config('entreprise.savoir_faire'),
        ];
    }

    /**
     * La personne derriere l'entreprise.
     *
     * Un nœud a elle, et non une `Person` recopiee · elle est le `founder` de
     * l'entreprise et le sujet de la page « A propos », qui la decrivaient
     * chacune de leur cote.
     *
     * @return array<string, mixed>
     */
    public static function founder(): array
    {
        return [
            '@type' => 'Person',
            '@id' => self::id('founder'),
            'name' => config('entreprise.fondatrice.nom'),
            'jobTitle' => config('entreprise.fondatrice.fonction'),
            'description' => config('entreprise.fondatrice.description'),
            'image' => asset((string) config('entreprise.fondatrice.portrait')),
            'worksFor' => self::ref('business'),
            'knowsAbout' => config('entreprise.savoir_faire'),
            'sameAs' => config('entreprise.reseaux'),
        ];
    }

    /**
     * Le site · ce dont chaque page fait partie.
     *
     * @return array<string, mixed>
     */
    public static function website(): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => self::id('website'),
            'name' => config('entreprise.nom_court'),
            'alternateName' => config('entreprise.nom'),
            'url' => url('/'),
            'description' => config('entreprise.description'),
            'inLanguage' => 'fr-FR',
            'publisher' => self::ref('business'),
        ];
    }

    /**
     * La page courante · ni le site, ni l'entreprise dont elle parle.
     *
     * Son `@id` porte l'adresse de la page, contrairement a tous les autres ·
     * c'est le seul nœud qui change d'une page a l'autre, et c'est bien de
     * pages differentes qu'il s'agit.
     *
     * Le type vient de la page, qui seule sait ce qu'elle est · `AboutPage` et
     * `ContactPage` descendent de `WebPage`, et le declarer ici demanderait au
     * gabarit de connaitre les routes qu'il rend.
     *
     * @return array<string, mixed>
     */
    public static function webPage(string $title, string $description, string $type = 'WebPage'): array
    {
        return [
            '@type' => $type,
            '@id' => url()->current().'#webpage',
            'url' => url()->current(),
            'name' => $title,
            'description' => $description,
            'inLanguage' => 'fr-FR',
            'isPartOf' => self::ref('website'),
            'about' => self::ref('business'),
            'primaryImageOfPage' => asset((string) config('entreprise.image')),
        ];
    }

    /**
     * Les villes ou l'on se deplace.
     *
     * @return list<array<string, string>>
     */
    public static function areaServed(): array
    {
        return array_map(
            static fn (string $city): array => ['@type' => 'City', 'name' => $city],
            (array) config('entreprise.villes_desservies'),
        );
    }

    /**
     * La semaine, groupee par plage.
     *
     * @return list<array<string, mixed>>
     */
    public static function openingHours(): array
    {
        return array_map(
            static fn (array $range): array => [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => $range['jours'],
                'opens' => $range['ouvre'],
                'closes' => $range['ferme'],
            ],
            (array) config('entreprise.horaires'),
        );
    }

    /**
     * Le graphe, tel qu'il entre dans une balise `<script>`.
     *
     * `JSON_HEX_TAG` n'est pas decoratif · sans lui, un « </script> » arrive
     * dans un avis Google ou un nom de prestation fermerait la balise, et tout
     * ce qui suit deviendrait du HTML.
     *
     * @param  list<array<string, mixed>>  $nodes
     */
    public static function render(array $nodes): string
    {
        return (string) json_encode(
            ['@context' => 'https://schema.org', '@graph' => $nodes],
            JSON_HEX_TAG | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }
}
