{{-- Comercializadora --}}
@extends('layouts/sections/sidebar/index')
@section('user-type', 'Comercializadora')
@section('body')
    <li class="nav-item has-treeview">
        <a href="{{ route('directory.index', ['countryCode' => strtolower(session('countryCode'))]) }}" title="Directorio"
            class="nav-link  {{ !Route::is('directory.index') ?: 'active' }}">
            <i class="nav-icon fas fa-university"></i>
            <p>Directorio</p>
        </a>
    </li>
    <li class="nav-item has-treeview">
        <a href="{{ route('exchange-rate.index', ['creatorType' => 'commerce']) }}" title="Tasa de Cambio"
            class="nav-link  {{ !Route::is('exchange-rate.index') ?: 'active' }}">
            <i class="nav-icon fa-solid fa-circle-dollar-to-slot"></i>
            <p>Conversión</p>
        </a>
    </li>
    <li class="nav-item has-treeview">
        <a href="{{ route('commerce.sellers') }}" title="Mis Vendedores"
            class="nav-link {{ !Route::is('commerce.sellers') ?: 'active' }}">
            <i class="nav-icon fa-solid fa-comments-dollar"></i>
            <p>Vendedores</p>
        </a>
    </li>

    <li class="nav-item has-treeview">
        <a href="#"
            class="nav-link {{ !Route::is('polla.all-commerce-rooms', 'polla.winners', 'polla.roomsSalesReport') ?: 'active' }} ">
            <i class="nav-icon fa-solid fa-splotch"></i>
            <p>Polla RKE
                <i class="right fas fa-angle-left"></i>
            </p>
        </a>
        <ul class="nav nav-treeview">
            <li class="nav-item">
                <a href="{{ route('polla.all-commerce-rooms') }}" title="Polla RKE"
                    class="nav-link {{ !Route::is('polla.all-commerce-rooms') ?: 'active' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Salas</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('polla.winners') }}" title="Ganadores"
                    class="nav-link {{ !Route::is('commerce.winnersByCommerce') ?: 'active' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Ganadores</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('lottery.results', ['game' => 'polla-rke']) }}" title="Resultados"
                    class="nav-link {{ !Route::is('lottery.results') ?: 'active' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Resultados</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('polla.roomsSalesReport') }}" title="Reporte de Ventas"
                    class="nav-link {{ !Route::is('polla.reportSalesRooms') ?: 'active' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Reporte de Ventas</p>
                </a>
            </li>
        </ul>
    </li>
    <li class="nav-item has-treeview">
        <a href="#"
            class="nav-link {{ !Route::is('bingo.all-rooms', 'commerce.getBingoWinnersByCommerce', 'commerce.getBingoWinnersByOtherCommerces', 'commerce.ctnsSold', 'commerce.reportSalesRooms') ?: 'active' }} ">
            <i class="nav-icon fa-solid fa-hat-wizard"></i>
            <p>Bingo
                <i class="right fas fa-angle-left"></i>
            </p>
        </a>
        <ul class="nav nav-treeview">
            <li class="nav-item">
                <a href="{{ route('bingo.all-rooms', ['roomType' => 'private-rooms', 'creatorType' => 'commerce']) }}"
                    title="Salas Bingo" class="nav-link {{ !Route::is('bingo.all-rooms') ?: 'active' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Salas</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('commerce.getBingoWinnersByCommerce') }}" title="Ganadores"
                    class="nav-link {{ !Route::is('commerce.getBingoWinnersByCommerce') ?: 'active' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Ganadores</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('commerce.ctnsSold') }}" title="Cartones Vendidos"
                    class="nav-link {{ !Route::is('commerce.ctnsSold') ?: 'active' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Cartones</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('commerce.reportSalesRooms') }}" title="Reporte de Ventas"
                    class="nav-link {{ !Route::is('commerce.reportSalesRooms') ?: 'active' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Reporte de Ventas</p>
                </a>
            </li>
        </ul>
    </li>
@endsection
{{-- Comercializadora --}}
