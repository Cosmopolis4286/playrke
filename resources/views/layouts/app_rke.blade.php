<!DOCTYPE html>
<html lang="en">

<head>
    @include('layouts.sections.head')
    @vite('resources/js/app.js') <!-- Si usas Vite para tus activos -->
</head>

<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed" x-data="{ open: false }">
    <div class="wrapper" 
    x-data x-init="Echo.channel('chat').listen('')"
    >
        <!-- CSRF Token -->
        <input type="hidden" id="token" value="{{ csrf_token() }}">

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
                    <div class="row">
                        @yield('content')
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals -->
        @include('layouts.modals.report')

        <!-- Footer -->
        <footer class="main-footer foo_gradnt"
            style="font-size:12.3px; display: flex; justify-content:center; align-items:center ">
            <span>PlayRKE © {{ date('Y') }}</span>
            <span class="float-center d-none d-sm-inline-block">, todos los derechos reservados. V 1.3.0</span>
        </footer>

        <!-- Scripts -->
        @include('layouts.sections.scripts')
    </div>
</body>

</html>
