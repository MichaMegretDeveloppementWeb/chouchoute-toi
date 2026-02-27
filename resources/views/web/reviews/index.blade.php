@extends('layouts.web')

@section('title', 'Avis clients — Extensions de cils à domicile')
@section('meta_description', 'Avis Google de nos clientes sur leurs extensions de cils à domicile. Pose cil à cil, volume russe, remplissage sur Évian et Thonon-les-Bains.')

@section('assets')
    @vite([
        'resources/css/web/reviews/index.css',
        'resources/js/web/reviews/index.js',
    ])
@endsection

@section('schema')
    <script type="application/ld+json">
    @php
    $schema = [
        "@context" => "https://schema.org",
        "@type" => "BeautySalon",
        "name" => "Chouchoute-toi by Amande",
        "url" => url('/'),
        "telephone" => "+33671637666",
        "email" => "dc.amandine@gmail.com",
        "image" => asset('images/og-image.jpg'),
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
        "priceRange" => "€€",
        "sameAs" => [
            "https://www.instagram.com/chouchoutetoibyamande/",
            "https://www.facebook.com/p/Chouchoute-Toi-Ongles-Cils-by-Amande-61551795336766/",
        ],
    ];

    if (! empty($googleRating) && ! empty($googleTotal)) {
        $schema["aggregateRating"] = [
            "@type" => "AggregateRating",
            "ratingValue" => (string) $googleRating,
            "bestRating" => "5",
            "worstRating" => "1",
            "ratingCount" => (string) $googleTotal,
        ];
    }

    if (! empty($googleReviews)) {
        $schema["review"] = [];
        foreach ($googleReviews as $review) {
            $schema["review"][] = [
                "@type" => "Review",
                "author" => [
                    "@type" => "Person",
                    "name" => $review['nom'],
                ],
                "reviewRating" => [
                    "@type" => "Rating",
                    "ratingValue" => (string) $review['note'],
                    "bestRating" => "5",
                    "worstRating" => "1",
                ],
                "reviewBody" => $review['texte'],
            ];
        }
    }

    echo json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    @endphp
    </script>
@endsection

@section('content')
    @include('web.reviews.partials.temoignages', [
        'googleReviews' => $googleReviews ?? [],
        'googleRating' => $googleRating ?? null,
        'googleTotal' => $googleTotal ?? null,
    ])
    @include('web.reviews.partials.confiance')
@endsection
