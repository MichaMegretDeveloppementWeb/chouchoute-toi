@extends('layouts.web')

@section('title', 'Prestations et tarifs — Extensions de cils')
@section('meta_description', 'Extensions de cils à domicile : pose complète dès 65 €, remplissage, volume russe, dépose. Tarifs et prestations sur Évian et Thonon-les-Bains.')

@section('assets')
    @vite([
        'resources/css/web/prestations/index.css',
        'resources/js/web/prestations/index.js',
    ])
@endsection

@section('schema')
    <script type="application/ld+json">
    @php
    $categories = config('tarifs.categories');
    $depose = config('tarifs.depose');

    $catalogItems = [];
    foreach ($categories as $slug => $categorie) {
        $subOffers = [];

        $subOffers[] = [
            "@type" => "Offer",
            "itemOffered" => [
                "@type" => "Service",
                "name" => $categorie['pose']['nom'],
                "description" => $categorie['description'],
                "serviceType" => "Pose complète d'extensions de cils",
            ],
            "price" => (string) $categorie['pose']['prix'],
            "priceCurrency" => "EUR",
            "availability" => "https://schema.org/InStock",
        ];

        foreach ($categorie['remplissages'] as $remplissage) {
            $subOffers[] = [
                "@type" => "Offer",
                "itemOffered" => [
                    "@type" => "Service",
                    "name" => $remplissage['nom'] . ' ' . $categorie['nom'],
                    "description" => $remplissage['description'],
                    "serviceType" => "Remplissage d'extensions de cils",
                ],
                "price" => (string) $remplissage['prix'],
                "priceCurrency" => "EUR",
                "availability" => "https://schema.org/InStock",
            ];
        }

        $catalogItems[] = [
            "@type" => "OfferCatalog",
            "name" => $categorie['nom'],
            "description" => $categorie['description'],
            "itemListElement" => $subOffers,
        ];
    }

    $catalogItems[] = [
        "@type" => "OfferCatalog",
        "name" => "Dépose",
        "itemListElement" => [
            [
                "@type" => "Offer",
                "itemOffered" => [
                    "@type" => "Service",
                    "name" => $depose['nom'],
                    "description" => $depose['description'],
                    "serviceType" => "Dépose d'extensions de cils",
                ],
                "price" => (string) $depose['prix'],
                "priceCurrency" => "EUR",
                "availability" => "https://schema.org/InStock",
            ],
        ],
    ];

    echo json_encode([
        "@context" => "https://schema.org",
        "@type" => "Service",
        "name" => "Extensions de cils à domicile",
        "description" => "Pose complète, remplissage et dépose d'extensions de cils à domicile sur Évian-les-Bains, Thonon-les-Bains et le bassin lémanique. Pose cil à cil, volume russe, volume mixte et volume intense par technicienne certifiée.",
        "url" => route('prestations'),
        "serviceType" => "Extensions de cils",
        "termsOfService" => route('legal'),
        "providerMobility" => "dynamic",
        "provider" => [
            "@type" => "BeautySalon",
            "name" => "Chouchoute-toi by Amande",
            "url" => url('/'),
            "telephone" => "+33671637666",
            "email" => "dc.amandine@gmail.com",
            "address" => [
                "@type" => "PostalAddress",
                "streetAddress" => "261 rue des Tattes",
                "addressLocality" => "Publier",
                "postalCode" => "74500",
                "addressRegion" => "Haute-Savoie",
                "addressCountry" => "FR",
            ],
        ],
        "areaServed" => [
            ["@type" => "City", "name" => "Évian-les-Bains"],
            ["@type" => "City", "name" => "Thonon-les-Bains"],
            ["@type" => "City", "name" => "Publier"],
            ["@type" => "City", "name" => "Amphion"],
            ["@type" => "City", "name" => "Maxilly"],
            ["@type" => "City", "name" => "Neuvecelle"],
        ],
        "hasOfferCatalog" => [
            "@type" => "OfferCatalog",
            "name" => "Prestations extensions de cils à domicile",
            "itemListElement" => $catalogItems,
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    @endphp
    </script>
@endsection

@section('content')
    @include('web.prestations.partials.detail')
    @include('web.prestations.partials.tarifs')
    @include('web.prestations.partials.galerie')
@endsection
