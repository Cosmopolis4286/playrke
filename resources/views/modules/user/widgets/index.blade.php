@extends ('layouts.app_rke')
@section('title', $title)
@section('content')
    {{-- Balance --}}
    <div class="col-12 col-sm-6 col-md-6 col-lg-3">
        <div class="info-box bg-rke">
            <a class="info-box-icon bg-success" href=" {{ route('wallet.index') }}" title="Saldo Disponible">
                <i class=" fas fa-wallet"></i>
            </a>
            <div class="info-box-content">
                <span class="info-box-number">
                    <a href="{{ route('wallet.index') }}" style="color: #00237A;">Saldo
                        Disponible</a></span>
                <div id="saldos" class="saldos">
                    {{ number_format(Auth::user()->country ? $wallet->balance : $wallet->usdt, 2) }}
                    {{ optional($wallet->Coin)->name }}
                </div>
            </div>
        </div>
    </div>
    {{-- Balance --}}
    {{-- Last T --}}
    <div class="col-12 col-sm-6 col-md-6 col-lg-3">
        <div class="info-box mb-3 bg-rke">
            <span class="info-box-icon bg-gray-dark" style="color:#62C2CD;">
                <i class="fa-solid fa-medal"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-number">Nivel Global</span>
                <span class="info-box-text">Bronce</span>
            </div>
        </div>
    </div>
    {{-- Last T --}}
    {{-- Game Room --}}
    {{-- <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                <div class="info-box mb-3 bg-rke">
                    <a class="info-box-icon bg-navy bg-rke" href="{{ route('trivia.rooms', ['symCoin' => $wallet->Coin->name]) }}">
                        <i class="fa-solid fa-piggy-bank"></i>
                    </a>
                    <div class="info-box-content">
                        <a style="color: #00237A;" href="{{ route('trivia.rooms', ['symCoin' => $wallet->Coin->name]) }}">
                            <span class="info-box-number">jackpot en Juego</span>
                            <span class="info-box-text" style="color: #000 !important;">
                                @if ($wallet->country > 0)
                                    {{ $wallet->name }} {{ number_format($balance_inGBs, 2) }}
                                @else
                                    {{ number_format($balance_inGUSDT, 2) }} USDT
                                @endif
                            </span>
                        </a>
                    </div>
                </div>
            </div> --}}
    {{-- Game Room --}}
    @if (Auth::user()->country == 234)
        {{-- TC --}}
        <div class="col-12 col-sm-6 col-md-6 col-lg-3">
            <div class="info-box mb-3 bg-rke">
                <span class="info-box-icon bg-navy">
                    <i class="fa-solid fa-dollar-sign"></i> </span>
                <div class="info-box-content">
                    <span class="info-box-number">Tasa de Cambio</span>
                    <span class="info-box-text ts_ve">
                        {{-- Bs/$ {{ $CommerceExchangeRate->CommerceExchangeRate_RKE }} --}}
                    </span>
                    <input type="hidden" id="bcv" value="">
                    <input type="hidden" id="f_ts" value="">
                </div>
            </div>
        </div>
        {{-- TC --}}
    @else
        {{-- News --}}
        <div class="col-12 col-sm-6 col-md-6 col-lg-3">
            <div class="info-box mb-3 bg-rke" title="Próximamente">
                <a class="info-box-icon bg-navy" href="{{ route('info.terminos') }}">
                    <i class="fas fa-newspaper"></i>
                </a>
                <div class="info-box-content">
                    <a href="{{ route('info.acerca') }}" style="color: #001752;">
                        <span class="info-box-number">Más información</span>
                        <span class="info-box-text">PlayRKE - V1.2</span></a>
                </div>
            </div>
        </div>
        {{-- News --}}
    @endif
    {{-- @include('/modules/user/info/home') --}}
@endsection
