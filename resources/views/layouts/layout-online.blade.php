<!DOCTYPE html>
<html lang="en">
<x-head />

<body
    class="d-flex flex-column min-vh-100 hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
    @routes
    <!-- Loader al iniciar -->
    <x-app-loader />
    <!-- Contenido oculto hasta que Vue esté montado o scripts cargados -->
    <div id="app-wrapper" class="flex-grow-1 d-none">
        {{-- Navbar --}}
        <x-navbar />
        <x-modals.change-password />
        {{-- Sidebar --}}
        <x-sidebar-selector />
        {{-- SweetAlert Notifications --}}
        @include('sweetalert::alert')
        {{-- Main Content --}}
        <div class="content-wrapper bg-transparent">
            <div class="content-header">
                <div class="container-fluid">
                    @yield('content')
                </div>
            </div>
        </div>
        <x-footer />
    </div>
    {{-- Scripts de tu app --}}
    @vite('resources/js/app.js')
    <x-scripts />
</body>

</html>
