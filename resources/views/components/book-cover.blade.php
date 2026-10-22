{{--
    Portada de libro (proporcion 2:3).
    Muestra la imagen subida o, si no hay, una portada generada en tonos sobrios segun el libro.
    Uso: <x-book-cover :book="$book" class="w-40" />
--}}
@props(['book'])
@php
    $palettes = [
        'from-brand-700 to-brand-950',
        'from-slate-600 to-slate-900',
        'from-emerald-800 to-emerald-950',
        'from-rose-900 to-rose-950',
        'from-stone-600 to-stone-900',
        'from-cyan-800 to-cyan-950',
        'from-indigo-800 to-indigo-950',
        'from-zinc-600 to-zinc-900',
    ];
    $palette = $palettes[($book->id ?? crc32($book->title)) % count($palettes)];
    $coverUrl = $book->cover_url;
@endphp
<div {{ $attributes->class(['@container relative aspect-[2/3] overflow-hidden rounded-sm bg-slate-200 shadow-md ring-1 ring-black/10']) }}>
    @if ($coverUrl)
        <img src="{{ $coverUrl }}" alt="Portada de {{ $book->title }}" loading="lazy" class="h-full w-full object-cover">
    @else
        <div class="relative flex h-full w-full flex-col justify-between bg-linear-to-b {{ $palette }} p-[10%] text-white">
            <div class="absolute inset-[5%] border border-white/25"></div>
            <x-icon name="book-open" class="absolute inset-0 m-auto hidden h-1/2 w-1/2 text-white/60 @max-[80px]:block" />
            <div class="relative mt-[12%] px-[2%] text-center @max-[80px]:hidden">
                <p class="line-clamp-5 font-serif font-semibold uppercase leading-tight tracking-wide [overflow-wrap:anywhere]"
                   style="font-size: clamp(0.42rem, 8.5cqw, 1.5rem)">{{ $book->title }}</p>
                <div class="mx-auto mt-[8%] h-px w-1/3 bg-white/50"></div>
            </div>
            <p class="relative mb-[4%] line-clamp-2 text-center font-serif italic text-white/75 [overflow-wrap:anywhere] @max-[80px]:hidden"
               style="font-size: clamp(0.4rem, 5.5cqw, 0.95rem)">{{ $book->author?->name }}</p>
        </div>
    @endif
    <div class="pointer-events-none absolute inset-y-0 left-0 w-[4%] bg-linear-to-r from-black/30 to-transparent"></div>
</div>
