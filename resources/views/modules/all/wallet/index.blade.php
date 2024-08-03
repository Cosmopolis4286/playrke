@extends('layouts.app_rke')
@section('title', 'Monedero')
@section('styles')
    <link rel="stylesheet" href="{{ asset('css/styles-rke.css') }}">
@endsection
@section('content')
    <div class="col-12">
        <div class="container">
            <style>
                .container-options-wallet {
                    position: relative;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                }

                .title-container-options-wallet {
                    font-size: 16.5px;
                }

                .container-options-wallet>span {
                    font-size: 13px;
                }
            </style>
            <div class="row">
                <div class="col-sm-6 col-lg-3">
                    <div class="info-box justify-content-between">
                        <button class="btn btn-dark container-options-wallet info-box-icon elevation-1" title="Retirar Saldo">
                            <i class="fas fa-hand-holding-usd"></i>
                            <span>Retirar</span>
                        </button>
                        <button class="btn btn-danger container-options-wallet info-box-icon elevation-1"
                            title="Recargar Saldo">
                            <i class="fas fa-donate"></i>
                            <span>Depósito</span>
                        </button>
                        <button class="btn btn-secondary container-options-wallet info-box-icon elevation-1"
                            title="Transferir Saldo">
                            <i class="fas fa-exchange-alt"></i>
                            <span>Transferir</span>
                        </button>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-3">
                    <div class="info-box" title="Saldo Disponible">
                        <button class="btn btn-success container-options-wallet info-box-icon elevation-1">
                            <i class="fas fa-wallet"></i>
                            <i class="fa-solid fa-clock-rotate-left"
                                style="color:rgb(226, 226, 226); position:absolute; top:7%; right:8.5%; font-size:11.5px"></i>
                        </button>
                        <div class="info-box-content">
                            <div class="title-container-options-wallet">Saldo Disponible</div>
                            <span class="info-box-number">
                                {{ $wallet->Coin->symbol }}{{ number_format($wallet->balance, 2) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-3">
                    <div class="info-box" title="Saldo Congelado">
                        <button class="btn btn-primary container-options-wallet info-box-icon elevation-1"
                            title="Saldo Disponible">
                            <i class="fas fa-lock"></i>
                        </button>
                        <div class="info-box-content">
                            <div class="title-container-options-wallet">Saldo Congelado</div>
                            <span class="info-box-number">
                                {{ $wallet->Coin->symbol }}{{ number_format($wallet->balance, 2) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @include('components.wallet.index', ''=>)
    </div>

@endsection
@section('scripts')
    <script src="{{ asset('js/wallet.js') }}"></script>
@endsection
