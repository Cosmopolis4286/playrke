@php
    $creatorTypePrefix = $creatorTypePrefix ?? '';
@endphp

@switch($creatorTypePrefix)
    @case('admin')
        <x-template.sidebar.admin />
    @break

    @case('commerce_id')
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

    @case('user')
        <x-template.sidebar.user />
    @break

    @default
        {{-- Puedes mostrar un mensaje o no renderizar nada --}}
@endSwitch
