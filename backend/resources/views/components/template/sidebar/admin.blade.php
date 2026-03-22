<x-template.sidebar :userRolePrefix="$userRolePrefix" userRole="Admin">
    {{-- Slot de navegación específica para Admin --}}
    <li class="nav-item has-treeview">
        <a href="#" class="nav-link {{ !Route::is('admin.*') ?: 'active' }} ">
            <i class="fa-solid fa-users-gear nav-icon"></i>
            <p>Usuarios
                <i class="right fas fa-angle-left"></i>
            </p>
        </a>
        <ul class="nav nav-treeview">
            <li class="nav-item">
                <a href="{{ route('admin.customers') }}" title="Comercios"
                    class="nav-link {{ !Route::is('admin.customers') ?: 'active' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Clientes</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.commerces') }}" title="Comercios"
                    class="nav-link {{ !Route::is('admin.commerces') ?: 'active' }}"> <i
                        class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Comercios</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.treasurers') }}" title="Tesoreros"
                    class="nav-link {{ !Route::is('admin.treasurers') ?: 'active' }}"> <i
                        class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Tesoreros</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.sellers') }}" title="Vendedores"
                    class="nav-link {{ !Route::is('admin.sellers') ?: 'active' }}"> <i
                        class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Vendedores</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.players') }}" title="Jugadores"
                    class="nav-link {{ !Route::is('admin.players') ?: 'active' }}"> <i
                        class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Jugadores</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.users') }}" title="Jugadores"
                    class="nav-link {{ !Route::is('admin.users') ?: 'active' }}"> <i
                        class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Todos</p>
                </a>
            </li>
        </ul>
    </li>
    <li class="nav-item has-treeview">
        <a href="{{ route('exchange-rate.index') }}" title="Tasa de Cambio"
            class="nav-link {{ Route::is('exchange-rate.*') ? 'active' : '' }}">
            <i class="nav-icon fa-solid fa-circle-dollar-to-slot"></i>
            <p>Conversión</p>
        </a>
    </li>
    <li class="nav-item has-treeview">
        <a href="{{ route('currency.index') }}" title="Monedas"
            class="nav-link {{ Route::is('currency.*') ? 'active' : '' }}">
            <i class="nav-icon fa-solid fa-coins"></i>
            <p>Monedas</p>
        </a>
    </li>
    <li class="nav-item has-treeview">
        <a href="#" class="nav-link {{ Route::is('trivia-rooms.*') ? 'active' : '' }}">
            <i class="nav-icon fa-solid fa-hat-wizard"></i>
            <p>Trivia <i class="right fas fa-angle-left"></i></p>
        </a>
        <ul class="nav nav-treeview">
            <li class="nav-item">
                <a href="{{ route('trivia-rooms.index') }}"
                    class="nav-link {{ Route::is('trivia-rooms.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Salas</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('trivia.winners') }}"
                    class="nav-link {{ Route::is('trivia.winners', 'trivia.winners-room') ? 'active' : '' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Ganadores</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('trivia.sales-report') }}"
                    class="nav-link {{ Route::is('trivia.sales-report', 'trivia.sales-report-room') ? 'active' : '' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Reporte de Ventas</p>
                </a>
            </li>
        </ul>
    </li>
</x-template.sidebar>
