@php
    $userRolePrefix = $userRolePrefix ?? '';
    
    // Verificar si debemos usar el menú dinámico basado en permisos
    // Por defecto usamos el dinámico, pero mantenemos compatibilidad
    $useDynamicMenu = true; // Cambiar a false para usar el sistema antiguo
@endphp

@if($useDynamicMenu)
    {{-- Menú dinámico basado en permisos --}}
    <x-template.sidebar.dynamic />
@else
    {{-- Sistema antiguo basado en roles (mantenido para compatibilidad) --}}
    @switch($userRolePrefix)
        @case('superadmin')
        @case('admin')
            <x-template.sidebar.admin />
        @break

        @case('commerce')
        @case('subsidiary')
            <x-template.sidebar.commerce />
        @break

        @case('customer')
            <x-template.sidebar.customer />
        @break

        @case('seller')
            <x-template.sidebar.seller />
        @break

        @case('treasurer')
            <x-template.sidebar.treasurer />
        @break

        @case('player')
            <x-template.sidebar.player />
        @break

        @default
            {{-- Puedes mostrar un mensaje o no renderizar nada --}}
    @endSwitch
@endif
