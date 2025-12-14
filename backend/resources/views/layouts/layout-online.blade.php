<!DOCTYPE html>
<html lang="en">
<x-template.head :title="$title ?? 'Título por defecto'" />

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
                <div id="app-inertia" class="container-fluid">
                    @inertia
                </div>
            </div>
        </main>
        {{-- Modal para cambiar de contraseña --}}
        <x-shared.modal id="change-password" title="Cambiar Contraseña" :form-view="'components.form.change-password-form'" />
        {{-- Pie de página --}}
        <x-template.footer />
    </div>
    {{-- Scripts adicionales y dependencias --}}
    <x-template.scripts />
    {{-- Pila para scripts específicos de cada vista --}}
    @stack('stack-scripts')
    {{-- Scripts compilados con Vite --}}
    @vite(['resources/js/app.ts'])
</body>

</html>
