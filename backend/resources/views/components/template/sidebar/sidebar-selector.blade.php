@php
    $userRolePrefix = $userRolePrefix ?? '';
@endphp

@switch($userRolePrefix)
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
