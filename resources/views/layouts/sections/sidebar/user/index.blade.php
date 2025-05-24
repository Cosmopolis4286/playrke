@extends('layouts/sections/sidebar/index')
@section('user-type', Auth::user()->username)
@section('body')
    <li class="nav-item has-treeview">
        <a href="{{ route('directory.index', ['countryCode' => strtolower(session('countryCode'))]) }}" title="Directorio"
            class="nav-link {{ !Route::is('directory.index') ?: 'active' }}">
            <i class="nav-icon fas fa-university"></i>
            <p>Directorio</p>
        </a>
    </li>
    <li class="nav-item has-treeview">
        <a href="{{ route('wallet.index') }}" title="Billetera" class="nav-link {{ !Route::is('wallet.index') ?: 'active' }}">
            <i class="nav-icon fa-solid fa-wallet"></i>
            <p>Billetera</p>
        </a>
    </li>
    <li class="nav-item has-treeview">
        <a href="#" title="Bingo" class="nav-link">
            <i class="nav-icon fa-solid fa-hat-wizard"></i>
            <p>Bingo</p>
        </a>
    </li>
    <li class="nav-item has-treeview">
        <a href="#" title="Trivia" class="nav-link">
            <i class="nav-icon fa-solid fa-puzzle-piece"></i>
            <p>Trivia</p>
        </a>
    </li>
@endsection
