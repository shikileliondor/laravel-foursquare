@extends('site.layout')
@section('title', 'Page introuvable')
@section('content')
    <section class="hero">
        <h1>Page introuvable</h1>
        <p class="muted">Cette page n’existe pas ou n’est plus publiée.</p>
        <a class="btn btn-blue" href="{{ route('home') }}">Retour à l’accueil</a>
    </section>
@endsection
