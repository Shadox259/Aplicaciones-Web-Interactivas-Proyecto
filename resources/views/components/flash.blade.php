{{-- Mensajes flash: success | error | warning | info. Uso en controladores: ->with('success', '...') --}}
@php
    $styles = [
        'success' => ['bg-emerald-50 text-emerald-900 ring-emerald-200', 'check-circle', 'text-emerald-500'],
        'error' => ['bg-red-50 text-red-900 ring-red-200', 'x-circle', 'text-red-500'],
        'warning' => ['bg-amber-50 text-amber-900 ring-amber-200', 'exclamation-triangle', 'text-amber-500'],
        'info' => ['bg-sky-50 text-sky-900 ring-sky-200', 'information-circle', 'text-sky-500'],
    ];
@endphp
@foreach ($styles as $type => [$classes, $icon, $iconColor])
    @if (session($type))
        <div x-data="{ show: true }" x-show="show" x-transition.opacity role="alert"
             class="mb-4 flex items-start gap-3 rounded-md px-4 py-3 text-sm ring-1 {{ $classes }}">
            <x-icon :name="$icon" solid class="mt-0.5 h-5 w-5 {{ $iconColor }}" />
            <p class="flex-1">{{ session($type) }}</p>
            <button type="button" @click="show = false" class="opacity-60 transition hover:opacity-100" aria-label="Cerrar">
                <x-icon name="x-mark" class="h-4 w-4" />
            </button>
        </div>
    @endif
@endforeach
