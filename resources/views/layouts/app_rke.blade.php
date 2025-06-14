<!DOCTYPE html>
<html lang="en">

@include('layouts.sections.head')

<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
    <div id="app" class="wrapper"> {{-- Contenedor raíz para Vue --}}

        @routes
        <input type="hidden" id="5K3_70K" value="{{ csrf_token() }}">

        <!-- Navbar -->
        @include('layouts.sections.nav')

        <!-- Sidebar -->
        @include('layouts.sections.sidebar.content')

        <!-- SweetAlert Notifications -->
        @include('sweetalert::alert')

        <!-- Main Content -->
        <div class="content-wrapper bg-transparent">
            <div class="content-header">
                <div class="container-fluid">
                    @yield('content') {{-- Aquí puedes incluir componentes Vue --}}
                </div>
            </div>
        </div>

        <!-- Modals -->
        @include('layouts.modals.report')
        @include('layouts.modals.change-password')

        <!-- Footer -->
        <footer class="main-footer foo_gradnt"
            style="font-size:12.3px; display: flex; justify-content:center; align-items:center ">
            <span>PlayRKE © {{ date('Y') }}</span>
            <span class="float-center d-none d-sm-inline-block">, todos los derechos reservados. V 1.3.7</span>
        </footer>

        <!-- Scripts -->
        @vite('resources/js/app.js') {{-- Aquí cargas el bundle Vue con Vite --}}

        @include('layouts.sections.scripts') {{-- Si tienes otros scripts que no quieres migrar aún --}}
    </div>
</body>

</html>
