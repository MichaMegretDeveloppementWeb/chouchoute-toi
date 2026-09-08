@extends('layouts.web')

@section('title', 'Contact et rendez-vous')
@section('meta_description', 'Prenez rendez-vous pour vos extensions de cils à domicile. Interventions sur Évian, Thonon-les-Bains, Publier et alentours. Réponse rapide.')

@section('assets')
    @vite([
        'resources/css/web/contact/index.css',
        'resources/js/web/contact/index.js',
    ])
@endsection

@push('head-extra')
    @livewireStyles
@endpush

@push('body-scripts')
    @livewireScripts
@endpush

@section('schema_page_type', 'ContactPage')

{{-- What this page adds to the site's graph: the contact point and the FAQ.
     The business, its address, its coordinates and its hours come from the
     layout. --}}
@section('schema')
    @php
    $questions = [
            [
                "@type" => "Question",
                "name" => "Combien de temps dure une pose complète d'extensions de cils ?",
                "acceptedAnswer" => [
                    "@type" => "Answer",
                    "text" => "Une pose complète dure en moyenne 1h30 à 2h30 selon le résultat souhaité (naturel ou volume). Vous serez confortablement installée chez vous, les yeux fermés, pendant toute la durée de la prestation.",
                ],
            ],
            [
                "@type" => "Question",
                "name" => "Les extensions de cils abîment-elles les cils naturels ?",
                "acceptedAnswer" => [
                    "@type" => "Answer",
                    "text" => "Non, à condition qu'elles soient posées par une professionnelle qualifiée. Chouchoute-toi utilise des produits hypoallergéniques et une technique respectueuse du cycle naturel de vos cils. Chaque extension est posée individuellement sur un cil naturel, sans contact avec la paupière.",
                ],
            ],
            [
                "@type" => "Question",
                "name" => "À quelle fréquence faut-il faire un remplissage d'extensions de cils ?",
                "acceptedAnswer" => [
                    "@type" => "Answer",
                    "text" => "Pour maintenir un regard parfait, un remplissage est recommandé toutes les 2 à 3 semaines. Ce délai dépend de votre cycle de renouvellement capillaire naturel et de votre routine quotidienne.",
                ],
            ],
            [
                "@type" => "Question",
                "name" => "Comment préparer son rendez-vous pour une pose d'extensions de cils ?",
                "acceptedAnswer" => [
                    "@type" => "Answer",
                    "text" => "Venez démaquillée au niveau des yeux et évitez les crèmes huileuses sur le contour de l'œil le jour même. Prévoyez un espace confortable où vous pourrez vous allonger (canapé, lit). La technicienne s'occupe de tout le reste.",
                ],
            ],
            [
                "@type" => "Question",
                "name" => "Peut-on se maquiller avec des extensions de cils ?",
                "acceptedAnswer" => [
                    "@type" => "Answer",
                    "text" => "Oui, mais avec quelques précautions. Il faut éviter le mascara waterproof et les démaquillants huileux. Des conseils d'entretien complets sont donnés lors de la première séance pour profiter des extensions le plus longtemps possible.",
                ],
            ],
            [
                "@type" => "Question",
                "name" => "Quelles sont les disponibilités de Chouchoute-toi ?",
                "acceptedAnswer" => [
                    "@type" => "Answer",
                    "text" => "Chouchoute-toi travaille du lundi au samedi, sur rendez-vous. Des créneaux en journée et en soirée sont proposés pour s'adapter à votre emploi du temps. Contactez-nous pour connaître les prochaines disponibilités.",
                ],
            ],
    ];
    @endphp

    <x-seo.graph :nodes="[
        [
            '@id' => \App\Services\SiteGraphService::id('business'),
            'contactPoint' => [[
                '@type' => 'ContactPoint',
                'telephone' => config('entreprise.telephone'),
                'contactType' => 'customer service',
                'availableLanguage' => 'French',
                'areaServed' => config('entreprise.adresse.pays'),
            ]],
        ],
        [
            '@type' => 'FAQPage',
            '@id' => url()->current().'#faq',
            'isPartOf' => ['@id' => url()->current().'#webpage'],
            'mainEntity' => $questions,
        ],
    ]" />
@endsection

@section('content')
    <div data-animate>
        @livewire('contact-form')
    </div>
    @include('web.contact.partials.zone')
    @include('web.contact.partials.faq')
@endsection
