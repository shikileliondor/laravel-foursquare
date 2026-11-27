@extends('site.layout')
@section('title', $item->title)
@section('description', \Illuminate\Support\Str::limit(trim(strip_tags(str_replace('<', ' <', (string) $item->content))), 160))
@section('content')
    <article class="article">
        <a class="back" href="{{ route('site.discover.index') }}">← Découvrir Foursquare</a>
        <h1>{{ $item->title }}</h1>
        @if ($item->image)<img class="hero-img" src="{{ $item->image->url() }}" alt="">@endif
        @if (filled($item->content))
            <div class="prose">{!! \App\Support\SiteFormat::html($item->content) !!}</div>
        @else
            @include('site.partials.empty', ['text' => 'Contenu à venir.'])
        @endif
    </article>
    @if ($items->count() > 1)
        <nav class="tabs" aria-label="Autres rubriques">
            @foreach ($items as $other)
                <a href="{{ route('site.discover.show', $other->key) }}" @if ($other->key === $item->key) aria-current="page" @endif>{{ $other->title }}</a>
            @endforeach
        </nav>
    @endif
@endsection
