@extends('site.layout')
@section('title', $item->title)
@section('description', \Illuminate\Support\Str::limit($item->excerpt ?: strip_tags((string) $item->content), 160))
@if ($item->cover)
    @section('og_image', $item->cover->url())
@endif
@section('content')
    <article class="article">
        <a class="back" href="{{ route('site.news.index') }}">← Actualités</a>
        @include('site.partials.badge', ['item' => $item])
        <h1>{{ $item->title }}</h1>
        @if ($item->published_at)<p class="meta">{{ \App\Support\SiteFormat::date($item->published_at) }}</p>@endif
        @if ($item->cover)<img class="hero-img" src="{{ $item->cover->url() }}" alt="">@endif
        @if ($item->excerpt)<p class="lead">{{ $item->excerpt }}</p>@endif
        <div class="prose">{!! \App\Support\SiteFormat::html($item->content) !!}</div>
        @include('site.partials.share', ['title' => $item->title])
    </article>
@endsection
