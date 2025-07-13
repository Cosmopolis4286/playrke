@php
    $creatorTypePrefix = $creatorTypePrefix ?? '';
@endphp

@switch($creatorTypePrefix)
    {{-- @case('user')
        <x-sidebar-user />
    @break --}}

    {{-- @case('admin')
        <x-sidebar-admin />
    @break --}}
    @case('commerce')
    @case('subsidiary')
        <x-sidebar.commerce />
    @break

    @case('seller')
        <x-sidebar-seller />
    @break

    {{-- @case('customer')
        <x-sidebar-customer />
    @break

    @case('treasurer')
        <x-sidebar-treasurer />
    @break --}}

    @default
        {{-- Puedes mostrar un mensaje o no renderizar nada --}}
@endSwitch
