@props([
    'containerId' => false,
    'id' => null,
    'label' => null,
    'name',
    'type' => 'text',
    'value' => null,
    'required' => false,
    'readonly' => false,
    'minlength' => null,
    'maxlength' => null,
    'col' => 'col-sm-6',
    'class' => '',
])

<div @if ($containerId) id="container-{{ $id ?? $name }}" @endif
    class="form-group {{ $col }} {{ $class }}">
    @if ($label)
        <label for="{{ $id ?? $name }}">{{ $label }}{{ $required ? '*' : '' }}</label>
    @endif
    <input type="{{ $type }}" id="{{ $id ?? $name }}" name="{{ $name }}" value="{{ old($name, $value) }}"
        @class(['form-control', 'is-invalid' => $errors->has($name)]) @if ($readonly) readonly @endif
        @if ($required) required @endif
        @isset($minlength) minlength="{{ $minlength }}" @endisset
        @isset($maxlength) maxlength="{{ $maxlength }}" @endisset>

    @error($name)
        <span class="invalid-feedback">{{ $message }}</span>
    @enderror
</div>
