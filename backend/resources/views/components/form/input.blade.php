@props([
    'containerId' => false,
    'id' => null,
    'label',
    'name',
    'type' => 'text',
    'value' => null,
    'required' => false,
    'readonly' => false,
    'minlength' => null,
    'maxlength' => null,
    'col' => 'col-sm-6',
])

<div @if ($containerId) id="container-{{ $id ?? $name }}" @endif class="form-group {{ $col }}">
    <label for="{{ $id ?? $name }}">{{ $label }}{{ $required ? '*' : '' }}</label>

    <input type="{{ $type }}" id="{{ $id ?? $name }}" name="{{ $name }}" value="{{ old($name, $value) }}"
        @class(['form-control', 'is-invalid' => $errors->has($name)]) @if ($required) required @endif @if($readonly ?? false) readonly @endif
        @isset($minlength) minlength="{{ $minlength }}" @endisset
        @isset($maxlength) maxlength="{{ $maxlength }}" @endisset>

    @error($name)
        <span class="invalid-feedback">{{ $message }}</span>
    @enderror
</div>
