@extends('site.layout')
@section('description', 'Actualités, événements, verset et méditation du jour de '.config('site.name').'.')
@section('content')
    @if ($banners->isNotEmpty())
        <section class="banners" aria-label="À la une">
            @foreach ($banners as $banner)
                @php($link = \App\Support\SiteFormat::bannerUrl($banner))
                <article class="banner">
                    @if ($banner->media)<img src="{{ $banner->media->url() }}" alt="" @if (! $loop->first) loading="lazy" @endif>@endif
                    <div class="banner-text">
                        @if ($banner->title)<h2>{{ $banner->title }}</h2>@endif
                        @if ($banner->subtitle)<p>{{ $banner->subtitle }}</p>@endif
                        @if ($link)<a class="btn btn-gold" href="{{ $link }}" @if ($banner->link_type === 'EXTERNAL') rel="noopener" target="_blank" @endif>{{ $banner->button_text ?: 'Voir' }}</a>@endif
                    </div>
                </article>
            @endforeach
        </section>
    @else
        <section class="hero">
            <img src="/admin/logo.jpg" alt="Logo Foursquare" width="88" height="88">
            <h1>{{ config('site.name') }}</h1>
            @if ($siteChurch)<p>{{ $siteChurch->district?->name }} · Église Foursquare Côte d’Ivoire</p>@endif
        </section>
    @endif

    @if ($verse || $meditation)
        <section class="duo">
            @if ($verse)
                <article class="card verse">
                    <p class="eyebrow">Verset du jour</p>
                    <blockquote>« {{ $verse->text }} »</blockquote>
                    <p class="ref">{{ $verse->reference }}@if ($verse->bible_version) · {{ $verse->bible_version }}@endif</p>
                </article>
            @endif
            @if ($meditation)
                <article class="card">
                    <p class="eyebrow">Méditation du jour</p>
                    <h3>{{ $meditation->title }}</h3>
                    <p class="muted clamp">{{ $meditation->excerpt(220) }}</p>
                    <a class="btn btn-ghost" href="{{ route('site.meditations.show', $meditation->id) }}">Lire la méditation</a>
                </article>
            @endif
        </section>
    @endif

    @if ($featured->isNotEmpty())
        <section>
            <div class="section-head"><h2>À la une</h2></div>
            <div class="grid">@foreach ($featured as $item) @include('site.partials.news-card') @endforeach</div>
        </section>
    @endif

    <section>
        <div class="section-head"><h2>Prochains événements</h2><a href="{{ route('site.events.index') }}">Tout voir</a></div>
        @if ($events->isEmpty())
            @include('site.partials.empty', ['text' => 'Aucun événement à venir pour le moment.'])
        @else
            <div class="grid">@foreach ($events as $item) @include('site.partials.event-card') @endforeach</div>
        @endif
    </section>

    <section>
        <div class="section-head"><h2>Dernières actualités</h2><a href="{{ route('site.news.index') }}">Tout voir</a></div>
        @if ($latest->isEmpty())
            @include('site.partials.empty', ['text' => 'Aucune actualité pour le moment.'])
        @else
            <div class="grid">@foreach ($latest as $item) @include('site.partials.news-card') @endforeach</div>
        @endif
    </section>

    @php($links = array_filter(['Facebook' => $social['facebook_url'] ?? null, 'YouTube' => $social['youtube_url'] ?? null, 'TikTok' => $social['tiktok_url'] ?? null]))
    @php($store = config('site.play_store_url'))
    @if ($links || $store)
        <section class="card cta">
            <div>
                <h2>Restez connectés</h2>
                <p class="muted">Suivez Foursquare Côte d’Ivoire et recevez les annonces sur votre téléphone.</p>
            </div>
            <div class="cta-links">
                @if ($store)<a class="btn btn-red" href="{{ $store }}" rel="noopener" target="_blank">Télécharger l’app</a>@endif
                @foreach ($links as $label => $url)<a class="btn btn-ghost" href="{{ $url }}" rel="noopener" target="_blank">{{ $label }}</a>@endforeach
            </div>
        </section>
    @endif
@endsection
