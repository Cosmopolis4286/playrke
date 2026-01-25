@php
    /**
     * Layout Blade por defecto
     */
    $layoutComponent = $page['props']['bladeLayout'] ?? 'layouts.auth-layout';

    if (isset($page['props']['isLogged'])) {
        $layoutComponent = $page['props']['isLogged'] ? 'layouts.auth-layout' : 'layouts.guest-layout';
    }
@endphp

{{-- Layout Blade envolviendo Inertia --}}
<x-dynamic-component :component="$layoutComponent" :inertia="true">@inertia</x-dynamic-component>
