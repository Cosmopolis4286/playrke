@props([
    'text' => 'Cargando',
    'logo' => asset('img/rke.png'),
    'id'   => 'app-loader',
])

<div id="{{ $id }}"
    {{ $attributes->merge([
        'class'      => 'app-loader',
        'role'       => 'alert',
        'aria-busy'  => 'true',
        'aria-live'  => 'polite',
        'aria-label' => 'Cargando aplicación',
    ]) }}>

    {{-- partículas flotantes --}}
    <div class="loader-particles" aria-hidden="true">
        @for ($i = 0; $i < 14; $i++)
            @php
                $size  = rand(2, 5);
                $left  = rand(5, 95);
                $delay = $i * 0.45;
                $dur   = rand(6, 14);
            @endphp
            <span class="loader-particle" style="
                width: {{ $size }}px;
                height: {{ $size }}px;
                left: {{ $left }}%;
                bottom: -10px;
                animation-duration: {{ $dur }}s;
                animation-delay: {{ $delay }}s;
                opacity: 0;
            "></span>
        @endfor
    </div>

    {{-- contenido central --}}
    <div class="loader-content">

        {{-- spinner doble anillo + logo --}}
        <div class="loader-spinner" role="status" aria-hidden="true">
            <img src="{{ $logo }}" alt="" class="loader-spin" />
        </div>

        {{-- barra de progreso indeterminada --}}
        <div class="loader-bar" aria-hidden="true">
            <div class="loader-bar__fill"></div>
        </div>

        {{-- texto --}}
        <p class="loader-text">{{ $text }}</p>
    </div>
</div>
