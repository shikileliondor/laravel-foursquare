@if ($items->hasPages())
    <nav class="pager" aria-label="Pages">
        @if ($items->previousPageUrl())<a class="btn btn-ghost" href="{{ $items->previousPageUrl() }}">← Précédent</a>@endif
        <span class="muted">Page {{ $items->currentPage() }} sur {{ $items->lastPage() }}</span>
        @if ($items->nextPageUrl())<a class="btn btn-ghost" href="{{ $items->nextPageUrl() }}">Suivant →</a>@endif
    </nav>
@endif
