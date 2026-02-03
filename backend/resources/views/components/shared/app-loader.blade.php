@props([
    'text' => 'Cargando...',
    'logo' => asset('img/rke.png'),
    'id' => 'app-loader',
])

<div id="{{ $id }}"
    {{ $attributes->merge([
        'class' => 'app-loader',
        'role' => 'alert',
        'aria-busy' => 'true',
        'aria-live' => 'polite',
    ]) }}>
    <div class="loader-content">
        <div class="spinner-border text-primary loader-spinner" role="status" aria-hidden="true">
            <img src="{{ $logo }}" alt="Logo" class="loader-spin" />
        </div>

        <div class="loader-text">
            {{ $text }}
        </div>
    </div>
</div>
