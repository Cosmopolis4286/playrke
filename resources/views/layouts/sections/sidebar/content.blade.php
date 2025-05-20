@php
    $creatorTypePrefix = \App\Models\UserType::getValueById(Auth::user()->user_type, 'prefix');
@endphp

@switch($creatorTypePrefix)
    @case('user')
        @includeIf('layouts.sections.sidebar.user.index')
    @break

    @case('admin')
        @includeIf('layouts.sections.sidebar.admin.index')
    @break

    @case('commerce')
    @case('subsidiary')
        @includeIf('layouts.sections.sidebar.commerce.index')
    @break

    @case('seller')
        @includeIf('layouts.sections.sidebar.seller.index')
    @break

    @case('customer')
        @includeIf('layouts.sections.sidebar.customer.index')
    @break

    @case('treasurer')
        @includeIf('layouts.sections.sidebar.treasurer.index')
    @break

    @default
        {{-- Código a ejecutar si $creatorTypePrefix no coincide con ningún caso --}}
@endswitch
