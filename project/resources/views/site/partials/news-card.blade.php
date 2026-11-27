<article class="card card-link">
    @if ($item->cover)
        <img class="cover" src="{{ $item->cover->url() }}" alt="" loading="lazy">
    @endif
    <div class="card-body">
        @include('site.partials.badge', ['item' => $item])
        <h3><a href="{{ route('site.news.show', $item->slug) }}">{{ $item->title }}</a></h3>
        @if ($item->excerpt)<p class="muted clamp">{{ $item->excerpt }}</p>@endif
        @if ($item->published_at)<p class="meta">{{ \App\Support\SiteFormat::date($item->published_at) }}</p>@endif
    </div>
</article>
