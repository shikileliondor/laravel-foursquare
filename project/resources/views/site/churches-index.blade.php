@extends('site.layout')
@section('title', 'Annuaire des églises')
@section('content')
    <h1>Annuaire des églises</h1>
    <form class="filters card" method="get" action="{{ route('site.churches.index') }}" role="search">
        <label>Rechercher<input type="search" name="q" value="{{ $q }}" placeholder="Nom, commune, quartier…"></label>
        <label>District
            <select name="district">
                <option value="">Tous les districts</option>
                @foreach ($districts as $d)<option value="{{ $d->id }}" @selected($district === $d->id)>{{ $d->name }}</option>@endforeach
            </select>
        </label>
        <button class="btn btn-blue" type="submit">Filtrer</button>
    </form>
    @if ($items->isEmpty())
        @include('site.partials.empty', ['text' => 'Aucune église ne correspond à votre recherche.'])
    @else
        <div class="grid">
            @foreach ($items as $church)
                <article class="card card-link">
                    <div class="card-body">
                        @if ($church->district)<span class="badge badge-district">{{ $church->district->name }}</span>@endif
                        <h3><a href="{{ route('site.churches.show', $church->slug) }}">{{ $church->name }}</a></h3>
                        @php($where = collect([$church->quartier, $church->commune])->filter()->implode(' · '))
                        @if ($where)<p class="muted">{{ $where }}</p>@endif
                        @if ($church->main_service_day || $church->main_service_time)<p class="meta">Culte : {{ collect([$church->main_service_day, $church->main_service_time])->filter()->implode(' · ') }}</p>@endif
                    </div>
                </article>
            @endforeach
        </div>
        @include('site.partials.pager')
    @endif
@endsection
