{{--
    Estrellas de calificacion (con fracciones).
    Uso: <x-stars :value="$book->rating" :count="$book->reviews_total" />
--}}
@props(['value' => 0, 'count' => null, 'size' => 'sm'])
@php
    $value = (float) $value;
    $percent = max(0, min(100, $value / 5 * 100));
    $starClass = $size === 'lg' ? 'h-5 w-5' : 'h-4 w-4';
@endphp
<span {{ $attributes->class(['inline-flex items-center gap-1.5 text-xs text-slate-500']) }} title="{{ number_format($value, 1) }} de 5">
    <span class="relative inline-flex" aria-hidden="true">
        <span class="flex text-slate-300">
            @for ($i = 0; $i < 5; $i++)<x-icon name="star" solid :class="$starClass" />@endfor
        </span>
        <span class="absolute inset-y-0 left-0 flex overflow-hidden text-amber-400" style="width: {{ $percent }}%">
            @for ($i = 0; $i < 5; $i++)<x-icon name="star" solid :class="$starClass" />@endfor
        </span>
    </span>
    @if (! is_null($count))
        <span class="sr-only">Calificación {{ number_format($value, 1) }} de 5.</span>
        <span>{{ $count > 0 ? number_format($value, 1).' ('.$count.')' : 'Sin reseñas' }}</span>
    @endif
</span>
