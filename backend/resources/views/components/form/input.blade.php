@props([
    'container' => false,
    'id' => null,
    'label' => null,
    'name',
    'type' => 'text',
    'value' => null,
    'required' => false,
    'readonly' => false,
    'minlength' => null,
    'maxlength' => null,
    'placeholder' => null,
    'col' => 'col-sm-6',
    'class' => '',
    'inputGroup' => false
])

<div @if ($container) id="container-{{ $id ?? $name }}" @endif
     @class(['form-group', $col, $class])>

    @if ($label)
        <label for="{{ $id ?? $name }}">
            {{ $label }}{{ $required ? '*' : '' }}
        </label>
    @endif

    @if ($inputGroup)
        <div class="input-group">
            <input
                type="{{ $type }}"
                id="{{ $id ?? $name }}"
                name="{{ $name }}"
                value="{{ old($name, $value) }}"
                @class(['form-control', 'is-invalid' => $errors->has($name)])
                @if ($readonly) readonly @endif
                @if ($required) required @endif
                @isset($placeholder) placeholder="{{ $placeholder }}" @endisset
                @isset($minlength) minlength="{{ $minlength }}" @endisset
                @isset($maxlength) maxlength="{{ $maxlength }}" @endisset
            >

            {{-- Slot para botón / icono --}}
            <div class="input-group-append">
                {{ $slot }}
            </div>
        </div>
    @else
        <input
            type="{{ $type }}"
            id="{{ $id ?? $name }}"
            name="{{ $name }}"
            value="{{ old($name, $value) }}"
            @class(['form-control', 'is-invalid' => $errors->has($name)])
            @if ($readonly) readonly @endif
            @if ($required) required @endif
            @isset($placeholder) placeholder="{{ $placeholder }}" @endisset
            @isset($minlength) minlength="{{ $minlength }}" @endisset
            @isset($maxlength) maxlength="{{ $maxlength }}" @endisset
        >
    @endif

    @error($name)
        <span class="invalid-feedback">{{ $message }}</span>
    @enderror
</div>

