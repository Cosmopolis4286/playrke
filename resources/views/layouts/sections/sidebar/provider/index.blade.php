{{-- Comercializadora --}}
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
        <a href="{{ route('provider.sellers') }}" title="Mis Vendedores"
            class="nav-link {{ !Route::is('provider.sellers') }}">
            <i class="nav-icon fa-solid fa-comments-dollar"></i>
            <p>Vendedores</p>
        </a>
    </li>
    <li class="nav-item has-treeview">
        <a href="#"
            class="nav-link {{ !Route::is('bingo-off.rooms', 'provider.getBingoOffWinnersByOtherCommerces', 'provider.ctnsSold', 'provider.reportSalesRooms') ?: 'active' }} ">
            <i class="nav-icon fa-solid fa-hat-wizard"></i>
            <p>Bingo
                <i class="right fas fa-angle-left"></i>
            </p>
        </a>
        <ul class="nav nav-treeview">
            @if (Auth::user()->user_type == 3)
                <li class="nav-item">
                    <a href="{{ route('bingo-off.rooms', ['coin' => encrypt(1)]) }}" title="Salas Bingo" class="nav-link">
                        <i class="fa-solid fa-caret-right nav-icon"></i>
                        <p>Salas</p>
                    </a>
                </li>
            @endif
            <li class="nav-item">
                <a href="{{ route('provider.getBingoOffWinnersByCommerce', ['dateFilter' => 'today']) }}" title="Ganadores"
                    class="nav-link">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Ganadores</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('provider.ctnsSold', ['coin' => encrypt($wallet->Coin->id)]) }}" title="Cartones Vendidos"
                    class="nav-link">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Cartones</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('provider.reportSalesRooms', ['dateFilter' => 'today', 'coin' => encrypt($wallet->Coin->id)]) }}"
                    title="Reporte de Ventas" class="nav-link"> <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Reporte de Ventas</p>
                </a>
            </li>
        </ul>
    </li>
@endsection
{{-- Comercializadora --}}
