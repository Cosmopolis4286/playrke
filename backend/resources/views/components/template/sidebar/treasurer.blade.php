@php
    $countryCode = strtolower(session('countryCode'));
@endphp

<x-template.sidebar :userRolePrefix="$userRolePrefix" userRole="Tesorero">
    {{-- Slot de navegación específica para Tesorero --}}
    <li class="nav-item has-treeview">
        <a href="{{ route('directory.index', ['countryCode' => strtolower(session('countryCode'))]) }}" title="Directorio"
            class="nav-link  {{ !Route::is('directory.index') ?: 'active' }}">
            <i class="nav-icon fas fa-university"></i>
            <p>Directorio</p>
        </a>
    </li>
    <li class="nav-item has-treeview">
        <a href="{{ route('exchange-rate.index', ['creatorType' => 'commerce_id']) }}" title="Tasa de Cambio"
            class="nav-link  {{ !Route::is('exchange-rate.index') ?: 'active' }}">
            <i class="nav-icon fa-solid fa-circle-dollar-to-slot"></i>
            <p>Conversión</p>
        </a>
    </li>
    <li class="nav-item has-treeview">
        <a href="{{ route('treasurer.sellers') }}" title="Mis Vendedores"
            class="nav-link {{ !Route::is('treasurer.sellers') ?: 'active' }}">
            <i class="nav-icon fa-solid fa-comments-dollar"></i>
            <p>Vendedores</p>
        </a>
    </li>

    <li class="nav-item has-treeview">
        <a href="#" class="nav-link {{ Route::is('bingo.*') ? 'active' : '' }}">
            <i class="nav-icon fa-solid fa-hat-wizard"></i>
            <p>Bingo
                <i class="right fas fa-angle-left"></i>
            </p>
        </a>
        <ul class="nav nav-treeview">
            <li class="nav-item">
                <a href="{{ route('bingo.game-rooms-summary') }}" title="Salas Bingo"
                    class="nav-link {{ !Route::is('bingo.game-rooms-summary') ?: 'active' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Salas</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('bingo.winners', ['type' => 'private', 'dateFilter' => 'today']) }}" title="Ganadores"
                    class="nav-link {{ !Route::is('bingo.winners') ?: 'active' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Ganadores</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('bingo.cartons-sold') }}" title="Cartones Vendidos"
                    class="nav-link {{ !Route::is('bingo.cartons-sold') ?: 'active' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Cartones</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('bingo.sales-report') }}" title="Reporte de Ventas"
                    class="nav-link {{ !Route::is('bingo.sales-report', 'bingo.reportSalesByRoom') ?: 'active' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Reporte de Ventas</p>
                </a>
            </li>
        </ul>
    </li>
</x-template.sidebar>
