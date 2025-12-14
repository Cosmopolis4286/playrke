@php
    $countryCode = strtolower(session('countryCode'));
@endphp

<x-template.sidebar :userRolePrefix="$userRolePrefix" userRole="Cliente">
    {{-- Slot de navegación específica para Cliente --}}
    <li class="nav-item has-treeview">
        <a href="{{ route('exchange-rate.index', ['creatorType' => 'customer']) }}" title="Tasa de Cambio"
            class="nav-link  {{ !Route::is('exchange-rate.index') ?: 'active' }}">
            <i class="nav-icon fa-solid fa-circle-dollar-to-slot"></i>
            <p>Conversión</p>
        </a>
    </li>
    <li class="nav-item has-treeview">
        <a href="{{ route('iframe.index') }}" title="Iframes"
            class="nav-link  {{ !Route::is('iframe.index') ?: 'active' }}">
            <i class="nav-icon fa-regular fa-file-code"></i>
            <p>Iframes</p>
        </a>
    </li>
    <li class="nav-item has-treeview">
        <a href="#" class="nav-link {{ !Route::is('bingo.handleGameRoomSummary') ?: 'active' }} ">
            <i class="nav-icon fa-solid fa-hat-wizard"></i>
            <p>Bingo
                <i class="right fas fa-angle-left"></i>
            </p>
        </a>
        <ul class="nav nav-treeview">
            <li class="nav-item">
                <a href="{{ route('bingo.handleGameRoomSummary', ['roomType' => 'private-rooms', 'creatorType' => 'customer']) }}"
                    title="Salas Bingo" class="nav-link {{ !Route::is('bingo.handleGameRoomSummary') ?: 'active' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Salas</p>
                </a>
            </li>
        </ul>
    </li>
</x-template.sidebar>
