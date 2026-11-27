@extends('site.layout')
@section('title', 'Événements')
@section('content')
    <h1>Événements</h1>
    <nav class="tabs" aria-label="Période">
        @foreach (['UPCOMING' => 'À venir', 'ONGOING' => 'En cours', 'PAST' => 'Passés'] as $key => $label)
            <a href="{{ route('site.events.index', $key === 'UPCOMING' ? [] : ['statut' => $key]) }}" @if ($status === $key) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>
    @if ($items->isEmpty())
        @include('site.partials.empty', ['text' => match ($status) {
            'ONGOING' => 'Aucun événement en cours.',
            'PAST' => 'Aucun événement passé.',
            default => 'Aucun événement à venir pour le moment.',
        }])
    @else
        <div class="grid">@foreach ($items as $item) @include('site.partials.event-card') @endforeach</div>
        @include('site.partials.pager')
    @endif
@endsection
