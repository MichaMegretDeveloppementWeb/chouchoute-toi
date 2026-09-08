<?php

declare(strict_types=1);

namespace App\Services;

/**
 * The site's Schema.org graph, composed from `config/entreprise.php`.
 *
 * One business, declared once and referenced everywhere: the layout emits the
 * business, the site and the current page, and each page adds only its own
 * nodes, naming the business through {@see self::ref()}. Copies without an
 * `@id` would read as that many unrelated businesses.
 *
 * Two `<script>` tags per page, the layout's and the page's: an engine merges
 * the blocks of one page and resolves the `@id`s between them. Having only one
 * would oblige the layout to receive the page's nodes, for which Blade offers
 * no clean mechanism.
 */
final class SiteGraphService
{
    /**
     * A reference to a node declared elsewhere on the page, which is what
     * replaces copying it out.
     *
     * @return array<string, string>
     */
    public static function ref(string $fragment): array
    {
        return ['@id' => self::id($fragment)];
    }

    /**
     * A node's identifier, always on the site's root and never on the current
     * page: the business does not change with the page mentioning it, and an
     * `@id` carrying the page's address would make as many entities as there
     * are pages.
     */
    public static function id(string $fragment): string
    {
        return url('/').'#'.$fragment;
    }

    /**
     * The nodes the layout lays on every page. The founder is among them
     * although no page asks for her: she is the business's `founder`, and a
     * reference dangling in the void is a broken graph.
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
     * The business: the node everything else refers to.
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
     * The person behind the business: a node of her own and not a `Person`
     * copied out, being both the business's `founder` and the subject of the
     * « À propos » page.
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
     * The site: what every page is part of.
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
     * The current page: neither the site nor the business it speaks of.
     *
     * Its `@id` carries the page's address, unlike every other node, this being
     * the only one that changes from one page to the next.
     *
     * The type comes from the page, which alone knows what it is: declaring it
     * here would oblige the layout to know the routes it renders.
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
     * The towns the business travels to.
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
     * The week, grouped by range.
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
     * The graph, as it enters a `<script>` tag. `JSON_HEX_TAG` is not
     * decorative: without it a « </script> » arriving in a Google review or a
     * treatment's name would close the tag, and everything after it would
     * become HTML.
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
