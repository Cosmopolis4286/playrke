<!DOCTYPE html>
<html lang="en">
<x-template.head :title="$title" />

<body
    class="d-flex flex-column min-vh-100 hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
    {{-- Loader visible mientras la app se carga --}}
    <x-shared.app-loader />
    {{-- Contenedor principal, oculto hasta que la app esté lista --}}
    <div id="app-wrapper" class="flex-grow-1 d-none">
        {{-- Barra de navegación --}}
        <x-template.navbar />
        {{-- Sidebar selector --}}
        <x-template.sidebar.sidebar-selector />
        {{-- Alertas SweetAlert --}}
        @include('sweetalert::alert')
        {{-- Contenido principal --}}
        <main class="content-wrapper">
            <div class="content-header">
                <div class="container-fluid">@yield('content')</div>
            </div>
        </main>
        {{-- Pie de página --}}
        <x-template.footer />
        {{-- Modal cambio de contraseña --}}
        <x-modals.change-password />
    </div> 
    {{-- Scripts adicionales y dependencias --}}
    <x-template.scripts />
    {{-- Scripts compilados con Vite --}}
    @vite(['resources/js/app.ts'])
    {{-- Pila para scripts específicos de cada vista --}}
    @stack('stack-scripts')
</body>

</html>
