<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    @vite('resources/js/inertia.ts')
    @inertiaHead
</head>

<body class="antialiased">
    @php
        /**
         * Si la prop `inertiaOnly === true`
         * → se renderiza SOLO Inertia (sin layout Blade)
         */
        $inertiaOnly = $page['props']['inertiaOnly'] ?? false;
        $layout = $page['props']['bladeLayout'] ?? 'layouts.layout-auth';
    @endphp

    @if ($inertiaOnly)
        {{-- Render puro Inertia --}}
        @inertia
    @else
        {{-- Layout Blade envolviendo Inertia --}}
        @include($layout)
    @endif
</body>

</html>
