<!DOCTYPE html>

<x-template.head :title="$page['props']['title'] ?? ''" :inertia />

<body class="antialiased">
    @php
        /**
         * inertiaOnly === true
         * → renderiza SOLO Inertia (sin layout Blade)
         */
        $inertiaOnly = $page['props']['inertiaOnly'] ?? false;

        /**
         * Layout Blade por defecto
         */
        $layoutComponent = $page['props']['bladeLayout'] ?? 'layouts.auth-layout';

        if (isset($page['props']['isLogged'])) {
            $layoutComponent = $page['props']['isLogged'] ? 'layouts.auth-layout' : 'layouts.guest-layout';
        }
    @endphp

    @if ($inertiaOnly)
        {{-- Render puro Inertia --}}
        @inertia
    @else
        {{-- Layout Blade envolviendo Inertia --}}
        <x-dynamic-component :component="$layoutComponent">@inertia</x-dynamic-component>
    @endif
</body>

</html>
