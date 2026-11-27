@extends('site.layout')
@section('title', 'Actualités')
@section('content')
    <h1>Actualités</h1>
    <nav class="tabs" aria-label="Filtrer par portée">
        @foreach (['' => 'Toutes', 'NATIONAL' => 'National', 'DISTRICT' => 'District', 'CHURCH' => 'Mon église'] as $key => $label)
            <a href="{{ route('site.news.index', $key ? ['portee' => $key] : []) }}" @if (($scope ?? '') === $key) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>
    @if ($items->isEmpty())
        @include('site.partials.empty', ['text' => 'Aucune actualité pour le moment.'])
    @else
        <div class="grid">@foreach ($items as $item) @include('site.partials.news-card') @endforeach</div>
        @include('site.partials.pager')
    @endif
@endsection
