@php
    $userTypePrefix = \App\Models\UserType::getValueById(Auth::user()->user_type, 'prefix');
@endphp

@switch($userTypePrefix)
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

    @case('provider')
        @includeIf('layouts.sections.sidebar.provider.index')
    @break

    @default
        {{-- Código a ejecutar si $userTypePrefix no coincide con ningún caso --}}
@endswitch
