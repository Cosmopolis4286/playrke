<!DOCTYPE html>
<html lang="en">
<x-template.head />

<body
    class="d-flex flex-column min-vh-100 hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
    <x-shared.app-loader />
    <div id="app-wrapper" class="flex-grow-1 d-none">
        <x-template.navbar />
        <x-template.sidebar.sidebar-selector />
        @include('sweetalert::alert')
        <div class="content-wrapper">
            <div class="content-header">
                <div class="container-fluid">
                    @yield('content')
                </div>
            </div>
        </div>
        <x-template.footer />
        <x-modals.change-password />
    </div>
    @vite('resources/js/app.js')
    <x-template.scripts />
</body>

</html>
