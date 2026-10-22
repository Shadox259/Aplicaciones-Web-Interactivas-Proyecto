{{-- Paginacion en espanol con estilo Wonderland. Se usa automaticamente con $libros->links() --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Paginación" class="flex flex-col items-center justify-between gap-4 sm:flex-row">
        <p class="text-sm text-slate-600">
            Mostrando <span class="font-semibold text-slate-900">{{ $paginator->firstItem() }}</span>–<span class="font-semibold text-slate-900">{{ $paginator->lastItem() }}</span>
            de <span class="font-semibold text-slate-900">{{ $paginator->total() }}</span>
        </p>

        <ul class="inline-flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <li><span class="inline-flex h-9 items-center rounded-lg px-3 text-sm text-slate-300">‹ Anterior</span></li>
            @else
                <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex h-9 items-center rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 transition hover:border-brand-300 hover:bg-brand-50">‹ Anterior</a></li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="inline-flex h-9 w-9 items-center justify-center text-sm text-slate-400">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li><span aria-current="page" class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-brand-700 text-sm font-semibold text-white shadow-sm">{{ $page }}</span></li>
                        @else
                            <li><a href="{{ $url }}" aria-label="Ir a la página {{ $page }}" class="hidden h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-sm font-medium text-slate-700 transition hover:border-brand-300 hover:bg-brand-50 sm:inline-flex">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li><a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex h-9 items-center rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 transition hover:border-brand-300 hover:bg-brand-50">Siguiente ›</a></li>
            @else
                <li><span class="inline-flex h-9 items-center rounded-lg px-3 text-sm text-slate-300">Siguiente ›</span></li>
            @endif
        </ul>
    </nav>
@endif
