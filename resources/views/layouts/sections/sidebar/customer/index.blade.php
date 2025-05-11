{{-- Cliente --}}
@extends('layouts/sections/sidebar/index')
@section('user-type', 'Cliente')
@section('body')
    <li class="nav-item has-treeview">
        <a href="{{ route('exchange-rate.index', ['creatorType' => 'customer']) }}" title="Tasa de Cambio"
            class="nav-link  {{ !Route::is('exchange-rate.index') ?: 'active' }}">
            <i class="nav-icon fa-solid fa-circle-dollar-to-slot"></i>
            <p>Conversión</p>
        </a>
    </li>
    <li class="nav-item has-treeview">
        <a href="{{ route('iframe.index') }}" title="Iframes" class="nav-link  {{ !Route::is('iframe.index') ?: 'active' }}">
            <i class="nav-icon fa-regular fa-file-code"></i>
            <p>Iframes</p>
        </a>
    </li>
    <li class="nav-item has-treeview">
        <a href="#" class="nav-link {{ !Route::is('bingo.all-rooms') ?: 'active' }} ">
            <i class="nav-icon fa-solid fa-hat-wizard"></i>
            <p>Bingo
                <i class="right fas fa-angle-left"></i>
            </p>
        </a>
        <ul class="nav nav-treeview">
            <li class="nav-item">
                <a href="{{ route('bingo.all-rooms', ['roomType' => 'private-rooms', 'creatorType' => 'customer']) }}"
                    title="Salas Bingo" class="nav-link {{ !Route::is('bingo.all-rooms') ?: 'active' }}">
                    <i class="fa-solid fa-caret-right nav-icon"></i>
                    <p>Salas</p>
                </a>
            </li>
        </ul>
    </li>
@endsection
{{-- Comercializadora --}}
