<x-template.sidebar :userRolePrefix="$userRolePrefix" userRole="Vendedor">
    {{-- Slot de navegación específica para Vendedor --}}
    <li class="nav-item has-treeview">
        <a href="{{ route('directory.index') }}" title="Directorio"
            class="nav-link {{ Route::is('directory.index') ? 'active' : '' }}">
            <i class="nav-icon fas fa-university"></i>
            <p>Directorio</p>
        </a>
    </li>

    <li class="nav-item has-treeview">
        <a href="#" class="nav-link {{ !Route::is('bingo-card.*') ?: 'active' }} ">
            <i class="nav-icon fa-solid fa-hat-wizard"></i>
            <p>Bingo
                <i class="right fas fa-angle-left"></i>
            </p>
        </a>
        <ul class="nav nav-treeview">
            <li class="nav-item">
                <a href="{{ route('bingo-card.view-generate-card') }}"
                    class="nav-link {{ !Route::is('bingo-card.view-generate-card') ?: 'active' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Generar Cartón</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('bingo.cartons-sold') }}"
                    class="nav-link {{ !Route::is('bingo.cartons-sold') ?: 'active' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Cartones</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('bingo.winners', ['type' => 'private', 'dateFilter' => 'today']) }}"
                    class="nav-link {{ !Route::is('bingo.winners') ?: 'active' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Ganadores</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('bingo.sales-report') }}"
                    class="nav-link {{ !Route::is('bingo.sales-report', 'bingo.reportSalesByRoom') ?: 'active' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Reporte de Ventas</p>
                </a>
            </li>
        </ul>
    </li>
</x-template.sidebar>
