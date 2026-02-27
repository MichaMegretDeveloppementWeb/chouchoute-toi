@extends('layouts.web')

@section('title', 'Accueil')
@section('meta_description', 'Extensions de cils pose par pose à domicile sur Évian-les-Bains, Thonon-les-Bains et le bassin lémanique. Pose complète, remplissage, volume russe. Réservez votre séance avec Chouchoute-toi.')

@section('assets')
    @vite([
        'resources/css/web/home/index.css',
        'resources/js/web/home/index.js',
    ])
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
