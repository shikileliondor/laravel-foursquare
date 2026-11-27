@extends('site.layout')
@section('title', 'Méditations')
@section('content')
    <h1>Méditations</h1>
    @if ($items->isEmpty())
        @include('site.partials.empty', ['text' => 'Aucune méditation publiée pour le moment.'])
    @else
        <div class="grid">
            @foreach ($items as $item)
                <article class="card card-link">
                    <div class="card-body">
                        <p class="meta">{{ \App\Support\SiteFormat::date($item->publication_date) }}</p>
                        <h3><a href="{{ route('site.meditations.show', $item->id) }}">{{ $item->title }}</a></h3>
                        @if ($item->scripture_reference)<p class="ref">{{ $item->scripture_reference }}</p>@endif
                        <p class="muted clamp">{{ $item->excerpt(180) }}</p>
                    </div>
                </article>
            @endforeach
        </div>
        @include('site.partials.pager')
    @endif
@endsection
