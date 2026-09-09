@extends('layouts.web')

@section('title', 'À propos · Technicienne certifiée')
@section('meta_description', 'Technicienne certifiée en extensions de cils à domicile. Découvrez l\'histoire et les valeurs de Chouchoute-toi sur Évian et Thonon-les-Bains.')

@section('assets')
    @vite(['resources/css/pages/about/index.css'])
@endsection

{{-- What this page adds to the site's graph: almost nothing, the layout
     already laying the page (as `AboutPage`, said just above) and the founder.
     All that is left is to say this page is about her. --}}
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
