@props(['title' => ''])

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="icon" href="{{ asset('img/fav.ico') }}" sizes="192x192" />
    {{-- Token CSRF para peticiones AJAX --}}
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    {{-- FontAwesome para iconos --}}
    <link rel="stylesheet" href="{{ asset('template/plugins/fontawesome/css/all.min.css') }}" />
    {{-- Scrollbars personalizados --}}
    <link rel="stylesheet" href="{{ asset('template/plugins/overlayScrollbars/css/OverlayScrollbars.min.css') }}" />
    {{-- AdminLTE estilo principal --}}
    <link rel="stylesheet" href="{{ asset('template/dist/css/adminlte.css') }}" />
    {{-- DataTables estilos para tablas avanzadas --}}
    <link rel="stylesheet" href="{{ asset('css/DataTables/dataTables.bootstrap4.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/DataTables/responsive.bootstrap4.css') }}" />
    {{-- Select2 para selects enriquecidos --}}
    <link rel="stylesheet" href="{{ asset('css/select2.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/select2-bootstrap4.css') }}" />
    {{-- Animaciones CSS --}}
    <link rel="stylesheet" href="{{ asset('css/animate.min.css') }}" />
    {{-- Estilos compilados con Vite --}}
    @vite(['resources/css/shared/styles.css', 'resources/css/shared/loader.css'])
    {{-- Estilos específicos por vista --}}
    {{ $slot }}
    <title>PlayRKE - {{ $title }}</title>
</head>
