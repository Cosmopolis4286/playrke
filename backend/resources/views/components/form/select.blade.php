@props([
    'containerId' => false,
    'id' => null,
    'label',
    'name',
    'options' => [],
    'value' => null,
    'required' => false,
    'placeholder' => null,
    'multiple' => false,
    'select2' => false,
    'col' => 'col-sm-6',
    // Datos dinámicos para JS
    'dataSource' => null,
    'dataValue' => 'id',
    'dataLabel' => 'name',
])

<div @if ($containerId) id="container-{{ $id ?? $name }}" @endif @class(['form-group', $col])>
    <label for="{{ $id ?? $name }}">{{ $label }}{{ $required ? '*' : '' }}</label>

    <select id="{{ $id ?? $name }}" name="{{ $name }}{{ $multiple ? '[]' : '' }}" @class([
        'form-control',
        'select2' => $select2,
        'is-invalid' => $errors->has($name),
    ])
        @if ($required) required @endif @if ($multiple) multiple @endif
        @isset($dataSource) data-source="{{ $dataSource }}" @endisset
        @isset($dataValue) data-value="{{ $dataValue }}" @endisset
        @isset($dataLabel) data-label="{{ $dataLabel }}" @endisset
        @isset($placeholder) data-placeholder="{{ $placeholder }}" @endisset>

        {{-- Placeholder para Select2 / nativo --}}
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif

        {{-- Opciones estáticas --}}
        @foreach ($options as $key => $labelOption)
            <option value="{{ $key }}" @selected($multiple ? is_array(old($name, $value)) && in_array($key, old($name, $value)) : old($name, $value) == $key)>{{ $labelOption }}</option>
        @endforeach
    </select>

    @error($name)
        <span class="invalid-feedback">{{ $message }}</span>
    @enderror
</div>
