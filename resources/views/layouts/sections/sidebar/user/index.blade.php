@extends('layouts/sections/sidebar/index')
@section('body')
    <li class="nav-item has-treeview">
        <a href="{{ route('directory.index', ['countryCode' => strtolower(session('countryCode'))]) }}" title="Directorio"
            class="nav-link  {{ !Route::is('directory.index') ?: 'active' }}">
            <i class="nav-icon fas fa-university"></i>
            <p>Directorio</p>
        </a>
    </li>
    <li class="nav-item has-treeview">
        <a href="#" title="Salas de Juego" class="nav-link">
            <i class="nav-icon fa-solid fa-hat-wizard"></i>
            <p>Bingo</p>
        </a>
    </li>
@endsection
