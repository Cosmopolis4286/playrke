{{-- Proveedor --}}
@extends('layouts/sections/sidebar/index')
@section('user-type', 'Proveedor')
@section('body')
    <li class="nav-item has-treeview">
        <a href="{{ route('directory.index', ['countryCode' => strtolower(session('countryCode'))]) }}" title="Directorio"
            class="nav-link  {{ !Route::is('directory.index') ?: 'active' }}">
            <i class="nav-icon fas fa-university"></i>
            <p>Directorio</p>
        </a>
    </li>
    <li class="nav-item has-treeview">
        <a href="{{ route('provider.exchangeRate') }}" title="Tasa de Cambio"
            class="nav-link  {{ !Route::is('provider.exchangeRate') ?: 'active' }}">
            <i class="nav-icon fa-solid fa-circle-dollar-to-slot"></i>
            <p>Conversión</p>
        </a>
    </li>

    <li class="nav-item has-treeview">
    <li class="nav-item has-treeview">
        <a href="{{ route('all-rooms', ['coin' => session('coin'), 'user' => encrypt(Auth::id())]) }}"
            title="Salas de Juego" class="nav-link {{ !Route::is('all-rooms') ?: 'active' }}">
            <i class="nav-icon fa-solid fa-hat-wizard"></i>
            <p>Bingo</p>
        </a>
    </li>

    </li>
@endsection
