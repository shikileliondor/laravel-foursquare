<article class="card card-link event-card">
    @if ($item->cover)
        <img class="cover" src="{{ $item->cover->url() }}" alt="" loading="lazy">
    @endif
    <div class="card-body">
        @include('site.partials.badge', ['item' => $item])
        <h3><a href="{{ route('site.events.show', $item->slug) }}">{{ $item->title }}</a></h3>
        <p class="meta">{{ \App\Support\SiteFormat::eventWhen($item) }}</p>
        @if ($item->venue_name || $item->commune)<p class="muted">{{ collect([$item->venue_name, $item->commune])->filter()->implode(' · ') }}</p>@endif
    </div>
</article>
