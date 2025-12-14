@php
    $countryCode = strtolower(session('countryCode'));
@endphp

<x-template.sidebar :userRolePrefix="$userRolePrefix" userRole="Admin">
    {{-- Slot de navegación específica para Admin --}}
    <li class="nav-item has-treeview">
        <a href="#" class="nav-link {{ !Route::is('admin.customers', 'admin.commerces') ?: 'active' }} ">
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
        </ul>
    </li>
</x-template.sidebar>
