<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="icon" href="{{ asset('img/fav.ico') }}" sizes="192x192" />

    <!-- CSRF Token para seguridad en peticiones AJAX -->
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <!-- Stylesheets agrupados y ordenados -->
    <link rel="stylesheet" href="{{ asset('template/plugins/fontawesome/css/all.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('template/plugins/overlayScrollbars/css/OverlayScrollbars.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('template/dist/css/adminlte.css') }}" />

    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('css/DataTables/dataTables.bootstrap4.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/DataTables/responsive.bootstrap4.css') }}" />

    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('css/select2.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/select2-bootstrap4.css') }}" />

    <!-- Animations -->
    <link rel="stylesheet" href="{{ asset('css/animate.min.css') }}" />

    <!-- Vite managed CSS -->
    @vite('resources/css/rke/styles.css')

    <!-- Estilos específicos de cada vista -->
    @yield('styles')

    <title>PlayRKE - @yield('title')</title>
</head>
