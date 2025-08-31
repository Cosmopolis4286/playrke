@php
    $countryCode = strtolower(session('countryCode'));
@endphp

<x-template.sidebar :creatorTypePrefix="$creatorTypePrefix" userType="Comercializadora">
    {{-- Slot de navegación específica para Comercializadora --}}
    <li class="nav-item has-treeview">
        <a href="{{ route('directory.index', ['countryCode' => $countryCode]) }}" title="Directorio"
            class="nav-link {{ Route::is('directory.index') ? 'active' : '' }}">
            <i class="nav-icon fas fa-university"></i>
            <p>Directorio</p>
        </a>
    </li>

    <li class="nav-item has-treeview">
        <a href="{{ route('exchange-rate.index', ['creatorType' => 'commerce_id']) }}" title="Tasa de Cambio"
            class="nav-link {{ Route::is('exchange-rate.index') ? 'active' : '' }}">
            <i class="nav-icon fa-solid fa-circle-dollar-to-slot"></i>
            <p>Conversión</p>
        </a>
    </li>

    <li class="nav-item has-treeview">
        <a href="{{ route('commerce.sellers') }}" title="Mis Vendedores"
            class="nav-link {{ Route::is('commerce.sellers') ? 'active' : '' }}">
            <i class="nav-icon fa-solid fa-comments-dollar"></i>
            <p>Vendedores</p>
        </a>
    </li>

    <li class="nav-item has-treeview">
        <a href="{{ route('rewards.index') }}" title="Premios"
            class="nav-link {{ Route::is('rewards.index') ? 'active' : '' }}">
            <i class="nav-icon fa-solid fa-award"></i>
            <p>Premios</p>
        </a>
    </li>

    <li class="nav-item has-treeview">
        <a href="#" class="nav-link {{ Route::is('bingo.*') ? 'active' : '' }}">
            <i class="nav-icon fa-solid fa-hat-wizard"></i>
            <p>Bingo <i class="right fas fa-angle-left"></i></p>
        </a>
        <ul class="nav nav-treeview">
            <li class="nav-item">
                <a href="{{ route('bingo.handleGameRoomSummary', ['roomType' => 'private-rooms', 'creatorType' => 'commerce_id']) }}"
                    class="nav-link {{ Route::is('bingo.handleGameRoomSummary') ? 'active' : '' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Salas</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('bingo.ctnsAwarded', ['type' => 'private', 'dateFilter' => 'today']) }}"
                    class="nav-link {{ Route::is('bingo.ctnsAwarded') ? 'active' : '' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Ganadores</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('bingo.ctnsSold') }}"
                    class="nav-link {{ Route::is('bingo.ctnsSold') ? 'active' : '' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Cartones</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('bingo.reportSalesRooms') }}"
                    class="nav-link {{ Route::is('bingo.reportSalesRooms', 'bingo.reportSalesByRoom') ? 'active' : '' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Reporte de Ventas</p>
                </a>
            </li>
        </ul>
    </li>
</x-template.sidebar>
