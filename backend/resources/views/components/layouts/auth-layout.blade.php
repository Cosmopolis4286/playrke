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
    <div id="app-wrapper" class="d-flex flex-column vh-100">

        {{-- Barra de navegación --}}
        <x-template.navbar />

        {{-- Sidebar selector --}}
        <x-template.sidebar.sidebar-selector />

        {{-- Alertas SweetAlert --}}
        @include('sweetalert::alert')

        {{-- Notificaciones globales --}}
        @include('includes.notifications')
        @include('includes.notifications-debug')

        {{-- Contenido principal --}}
        <main class="content-wrapper flex-grow-1 d-flex flex-column">

            {{-- Header del contenido --}}
            @isset($contentHeader)
                <section class="content-header">
                    {{ $contentHeader }}
                </section>
            @endisset

            {{-- Contenido de la página --}}
            <section @class([
                'content flex-grow-1 overflow-auto',
                'mt-2' => !$contentHeader,
            ])>
                {{ $slot }}
            </section>

        </main>

        {{-- Modal para cambiar de contraseña --}}
        <x-shared.modal id="change-password" title="Cambiar Contraseña" btnConfirm="btn-change-password"
            :form-view="'components.form.change-password-form'" />

        {{-- Pie de página --}}
        <x-template.footer />
    </div>

    {{-- Scripts adicionales y dependencias --}}
    <x-template.scripts :loadDataTables="$loadDataTables" :loadSelect2="$loadSelect2" />

    {{-- Pila para scripts específicos de cada vista --}}
    @stack('stack-scripts')

    @vite(['resources/js/app.ts'])
</body>

</html>
