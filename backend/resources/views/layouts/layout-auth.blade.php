<!DOCTYPE html>
<html lang="en">
<x-template.head :title="$title ?? 'Título por defecto'" />

<body class="hold-transition sidebar-mini layout-fixed overflow-hidden">
    <x-shared.app-loader />
    <div id="app-wrapper" class="d-flex flex-column vh-100">
        {{-- NAVBAR --}}
        <x-template.navbar />
        {{-- SIDEBAR --}}
        <x-template.sidebar.sidebar-selector />
        {{-- Alertas SweetAlert --}}
        @include('sweetalert::alert')
        {{-- CONTENEDOR CENTRAL --}}
        <main class="content-wrapper flex-grow-1 d-flex flex-column">
            {{-- HEADER OPCIONAL --}}
            <section class="content-header"></section>
            {{-- 🔥 ÚNICA ZONA CON SCROLL --}}
            <section class="content flex-grow-1 overflow-auto">
                @inertia
            </section>
        </main>
        {{-- Modal para cambiar de contraseña --}}
        <x-shared.modal id="change-password" title="Cambiar Contraseña" :form-view="'components.form.change-password-form'" />
        {{-- Pie de página --}}
        <x-template.footer />
    </div>
    <x-template.scripts />
    {{-- Pila para scripts específicos de cada vista --}}
    @stack('stack-scripts')
    {{-- Scripts compilados con Vite --}}
    @vite(['resources/js/app.ts'])
</body>

</html>
