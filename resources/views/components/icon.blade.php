@props(['name', 'solid' => false])
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
     @if ($solid) fill="currentColor" @else fill="none" stroke="currentColor" stroke-width="1.5" @endif
     {{ $attributes->class(['h-5 w-5 shrink-0']) }} aria-hidden="true">{!! \App\Support\Icons::get($name, (bool) $solid) !!}</svg>
