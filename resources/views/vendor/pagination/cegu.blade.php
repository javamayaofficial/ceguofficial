@if ($paginator->hasPages())
    <nav class="row" style="gap:6px;margin-top:14px;align-items:center">
        @if ($paginator->onFirstPage())
            <span class="btn ghost sm" style="opacity:.5">←</span>
        @else
            <a class="btn ghost sm" href="{{ $paginator->previousPageUrl() }}" rel="prev">←</a>
        @endif

        <span class="muted" style="font-size:.85rem">
            Hal. {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}
            ({{ number_format($paginator->total()) }} item)
        </span>

        @if ($paginator->hasMorePages())
            <a class="btn ghost sm" href="{{ $paginator->nextPageUrl() }}" rel="next">→</a>
        @else
            <span class="btn ghost sm" style="opacity:.5">→</span>
        @endif
    </nav>
@endif
