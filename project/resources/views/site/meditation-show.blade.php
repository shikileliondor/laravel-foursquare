@extends('site.layout')
@section('title', $item->title)
@section('description', $item->excerpt(160))
@if ($item->image)
    @section('og_image', $item->image->url())
@endif
@section('content')
    <article class="article">
        <a class="back" href="{{ route('site.meditations.index') }}">← Méditations</a>
        <p class="meta">{{ \App\Support\SiteFormat::date($item->publication_date) }}</p>
        <h1>{{ $item->title }}</h1>
        @if ($item->scripture_reference)<p class="ref">{{ $item->scripture_reference }}</p>@endif
        @if ($item->image)<img class="hero-img" src="{{ $item->image->url() }}" alt="">@endif
        <div class="prose">{!! \App\Support\SiteFormat::html($item->content) !!}</div>
        @if ($item->author)<p class="muted">— {{ $item->author }}</p>@endif
        @include('site.partials.share', ['title' => $item->title])
    </article>
@endsection
