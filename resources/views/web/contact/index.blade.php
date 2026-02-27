@extends('layouts.web')

@section('title', 'Contact')
@section('meta_description', 'Prenez rendez-vous pour vos extensions de cils à domicile. Interventions sur Évian-les-Bains, Thonon-les-Bains, Publier, Amphion, Maxilly et Neuvecelle.')

@section('assets')
    @vite([
        'resources/css/web/contact/index.css',
        'resources/js/web/contact/index.js',
    ])
@endsection

@section('content')
    @include('web.contact.partials.formulaire')
    @include('web.contact.partials.zone')
    @include('web.contact.partials.faq')
@endsection
