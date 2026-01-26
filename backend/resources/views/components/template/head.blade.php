@props(['title' => '', 'inertia' => false, 'page' => []])

<head>
    {{-- Meta básicos --}}
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    {{-- CSRF --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Favicon --}}
    <link rel="icon" type="image/x-icon" href="{{ asset('img/fav.ico') }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('img/fav.ico') }}">

    {{-- Título --}}
    <title>PlayRKE{{ $title ? ' - ' . $title : '' }}</title>

    {{-- Vendor CSS --}}
    <link rel="stylesheet" href="{{ asset('template/plugins/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('template/plugins/overlayScrollbars/css/OverlayScrollbars.min.css') }}">
    <link rel="stylesheet" href="{{ asset('template/dist/css/adminlte.css') }}">

    {{-- Plugins --}}
    <link rel="stylesheet" href="{{ asset('css/DataTables/dataTables.bootstrap4.css') }}">
    <link rel="stylesheet" href="{{ asset('css/DataTables/responsive.bootstrap4.css') }}">
    <link rel="stylesheet" href="{{ asset('css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/select2-bootstrap4.css') }}">
    <link rel="stylesheet" href="{{ asset('css/animate.min.css') }}">

    {{-- Estilos compilados (Vite) --}}
    @vite(['resources/css/global/styles.css', 'resources/css/global/loader.css'])

    {{-- Estilos específicos por vista --}}
    {{ $slot }}

    @if ($inertia)
        @vite('resources/js/inertia.ts')
        @inertiaHead()
    @endif
</head>
