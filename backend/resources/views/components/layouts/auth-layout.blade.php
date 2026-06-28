@props([
    'title' => 'Tablero',
    'contentHeader' => null,
    'loadDataTables' => true,
    'loadSelect2' => false,
    'inertia' => false,
])

<!DOCTYPE html>
<html lang="en">
<x-template.head :title="$title" :inertia="$inertia" />

<body class="hold-transition sidebar-mini layout-fixed overflow-hidden">
    {{-- Loader visible mientras la app se carga --}}
    <x-shared.app-loader />

    {{-- Contenedor principal, oculto hasta que la app esté lista --}}
    <div id="app-wrapper" class="wrapper d-flex flex-column vh-100">

        {{-- Barra de navegación --}}
        <x-template.navbar />

        {{-- Ticker de ganancias en vivo (montado por Vue) --}}
        <div id="platform-win-ticker"></div>

        {{-- Sidebar dinámico --}}
        <x-template.sidebar-dynamic />

        {{-- Alertas SweetAlert --}}
        @include('sweetalert::alert')

        {{-- Notificaciones globales --}}
        @include('includes.notifications')

        {{-- Contenido principal --}}
        <main class="content-wrapper flex-grow-1 d-flex flex-column">

            {{-- Contenido de la página --}}
            <section id="content-scrollable" @class([
                'content flex-grow-1 overflow-auto',
            ])>

                {{-- Header del contenido --}}
                @isset($contentHeader)
                    <section class="content-header">
                        {{ $contentHeader }}
                    </section>
                @endisset

                <div @class(['mt-2' => !$contentHeader])>
                    {{ $slot }}
                </div>
            </section>

        </main>

        {{-- Modal para cambiar de contraseña --}}
        <x-shared.modal id="change-password" title="Cambiar Contraseña" btnConfirm="btn-change-password"
            :form-view="'components.form.change-password-form'" />

        {{-- Pie de página --}}
        <x-template.footer />
    </div>

    {{-- Overlay de logout --}}
    <div id="logout-overlay" aria-hidden="true" style="display:none;position:fixed;inset:0;z-index:99999;align-items:center;justify-content:center;flex-direction:column;gap:1.25rem;background:rgba(5,8,20,.82);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);">
        <svg width="52" height="52" viewBox="0 0 50 50" style="animation:logout-spin 1s linear infinite;">
            <circle cx="25" cy="25" r="20" fill="none" stroke="rgba(255,255,255,.1)" stroke-width="3"/>
            <circle cx="25" cy="25" r="20" fill="none" stroke="#6366f1" stroke-width="3" stroke-linecap="round" stroke-dasharray="80 126"/>
        </svg>
        <p style="margin:0;font-size:.875rem;font-weight:500;color:rgba(255,255,255,.55);letter-spacing:.04em;">Cerrando sesión…</p>
    </div>
    <style>@keyframes logout-spin{to{transform:rotate(360deg)}}</style>

    {{-- Scripts adicionales y dependencias --}}
    <x-template.scripts :loadDataTables="$loadDataTables" :loadSelect2="$loadSelect2" />

    {{-- Pila para scripts específicos de cada vista --}}
    @stack('stack-scripts')

    @vite(['resources/js/app.ts'])
</body>

</html>
