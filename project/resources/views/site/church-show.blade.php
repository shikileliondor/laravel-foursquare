@extends('site.layout')
@section('title', $church->name)
@section('description', \Illuminate\Support\Str::limit(collect([$church->name, $church->district?->name, $church->commune, strip_tags((string) $church->description)])->filter()->implode(' · '), 160))
@if ($church->image)
    @section('og_image', $church->image->url())
@endif
@php
    $map = \App\Support\SiteFormat::mapUrl($church);
    $tel = \App\Support\SiteFormat::tel($church->secretariat_phone);
    $wa = \App\Support\SiteFormat::whatsapp($church->official_whatsapp);
    $address = collect([$church->address, $church->quartier, $church->commune, $church->city])->filter()->unique()->implode(', ');
    $service = collect([$church->main_service_day, $church->main_service_time])->filter()->implode(' · ');
    $social = array_filter([
        'Facebook' => $church->facebook_url, 'YouTube' => $church->youtube_url,
        'Instagram' => $church->instagram_url, 'TikTok' => $church->tiktok_url, 'Site web' => $church->website_url,
    ]);
@endphp
@push('head')
    <script type="application/ld+json">{!! json_encode(array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Church',
        'name' => $church->name,
        'address' => $address ?: null,
        'telephone' => $church->secretariat_phone,
        'email' => $church->official_email,
        'image' => $church->image?->url(),
        'geo' => $church->latitude && $church->longitude ? ['@type' => 'GeoCoordinates', 'latitude' => $church->latitude, 'longitude' => $church->longitude] : null,
    ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endpush
@section('content')
    <article class="article">
        @unless (request()->routeIs('site.church'))<a class="back" href="{{ route('site.churches.index') }}">← Églises</a>@endunless
        @if ($church->district)<span class="badge badge-district">{{ $church->district->name }}</span>@endif
        <h1>{{ $church->name }}</h1>
        @if ($church->image)<img class="hero-img" src="{{ $church->image->url() }}" alt="">@endif
        <dl class="facts card">
            @if ($church->pastor_name)<div><dt>Pasteur</dt><dd>{{ $church->pastor_name }}</dd></div>@endif
            @if ($address)<div><dt>Adresse</dt><dd>{{ $address }}</dd></div>@endif
            @if ($service)<div><dt>Culte principal</dt><dd>{{ $service }}</dd></div>@endif
            @if ($church->service_times)<div><dt>Horaires</dt><dd class="pre">{{ $church->service_times }}</dd></div>@endif
            @if ($church->secretariat_phone)<div><dt>Secrétariat</dt><dd>{{ $church->secretariat_phone }}</dd></div>@endif
            @if ($church->official_email)<div><dt>E-mail</dt><dd><a href="mailto:{{ $church->official_email }}">{{ $church->official_email }}</a></dd></div>@endif
        </dl>
        @if ($map || $tel || $wa)
            <p class="actions">
                @if ($map)<a class="btn btn-blue" href="{{ $map }}" rel="noopener" target="_blank">Voir sur la carte</a>@endif
                @if ($tel)<a class="btn btn-ghost" href="{{ $tel }}">Appeler</a>@endif
                @if ($wa)<a class="btn btn-ghost" href="{{ $wa }}" rel="noopener" target="_blank">WhatsApp</a>@endif
            </p>
        @endif
        @if ($social)
            <p class="actions">@foreach ($social as $label => $url)<a class="btn btn-ghost" href="{{ $url }}" rel="noopener" target="_blank">{{ $label }}</a>@endforeach</p>
        @endif
        @if ($church->description)<div class="prose">{!! nl2br(e($church->description)) !!}</div>@endif
    </article>

    @if ($events->isNotEmpty())
        <section>
            <div class="section-head"><h2>Prochains événements</h2></div>
            <div class="grid">@foreach ($events as $item) @include('site.partials.event-card') @endforeach</div>
        </section>
    @endif
    @if ($news->isNotEmpty())
        <section>
            <div class="section-head"><h2>Actualités de l’église</h2></div>
            <div class="grid">@foreach ($news as $item) @include('site.partials.news-card') @endforeach</div>
        </section>
    @endif
@endsection
