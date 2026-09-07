@extends('layouts.web')

@section('title', 'À propos · Technicienne certifiée')
@section('meta_description', 'Technicienne certifiée en extensions de cils à domicile. Découvrez l\'histoire et les valeurs de Chouchoute-toi sur Évian et Thonon-les-Bains.')

@section('assets')
    @vite([
        'resources/css/web/about/index.css',
        'resources/js/web/about/index.js',
    ])
@endsection

{{-- ── Ce que cette page ajoute au graphe du site ───────────────────────────

     Presque rien, et c'est le signe que le decoupage tient · le gabarit pose
     deja la page (en `AboutPage`, dit juste au-dessus) et la fondatrice. Il ne
     reste qu'a dire que cette page parle d'elle.

     Elle portait une `Person` complete avec son `worksFor`, lui-meme un
     `BeautySalon` complet · trois recopies pour une phrase. --}}
@section('schema_page_type', 'AboutPage')

@section('schema')
    <x-seo.graph :nodes="[
        [
            '@id' => url()->current().'#webpage',
            'mainEntity' => \App\Services\SiteGraphService::ref('founder'),
        ],
    ]" />
@endsection

@section('content')
    @include('web.about.partials.histoire')
    @include('web.about.partials.valeurs')
    @include('web.about.partials.pourquoi')
@endsection
