@extends('layouts.web')

@section('title', 'À propos · Technicienne certifiée')
@section('meta_description', 'Technicienne certifiée en extensions de cils à domicile. Découvrez l\'histoire et les valeurs de Chouchoute-toi sur Évian et Thonon-les-Bains.')

@section('assets')
    @vite([
        'resources/css/web/about/index.css',
        'resources/js/web/about/index.js',
    ])
@endsection

@section('schema')
    <script type="application/ld+json">
    @php
    echo json_encode([
        "@context" => "https://schema.org",
        "@type" => "AboutPage",
        "name" => "À propos de Chouchoute-toi by Amande",
        "description" => "Technicienne certifiée en extensions de cils à domicile sur le bassin lémanique. Découvrez l'histoire et les valeurs de Chouchoute-toi.",
        "url" => route('about'),
        "mainEntity" => [
            "@type" => "Person",
            "name" => "Amandine David-Cruz",
            "jobTitle" => "Technicienne certifiée en extensions de cils",
            "description" => "Passionnée par la beauté du regard, Amandine met son savoir-faire au service de ses clientes avec des extensions de cils naturelles et sur-mesure, directement à domicile.",
            "image" => asset('images/about/portrait-amandine.webp'),
            "worksFor" => [
                "@type" => "BeautySalon",
                "name" => "Chouchoute-toi by Amande",
                "url" => url('/'),
                "telephone" => "+33671637666",
                "address" => [
                    "@type" => "PostalAddress",
                    "addressLocality" => "Publier",
                    "postalCode" => "74500",
                    "addressRegion" => "Haute-Savoie",
                    "addressCountry" => "FR",
                ],
            ],
            "knowsAbout" => [
                "Extensions de cils",
                "Pose cil à cil",
                "Volume russe",
                "Volume mixte",
                "Remplissage extensions de cils",
            ],
            "sameAs" => [
                "https://www.instagram.com/chouchoutetoibyamande/",
                "https://www.facebook.com/p/Chouchoute-Toi-Ongles-Cils-by-Amande-61551795336766/",
            ],
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    @endphp
    </script>
@endsection

@section('content')
    @include('web.about.partials.histoire')
    @include('web.about.partials.valeurs')
    @include('web.about.partials.pourquoi')
@endsection
