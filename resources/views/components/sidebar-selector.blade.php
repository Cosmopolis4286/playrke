@php
    $creatorTypePrefix = $creatorTypePrefix ?? '';
@endphp

@switch($creatorTypePrefix)
    @case('admin')
        <x-sidebar.admin />
    @break

    @case('commerce')
    @case('subsidiary')
        <x-sidebar.commerce />
    @break

    @case('customer')
        <x-sidebar.customer />
    @break

    @case('seller')
        <x-sidebar.seller />
    @break

    @case('treasurer')
        <x-sidebar.treasurer />
    @break

    @case('user')
        <x-sidebar.user />
    @break

    @default
        {{-- Puedes mostrar un mensaje o no renderizar nada --}}
@endSwitch
