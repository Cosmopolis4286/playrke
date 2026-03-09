<x-template.sidebar :userRolePrefix="$userRolePrefix" userRole="Jugador">
    {{-- Slot de navegación específica para Usuario --}}
    <li class="nav-item has-treeview">
        <a href="{{ route('directory.index') }}" title="Directorio"
            class="nav-link {{ Route::is('directory.index') ? 'active' : '' }}">
            <i class="nav-icon fas fa-university"></i>
            <p>Directorio</p>
        </a>
    </li>
    <li class="nav-item has-treeview">
        <a href="{{ route('wallet.index') }}" title="Billetera"
            class="nav-link {{ Route::is('wallet.index') ? 'active' : '' }}">
            <i class="nav-icon fas fa-wallet"></i>
            <p>Billetera</p>
        </a>
    </li>
    <li class="nav-item has-treeview">
        <a href="{{ route('bingo-game-rooms.view-game-rooms') }}" title="Bingo"
            class="nav-link {{ Route::is('bingo-game-rooms.view-game-rooms') ? 'active' : '' }}">
            <i class="nav-icon fa-solid fa-hat-wizard"></i>
            <p>Bingo</p>
        </a>
    </li>
</x-template.sidebar>
