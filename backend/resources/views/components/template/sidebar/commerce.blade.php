<x-template.sidebar :userRolePrefix="$userRolePrefix" userRole="Comercializadora">
    {{-- Slot de navegación específica para Comercializadora --}}
    <li class="nav-item has-treeview">
        <a href="{{ route('directory.index') }}" title="Directorio"
            class="nav-link {{ Route::is('directory.index') ? 'active' : '' }}">
            <i class="nav-icon fas fa-university"></i>
            <p>Directorio</p>
        </a>
    </li>

    <li class="nav-item has-treeview">
        <a href="{{ route('exchange-rate.index') }}" title="Tasa de Cambio"
            class="nav-link {{ Route::is('exchange-rate.index') ? 'active' : '' }}">
            <i class="nav-icon fa-solid fa-circle-dollar-to-slot"></i>
            <p>Conversión</p>
        </a>
    </li>

    <li class="nav-item has-treeview">
        <a href="{{ route('manager-seller.index') }}" title="Mis Vendedores"
            class="nav-link {{ Route::is('manager-seller.index') ? 'active' : '' }}">
            <i class="nav-icon fa-solid fa-comments-dollar"></i>
            <p>Vendedores</p>
        </a>
    </li>

    <li class="nav-item has-treeview">
        <a href="{{ route('prizes.index') }}" title="Premios"
            class="nav-link {{ Route::is('prizes.index') ? 'active' : '' }}">
            <i class="nav-icon fa-solid fa-award"></i>
            <p>Premios</p>
        </a>
    </li>

    <li class="nav-item has-treeview">
        <a href="#" class="nav-link {{ Route::is('bingo-rooms.*') ? 'active' : '' }}">
            <i class="nav-icon fa-solid fa-hat-wizard"></i>
            <p>Bingo <i class="right fas fa-angle-left"></i></p>
        </a>
        <ul class="nav nav-treeview">
            <li class="nav-item">
                <a href="{{ route('bingo-rooms.index') }}"
                    class="nav-link {{ Route::is('bingo-rooms.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Salas</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('bingo.winners', ['type' => 'private', 'dateFilter' => 'today']) }}"
                    class="nav-link {{ Route::is('bingo.winners') ? 'active' : '' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Ganadores</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('bingo.cartons-sold') }}"
                    class="nav-link {{ Route::is('bingo.cartons-sold') ? 'active' : '' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Cartones</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('bingo.sales-report') }}"
                    class="nav-link {{ Route::is('bingo.sales-report', 'bingo.reportSalesByRoom') ? 'active' : '' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Reporte de Ventas</p>
                </a>
            </li>
        </ul>
    </li>
</x-template.sidebar>
