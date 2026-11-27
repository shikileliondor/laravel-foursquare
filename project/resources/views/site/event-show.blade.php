@extends('site.layout')
@section('title', $item->title)
@section('description', \Illuminate\Support\Str::limit(\App\Support\SiteFormat::eventWhen($item).' · '.strip_tags((string) $item->description), 160))
@if ($item->cover)
    @section('og_image', $item->cover->url())
@endif
@php
    $map = \App\Support\SiteFormat::mapUrl($item);
    $tel = \App\Support\SiteFormat::tel($item->contact_phone);
    $wa = \App\Support\SiteFormat::whatsapp($item->official_whatsapp);
    $place = collect([$item->venue_name, $item->address, $item->commune])->filter()->implode(', ');
@endphp
@push('head')
    <script type="application/ld+json">{!! json_encode(array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Event',
        'name' => $item->title,
        'startDate' => $item->start_at?->toIso8601String(),
        'endDate' => $item->end_at?->toIso8601String(),
        'eventStatus' => $item->status === 'CANCELLED' ? 'https://schema.org/EventCancelled' : 'https://schema.org/EventScheduled',
        'location' => $place ? ['@type' => 'Place', 'name' => $item->venue_name ?: $place, 'address' => $place] : null,
        'image' => $item->cover?->url(),
        'organizer' => $item->organizer_name ? ['@type' => 'Organization', 'name' => $item->organizer_name] : null,
    ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endpush
@section('content')
    <article class="article">
        <a class="back" href="{{ route('site.events.index') }}">← Événements</a>
        @include('site.partials.badge', ['item' => $item])
        <h1>{{ $item->title }}</h1>
        @if ($item->cover)<img class="hero-img" src="{{ $item->cover->url() }}" alt="">@endif
        <dl class="facts card">
            <div><dt>Quand</dt><dd>{{ \App\Support\SiteFormat::eventWhen($item) }}</dd></div>
            @if ($place)<div><dt>Où</dt><dd>{{ $place }}</dd></div>@endif
            @if ($item->organizer_name)<div><dt>Organisé par</dt><dd>{{ $item->organizer_name }}</dd></div>@endif
            @if ($item->contact_phone)<div><dt>Contact</dt><dd>{{ $item->contact_phone }}</dd></div>@endif
        </dl>
        @if ($map || $tel || $wa)
            <p class="actions">
                @if ($map)<a class="btn btn-blue" href="{{ $map }}" rel="noopener" target="_blank">Voir sur la carte</a>@endif
                @if ($tel)<a class="btn btn-ghost" href="{{ $tel }}">Appeler</a>@endif
                @if ($wa)<a class="btn btn-ghost" href="{{ $wa }}" rel="noopener" target="_blank">WhatsApp</a>@endif
            </p>
        @endif
        @if ($item->description)<div class="prose">{!! \App\Support\SiteFormat::html($item->description) !!}</div>@endif
        @include('site.partials.share', ['title' => $item->title])
    </article>
@endsection
