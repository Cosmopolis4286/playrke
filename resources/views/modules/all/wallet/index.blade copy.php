@extends('layouts.app_rke')
@section('title', 'Wallet')
@section('content')
    <div class="row">
        {{-- Deposito-Recargar --}}
        <div class="col-sm-6 col-lg-3">
            <div class="info-box bg-rke" style="z-index:99;">
                <a class="cont_items_mone info-box-icon bg-danger elevation-1"
                    @if ($pais == 234) href="#" data-target="#mrt_bs" data-toggle="modal"
                    @elseif($pais == 43) href="#" data-target="#mrt_clp" data-toggle="modal"
                    @else 
                        @if ($BPay == null)
                        href="{{ route('wallet.BPay') }}"
                        @else
                        href="{{ route('Wallet.rt_BPay') }}" @endif
                    @endif
                    title="Retirar Saldo">
                    <i class="mb_n16 fas fa-hand-holding-usd"></i>
                    <span class="tx_items_mone">Retirar</span>
                </a>
                <a class="cont_items_mone info-box-icon bg-navy" title="Recargar Saldo"
                    @if ($pais == 234) href="#" data-target="#mdp_bs" data-toggle="modal"
                    @elseif($pais == 43) href="#" data-target="#mdp_clp" data-toggle="modal"
                    @else href="{{ route('Wallet.createBPay') }}" @endif>
                    <i class="mb_n16 fas fa-donate"></i>
                    <span class="tx_items_mone">Depósito</span>
                </a>
                <a class="cont_items_mone info-box-icon bg-gray elevation-1" title="Transferir Saldo"
                    @if ($pais == 234 || $pais == 43) href="{{ route('Wallet.transfer_coins') }}"
                    @else href="{{ route('Wallet.transfer_usdt') }}" @endif>
                    <i class="mb_n16 fas fa-exchange-alt"></i>
                    <span class="tx_items_mone">Transferir</span>
                </a>
            </div>
        </div>
        @if ($pais == 234 || $pais == 43)
            {{-- Saldo Disponible --}}
            <div class="col-sm-6 col-lg-3">
                <div class="info-box bg-rke" title="Saldo Disponible">
                    <a href="#" onclick="hideShowDiv(1)">
                        <div class="info-box-icon bg-success elevation-1" style="height:100%; position:relative;">
                            <i class=" fas fa-wallet"></i>
                            <div>
                                <i class="fa-solid fa-clock-rotate-left"
                                    style="color:rgb(226, 226, 226); position:absolute; top:7%; right:8.5%; font-size:11.5px"></i>
                            </div>
                        </div>
                    </a>
                    <div class="info-box-content">
                        <span style="font-size: 17px;">{{ $wallet->info }}</span>
                        <div style="display:flex;">
                            <div id="saldos" class="saldos">
                                @if ($pais == 234)
                                    <span id="s-bs" class="info-box-number">
                                        {{ $wallet->name }} {{ number_format($wallet->balance, 2) }}</span>
                                    <span id="s-usd" style="display: none;" class="info-box-number">
                                        @if ($CommerceExchangeRate->CommerceExchangeRate_RKE > 0)
                                            US$ {{ number_format($wallet->balance / $CommerceExchangeRate->CommerceExchangeRate_RKE, 2) }}
                                        @else
                                            US$ {{ number_format($wallet->balance) }}
                                        @endif
                                    </span>
                                @else
                                    <span class="info-box-number">
                                        {{ $wallet->name }}{{ number_format($wallet->balance, 2) }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        {{-- USDT --}}
        <div class="col-sm-6 col-lg-3">
            <div class="info-box bg-rke" title="USDT Disponibles">
                <a href="#" onclick="hideShowDiv(2)" style="height:100%">
                    <div class="info-box-icon elevation-1" style="position: relative; background-color:#26a17b; ">
                        <img src="{{ asset('imgSystem/tether.svg') }}" style="margin-top:1.5px; width:57px;">
                        <a href="#"
                            style="color:rgb(226, 226, 226); position:absolute; top:2%; right:8%; font-size:11.5px;">
                            <i class="fa-solid fa-clock-rotate-left mt-1"></i>
                        </a>
                    </div>
                </a>
                <div class="info-box-content">
                    <span style="font-size: 17px;">Tether</span>
                    <img class="mb-1" src="{{ asset('imgSystem/bpay.png') }}" style="width:18.5px;">
                    <div style="display:flex;">
                        <div id="saldos" class="saldos">
                            <span class="info-box-number">
                                {{ number_format($wallet->usdt, 2) }}
                                <span style="font-size:14px;">USDT</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- Saldo Congelado --}}
        <div class="col-sm-6 col-lg-3">
            <div class="info-box bg-rke" title="Saldo Congelado">
                <a href="" data-target="#msc" data-toggle="modal" class="info-box-icon bg-info elevation-1">
                    <i class="fas fa-lock"></i>
                </a>
                <div class="info-box-content">
                    <span style="font-size: 17px;">Congelado</span>
                    @if ($pais == 234)
                        <div style="display:flex;">
                            <div id="saldosCongelado" class="saldos">
                                <span id="sC-bs" class="info-box-number"> Bs
                                    {{ number_format($Bs_cold, 2) }}</span>
                                <span id="sC-usd" style="display: none;" class="info-box-number">
                                    {{ number_format($USDT_cold, 2) }}
                                    <span style="font-size:14px;">USDT</span>
                                </span>
                            </div>
                        </div>
                    @else
                        <div class="saldos">
                            <span id="sC-bs" class="info-box-number">
                                {{ number_format($USDT_cold, 2) }}
                                <span style="font-size:14px;">USDT</span>
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        {{-- @include('modules/user/Wallet/datatables/transk_crypto') --}}
        @include('modules/user/Wallet/datatables/transk_bpay')
        @if ($pais == 234)
            @include('modules/user/Wallet/datatables/transk_ves')
        @elseif($pais == 43)
            @include('modules/user/Wallet/datatables/transk_clp')
        @endif
    </div>
    <div class="modal fade" id="msc" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog  modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">PlayRKE © 2023
                    <span class="sub"></span>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body mb-n3">
                    <p><b>8.5.</b> Si el monto del depósito solicitado por el usuario no coincide con el monto
                        recibido
                        en las cuentas bancarias asociadas con PlayRKE; el saldo será procesado como saldo congelado
                        y
                        cargado a su cuenta en base al valor del monto recibido.</p>
                    <p><b>8.6.</b> Al realizar un depósito/recarga inferior al monto mínimo estipulado; esta
                        transacción
                        será procesada y cargada su Wallet como saldo congelado.</p>
                    <p><b>8.7.</b> El saldo congelado, solo podrá liberarse o estar disponible una vez se realice
                        otra
                        recarga igual o superior al monto mínimo estipulado. Las transferencias entre usuarios no
                        cuentan como una recarga o depósito.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" id="close">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script src="{{ asset('/js/Wallet.js') }}"></script>
@endsection
