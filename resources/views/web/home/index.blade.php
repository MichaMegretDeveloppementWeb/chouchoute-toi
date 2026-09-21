@extends('layouts.web')

@section('title', 'Extensions de cils à domicile · Évian, Thonon')
@section('meta_description', 'Extensions de cils à domicile sur Évian-les-Bains, Thonon-les-Bains et le bassin lémanique. Pose complète, remplissage, volume russe. Réservez votre séance.')

@section('assets')
    @vite([
        'resources/css/web/home/index.css',
        'resources/js/web/home/index.js',
    ])
@endsection

{{-- What this page adds to the site's graph: its catalogue overview and its
     FAQ. The business, the founder, the site and the page come from the layout.

     The first node carries only the business's `@id` and the property it adds
     to it: an engine merges two nodes sharing an `@id`, which is the correct
     way to complete an entity declared elsewhere. --}}
@section('schema')
    @php
        $apercuDuCatalogue = [];

        foreach (config('tarifs.categories') as $categorie) {
            $apercuDuCatalogue[] = [
                '@type' => 'Offer',
                'itemOffered' => [
                    '@type' => 'Service',
                    'name' => $categorie['pose']['nom'],
                    'description' => $categorie['description'],
                ],
                'price' => (string) $categorie['pose']['prix'],
                'priceCurrency' => config('entreprise.devise'),
                'availability' => 'https://schema.org/InStock',
            ];
        }

        $depose = config('tarifs.depose');

        $apercuDuCatalogue[] = [
            '@type' => 'Offer',
            'itemOffered' => [
                '@type' => 'Service',
                'name' => $depose['nom'],
                'description' => $depose['description'],
            ],
            'price' => (string) $depose['prix'],
            'priceCurrency' => config('entreprise.devise'),
            'availability' => 'https://schema.org/InStock',
        ];

        $questions = [
            [
                "Combien de temps dure une pose complète d'extensions de cils ?",
                "Une pose complète dure en moyenne 1h30 à 2h30 selon le résultat souhaité. Vous serez confortablement installée chez vous pendant toute la durée de la prestation.",
            ],
            [
                "Les extensions de cils abîment-elles les cils naturels ?",
                "Non, à condition qu'elles soient posées par une professionnelle. Chouchoute-toi utilise des produits hypoallergéniques et une technique respectueuse du cycle naturel de vos cils. Chaque extension est posée individuellement sur un cil naturel, sans contact avec la paupière.",
            ],
            [
                "À quelle fréquence faut-il faire un remplissage d'extensions de cils ?",
                "Pour maintenir un regard parfait, un remplissage est recommandé toutes les 2 à 3 semaines selon votre cycle de renouvellement capillaire.",
            ],
            [
                "Comment préparer son rendez-vous pour une pose d'extensions de cils ?",
                "Venez démaquillée au niveau des yeux et évitez les crèmes huileuses le jour même. Prévoyez un espace confortable pour vous allonger (canapé, lit). La technicienne s'occupe de tout le reste.",
            ],
        ];
    @endphp

    <x-seo.graph :nodes="[
        [
            '@id' => \App\Services\SiteGraphService::id('business'),
            'hasOfferCatalog' => [
                '@type' => 'OfferCatalog',
                '@id' => url()->current().'#catalogue',
                'name' => 'Prestations extensions de cils à domicile',
                'itemListElement' => $apercuDuCatalogue,
            ],
        ],
        [
            '@type' => 'FAQPage',
            '@id' => url()->current().'#faq',
            'isPartOf' => ['@id' => url()->current().'#webpage'],
            'mainEntity' => array_map(fn (array $q) => [
                '@type' => 'Question',
                'name' => $q[0],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $q[1]],
            ], $questions),
        ],
    ]" />
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
