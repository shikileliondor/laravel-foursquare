@extends('site.layout')
@section('title', 'Découvrir Foursquare')
@section('description', 'Histoire, vision, doctrine et organisation de l’Église Foursquare Côte d’Ivoire.')
@section('content')
    <h1>Découvrir Foursquare</h1>
    @if ($items->isEmpty())
        @include('site.partials.empty', ['text' => 'Contenu à venir.'])
    @else
        <div class="grid">
            @foreach ($items as $item)
                <article class="card card-link">
                    @if ($item->image)<img class="cover" src="{{ $item->image->url() }}" alt="" loading="lazy">@endif
                    <div class="card-body">
                        <h3><a href="{{ route('site.discover.show', $item->key) }}">{{ $item->title }}</a></h3>
                        <p class="muted clamp">{{ \Illuminate\Support\Str::limit(trim(strip_tags(str_replace('<', ' <', (string) $item->content))), 140) }}</p>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
@endsection
