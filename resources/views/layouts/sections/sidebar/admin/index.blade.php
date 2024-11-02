{{-- ADMIN --}}
@extends('layouts/sections/sidebar/index')
@section('user-type', 'Admin')
@section('body')
    <li class="nav-item has-treeview">
        <a href="#" class="nav-link {{ !Route::is('admin.customers', 'admin.commerce') ?: 'active' }} ">
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
        </ul>
    </li>
    {{-- <li class="nav-item has-treeview">
        <a href="#" class="nav-link {{ !Route::is('trivia.allRooms') ?: 'active' }}">
            <i class="nav-icon fa-solid fa-puzzle-piece"></i>
            <p>Trivia
                <i class="right fas fa-angle-left"></i>
            </p>
        </a>
        <ul class="nav nav-treeview">
            <li class="nav-item ">
                <a href="{{ route('trivia.allRooms') }}" class="nav-link {{ !Route::is('trivia.allRooms') ?: 'active' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Salas de Juego</p>
                </a>
            </li>
            <li class="nav-item ">
                <a href="{{ route('trivia.cod_tri') }}" class="nav-link {{ !Route::is('trivia.CodTri') ?: 'active' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Códigos</p>
                </a>
            </li>
        </ul>
    </li>
    <li class="nav-item has-treeview">
        <a href="#" class="nav-link {{ !Route::is('bingo.allRooms', 'bingo.winners') ?: 'active' }}">
            <i class="nav-icon fa-solid fa-hat-wizard"></i>
            <p>Bingo
                <i class="right fas fa-angle-left"></i>
            </p>
        </a>
        <ul class="nav nav-treeview">
            <li class="nav-item ">
                <a href="{{ route('bingo.allRooms') }}" class="nav-link {{ !Route::is('bingo.allRooms') ?: 'active' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Salas de Juego</p>
                </a>
            </li>
            <li class="nav-item ">
                <a href="{{ route('bingo.winners') }}" class="nav-link {{ !Route::is('bingo.winners') ?: 'active' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Ganadores</p>
                </a>
            </li>
        </ul>
    </li>
    <li class="nav-item has-treeview">
        <a href="#" class="nav-link {{ !Route::is('historial.index') ?: 'active' }}">
            <i class="nav-icon fas fa-star"></i>
            <p>Historial
                <i class="right fas fa-angle-left"></i>
            </p>
        </a>
        <ul class="nav nav-treeview">
            <li class="nav-item">
                <a href="{{ route('historial.index') }}" title="Historial de Partidas"
                    class="nav-link {{ !Route::is('historial.index') ?: 'active' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Jugadas</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('historial.codes') }}" class="nav-link {{ !Route::is('historial.codes') ?: 'active' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Códigos</p>
                </a>
            </li>
        </ul>
        <hr>
    </li>
    <li class="nav-item has-treeview ">
        <a class="nav-link {{ !Route::is('recarga.transk_bs', 'recarga.transk_clp', 'recarga.transk_BPay') ?: 'active' }}">
            <i class="nav-icon fas fa-donate"></i>
            <p>Depósitos
                <i class="right fas fa-angle-left"></i>
            </p>
        </a>
        <ul class="nav nav-treeview">
            <li class="nav-item ">
                <a href="{{ route('recarga.transk_bs') }}" title="En espera"
                    class="nav-link {{ !Route::is('recarga.transk_bs') ?: 'active' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Bolivares Bs.</p>
                </a>
            </li>
            <li class="nav-item ">
                <a href="{{ route('recarga.transk_bs') }}" title="En espera"
                    class="nav-link {{ !Route::is('recarga.transk_clp') ?: 'active' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Pesos Chilenos</p>
                </a>
            </li>
            <li class="nav-item ">
                <a href="{{ route('recarga.transk_BPay') }}"
                    class="nav-link {{ !Route::is('recarga.transk_BPay') ?: 'active' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Binance Pay</p>
                </a>
            </li>
            <li class="nav-item ">
                <a href="{{ route('recarga.cryptos') }}" class="nav-link {{ !Route::is('recarga.cryptos') ?: 'active' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Red Blockchain</p>
                </a>
            </li>
        </ul>
    </li>
    <li class="nav-item has-treeview">
        <a class="nav-link {{ !Route::is('retiro.cryptos', 'retiro.transk_BPay', 'retiro.transk_bs') ?: 'active' }}">
            <i class="nav-icon fas fa-hand-holding-usd"></i>
            <p>Retiros
                <i class="right fas fa-angle-left"></i>
            </p>
        </a>
        <ul class="nav nav-treeview">
            <li class="nav-item ">
                <a href="{{ route('retiro.transk_bs') }}" title="En espera"
                    class="nav-link {{ !Route::is('retiro.transk_bs') ?: 'active' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Bolivares Bs.</p>
                </a>
            </li>
            <li class="nav-item ">
                <a href="{{ route('retiro.transk_bs') }}" title="En espera"
                    class="nav-link {{ !Route::is('retiro.pendientes') ?: 'active' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Pesos Chilenos</p>
                </a>
            </li>
            <li class="nav-item ">
                <a href="{{ route('retiro.transk_BPay') }}"
                    class="nav-link {{ !Route::is('retiro.transk_BPay') ?: 'active' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Binance Pay</p>
                </a>
            </li>
            <li class="nav-item ">
                <a href="{{ route('retiro.cryptos') }}" class="nav-link {{ !Route::is('retiro.cryptos') ?: 'active' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Red Blockchain</p>
                </a>
            </li>
        </ul>
    </li>
    <li class="nav-item has-treeview ">
        <a class="nav-link {{ !Route::is('') ?: 'active' }}">
            <i class="nav-icon fas fa-exchange-alt"></i>
            <p>Transferencias</p>
        </a>
        <hr>
    </li>
    <li class="nav-item">
        <a href="#" class="nav-link {{ !Route::is('CommerceExchangeRate.index', 'limits.index') ?: 'active' }}">
            <i class="nav-icon fa-solid fa-screwdriver-wrench"></i>
            <p>Configuración<i class="right fas fa-angle-left"></i></p>
        </a>
        <ul class="nav nav-treeview">
            <li class="nav-item">
                <a href="{{ route('limits.index') }}" class="nav-link {{ !Route::is('limits.index') ?: 'active' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Límites</p>
                </a>
            </li>
        </ul>
    </li>
    <li class="nav-item has-treeview ">
        <a href="#" class="nav-link  {{ !Route::is('report.index') ?: 'active' }}">
            <i class="nav-icon fa-solid fa-triangle-exclamation"></i>
            <p>Reportes</p>
        </a>
        <hr>
    </li> --}}
@endsection
{{-- ADMIN --}}
