@extends('site.layout')
@section('title', 'Connexion impossible')
@section('content')
    <section class="hero">
        <h1>Connexion impossible</h1>
        <p class="muted">Le site ne parvient pas à charger son contenu pour le moment.</p>
        <a class="btn btn-blue" href="{{ url()->current() }}">Réessayer</a>
    </section>
@endsection
