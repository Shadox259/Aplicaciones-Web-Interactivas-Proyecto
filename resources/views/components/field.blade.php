{{--
    Campo de formulario con etiqueta y error de validacion.
    Uso:
      <x-field name="email" label="Correo electrónico" type="email" required autocomplete="email" />
      <x-field name="synopsis" label="Sinopsis" type="textarea" rows="5" :value="$book->synopsis ?? ''" />
      <x-field name="genre_id" label="Género" type="select"> <option value="">...</option> </x-field>
      <x-field name="pdf" label="PDF" type="file" accept="application/pdf" help="Máximo 30 MB" />
    Lee old($name) automaticamente; pasa :value="..." para el valor inicial (edicion).
--}}
@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'help' => null, 'required' => false])
@php
    $id = $attributes->get('id', 'f-'.str_replace(['[', ']', '.'], '-', $name));
    $hasError = $errors->has($name);
    $inputClass = ($type === 'file' ? 'file-input' : 'input').($hasError ? ' input-error' : '');
    $current = $type === 'password' || $type === 'file' ? null : old($name, $value);
@endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="label">{{ $label }}@if ($required)<span class="text-red-500"> *</span>@endif</label>
    @endif

    @if ($type === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}" @required($required) {{ $attributes->except(['class', 'id'])->merge(['rows' => 4, 'class' => $inputClass]) }}>{{ $current }}</textarea>
    @elseif ($type === 'select')
        <select id="{{ $id }}" name="{{ $name }}" @required($required) {{ $attributes->except(['class', 'id'])->merge(['class' => $inputClass]) }}>{{ $slot }}</select>
    @else
        <input id="{{ $id }}" type="{{ $type }}" name="{{ $name }}"
               @if (! is_null($current)) value="{{ $current }}" @endif
               @required($required) {{ $attributes->except(['class', 'id'])->merge(['class' => $inputClass]) }}>
    @endif

    @if ($help && ! $hasError)
        <p class="help">{{ $help }}</p>
    @endif
    @error($name)
        <p class="error-text">{{ $message }}</p>
    @enderror
</div>
