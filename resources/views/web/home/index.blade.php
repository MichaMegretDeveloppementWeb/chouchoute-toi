@extends('layouts.web')

@section('title', 'Extensions de cils à domicile — Évian, Thonon')
@section('meta_description', 'Extensions de cils à domicile sur Évian-les-Bains, Thonon-les-Bains et le bassin lémanique. Pose complète, remplissage, volume russe. Réservez votre séance.')

@section('assets')
    @vite([
        'resources/css/web/home/index.css',
        'resources/js/web/home/index.js',
    ])
@endsection

@section('schema')
    <script type="application/ld+json">
    @php
    $categories = config('tarifs.categories');
    $depose = config('tarifs.depose');

    $offers = [];
    foreach ($categories as $categorie) {
        $offers[] = [
            "@type" => "Offer",
            "itemOffered" => [
                "@type" => "Service",
                "name" => $categorie['pose']['nom'],
                "description" => $categorie['description'],
            ],
            "price" => (string) $categorie['pose']['prix'],
            "priceCurrency" => "EUR",
            "availability" => "https://schema.org/InStock",
        ];
    }
    $offers[] = [
        "@type" => "Offer",
        "itemOffered" => [
            "@type" => "Service",
            "name" => $depose['nom'],
            "description" => $depose['description'],
        ],
        "price" => (string) $depose['prix'],
        "priceCurrency" => "EUR",
        "availability" => "https://schema.org/InStock",
    ];

    echo json_encode([
        "@context" => "https://schema.org",
        "@type" => "BeautySalon",
        "name" => "Chouchoute-toi by Amande",
        "alternateName" => "Chouchoute-toi",
        "description" => "Extensions de cils à domicile sur Évian-les-Bains, Thonon-les-Bains et le bassin lémanique. Pose cil à cil, volume russe, volume mixte et remplissage par technicienne certifiée.",
        "url" => url('/'),
        "telephone" => "+33671637666",
        "email" => "dc.amandine@gmail.com",
        "image" => asset('images/og-image.jpg'),
        "slogan" => "Sublimez votre regard, cil après cil",
        "founder" => [
            "@type" => "Person",
            "name" => "Amandine David-Cruz",
            "jobTitle" => "Technicienne certifiée en extensions de cils",
        ],
        "address" => [
            "@type" => "PostalAddress",
            "streetAddress" => "261 rue des Tattes",
            "addressLocality" => "Publier",
            "postalCode" => "74500",
            "addressRegion" => "Haute-Savoie",
            "addressCountry" => "FR",
        ],
        "geo" => [
            "@type" => "GeoCoordinates",
            "latitude" => 46.3925,
            "longitude" => 6.5456,
        ],
        "openingHoursSpecification" => [
            [
                "@type" => "OpeningHoursSpecification",
                "dayOfWeek" => ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"],
                "opens" => "09:00",
                "closes" => "17:00",
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
        "sameAs" => [
            "https://www.instagram.com/chouchoutetoibyamande/",
            "https://www.facebook.com/p/Chouchoute-Toi-Ongles-Cils-by-Amande-61551795336766/",
        ],
        "priceRange" => "€€",
        "paymentAccepted" => "Espèces, Virement, Paiement mobile",
        "currenciesAccepted" => "EUR",
        "knowsAbout" => [
            "Extensions de cils",
            "Pose cil à cil",
            "Volume russe",
            "Volume mixte",
            "Remplissage extensions de cils",
            "Dépose extensions de cils",
        ],
        "hasOfferCatalog" => [
            "@type" => "OfferCatalog",
            "name" => "Prestations extensions de cils à domicile",
            "itemListElement" => $offers,
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    @endphp
    </script>
    <script type="application/ld+json">
    @php
    echo json_encode([
        "@context" => "https://schema.org",
        "@type" => "WebSite",
        "name" => "Chouchoute-toi",
        "alternateName" => "Chouchoute-toi by Amande",
        "url" => url('/'),
        "description" => "Extensions de cils à domicile sur le bassin lémanique — Évian-les-Bains, Thonon-les-Bains et alentours.",
        "inLanguage" => "fr-FR",
        "publisher" => [
            "@type" => "Organization",
            "name" => "Chouchoute-toi by Amande",
            "url" => url('/'),
            "logo" => asset('images/og-image.jpg'),
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    @endphp
    </script>
    <script type="application/ld+json">
    @php
    echo json_encode([
        "@context" => "https://schema.org",
        "@type" => "FAQPage",
        "mainEntity" => [
            [
                "@type" => "Question",
                "name" => "Combien de temps dure une pose complète d'extensions de cils ?",
                "acceptedAnswer" => [
                    "@type" => "Answer",
                    "text" => "Une pose complète dure en moyenne 1h30 à 2h30 selon le résultat souhaité. Vous serez confortablement installée chez vous pendant toute la durée de la prestation.",
                ],
            ],
            [
                "@type" => "Question",
                "name" => "Les extensions de cils abîment-elles les cils naturels ?",
                "acceptedAnswer" => [
                    "@type" => "Answer",
                    "text" => "Non, à condition qu'elles soient posées par une professionnelle. Chouchoute-toi utilise des produits hypoallergéniques et une technique respectueuse du cycle naturel de vos cils. Chaque extension est posée individuellement sur un cil naturel, sans contact avec la paupière.",
                ],
            ],
            [
                "@type" => "Question",
                "name" => "À quelle fréquence faut-il faire un remplissage d'extensions de cils ?",
                "acceptedAnswer" => [
                    "@type" => "Answer",
                    "text" => "Pour maintenir un regard parfait, un remplissage est recommandé toutes les 2 à 3 semaines selon votre cycle de renouvellement capillaire.",
                ],
            ],
            [
                "@type" => "Question",
                "name" => "Comment préparer son rendez-vous pour une pose d'extensions de cils ?",
                "acceptedAnswer" => [
                    "@type" => "Answer",
                    "text" => "Venez démaquillée au niveau des yeux et évitez les crèmes huileuses le jour même. Prévoyez un espace confortable pour vous allonger (canapé, lit). La technicienne s'occupe de tout le reste.",
                ],
            ],
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    @endphp
    </script>
@endsection

@section('content')
    @include('web.home.partials.hero')
    @include('web.home.partials.prestations-apercu')
    @include('web.home.partials.avant-apres')
    @include('web.home.partials.processus')
    @include('web.home.partials.chiffres')
    @include('web.home.partials.zone-intervention')
    @include('web.home.partials.confiance')
    @include('web.home.partials.faq-rapide')
    @include('web.home.partials.cta')
@endsection
