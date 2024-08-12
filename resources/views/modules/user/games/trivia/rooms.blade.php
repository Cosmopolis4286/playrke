@extends('layouts.app_rke')
<title>PlayRKE - El Poder del Saber</title>
@section('styles')
    <link rel="stylesheet" href="{{ asset('css/styles_bg_off.css') }}">
@endsection
<style>
    /*Fonts*/
    @import url('https://fonts.googleapis.com/css2?family=Righteous&display=swap');
    @import url('https://fonts.googleapis.com/css2?family=Alfa+Slab+One&display=swap');
    @import url('https://fonts.googleapis.com/css2?family=Anton&display=swap');
    @import url('https://fonts.googleapis.com/css2?family=Braah+One&display=swap');
    @import url('https://fonts.googleapis.com/css?family=Poppins:400,500,600,700&display=swap');


    body {
        background: url(../img/body/space.png) !important;
        font-family: 'Poppins', sans-serif !important;
    }

    #loader {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }

    .loader {
        width: 50px;
        padding: 8px;
        aspect-ratio: 1;
        border-radius: 50%;
        background: #25b09b;
        --_m:
            conic-gradient(#0000 10%, #000),
            linear-gradient(#000 0 0) content-box;
        -webkit-mask: var(--_m);
        mask: var(--_m);
        -webkit-mask-composite: source-out;
        mask-composite: subtract;
        animation: l3 1s infinite linear;
    }

    @keyframes l3 {
        to {
            transform: rotate(1turn)
        }
    }

    .txRoomGM {
        margin-top: -10px;
        display: flex;
        flex-direction: column;
        justify-content: top;
        align-items: center;
        text-transform: uppercase;
        margin-bottom: 8px;
    }

    .txRoomGM span {
        color: #DADADA;
        font-size: 2.2em;
        margin-bottom: -8.5px;
    }

    .txRoomGM small {
        font-size: 1.1em;
    }

    .contCardGm {
        position: relative;
        cursor: pointer;
        min-width: 194px;
        height: 128px;
        padding: 4px;
        box-shadow: 0px 4px 8x 4px rgba(209, 216, 230, 0.45);
        border-radius: 15px;
        background: linear-gradient(180deg, #1317fd 0%, #08002e 100%);
    }

    /*Efecto-Pulso*/
    .effectPulse {
        animation: pulse;
        animation-duration: 1.6s;
        animation-iteration-count: infinite;
    }

    .contCardGmBody {
        position: relative;
        width: 100%;
        height: 100%;
        border-radius: 11px;
        background-color: #00001d;
    }

    .contSupInfo {
        color: white;
        position: relative;
        padding-top: 2.5px;
        padding-left: 8px;
        padding-right: 8px;
        border-top-left-radius: 11px;
        border-top-right-radius: 11px;
        width: 100%;
        height: 20%;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background-color: #000000;
    }

    .contImgRoom {
        position: relative;
        width: 100%;
        height: 60%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .imgRoom {
        max-width: 100%;
        max-height: 100%;
    }

    .contTxStatusRoom {
        text-transform: uppercase;
        color: white;
        z-index: 999;
        position: absolute;
        width: 100%;
        height: 42px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }

    .bgTxStatusRm {
        position: relative;
        height: 52%;
        width: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        background-color: rgba(10, 0, 75, 0.8);
    }

    .txStatusRm {
        font-size: 122%;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .contTimeInitGm {
        background-color: #03000e;
        color: rgb(255, 255, 255);
        height: 48%;
        font-size: 15px;
        font-weight: 600;
        width: 100%;
        padding: 7px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .contInfoUnder {
        padding-top: 2.5px;
        padding-left: 8px;
        padding-right: 8px;
        width: 100%;
        height: 20%;
        background-color: #000000;
        border-bottom-left-radius: 11px;
        border-bottom-right-radius: 11px;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .contTxBoat {
        display: flex;
        align-items: center;
        text-transform: uppercase;
        font-weight: 600;
        font-size: 112%;
        color: white;
    }

    .contTxSymCoin {
        position: relative;
        width: 56%;
        height: 100%;
        color: #ffffff;
        font-weight: 500;
        font-size: 14.75px;
        border-radius: 4px;
        display: flex;
        justify-content: right;
        align-items: center;
    }

    .iconCryptoTriv {
        position: relative;
        width: 100%;
        height: 100%;
    }

    .iconCryptoTriv {
        width: 13.5px;
        height: 13.5px;
    }

    .contTxModeGm {
        display: flex;
        align-items: center;
        gap: 5px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        font-weight: 600;
        font-size: 12.5px;
        color: rgb(212, 212, 212);
    }

    /*Lista Sala Trivias*/
</style>
@section('content')
    <div class="txRoomGM">
        <span>Salas de Juego</span>
        <a href="{{ route('trivia.rooms', ['symCoin' => $wallet->Coin->name]) }}">
            <small>Cambiar a {{ $wallet->Coin->info }}</samll>
        </a>
    </div>
    <div class="container">
        <div class="row justify-content-center">
            @foreach ($trivMulti as $triv)
                <div class="contCardGm">
                    <div class="contCardGmBody">
                        <section class="contSupInfo">
                            <div class="contTxModeGm">
                                <small>Modo:</small>
                                <span>Multiple</span>
                            </div>
                            <div class="contTxSymCoin">
                                <img class="iconCryptoTriv" src="{{ asset('imgSystem/tether.svg') }}">
                                <span>{{ $triv->precio }}</span>
                            </div>
                        </section>
                        <div class="contImgRoom">
                            <div class="contTxStatusRoom">
                                <div class="bgTxStatusRm">
                                    @if ($triv->PlayersRoom[0]->user)
                                        @if ($triv->status == 3 && strtotime($triv->fecha . $triv->hora) <= $date)
                                            <a href="{{ url('trivia/multi/' . $coinRoom . '/' . $triv->id) }}">
                                                <div id="tx_info_triv{{ $triv->id }}" class="content_tx_gm triv_play">
                                                    <span class="text_gm">JUGAR</span>
                                                </div>
                                            </a>
                                        @elseif($triv->status == 2 && strtotime($triv->fecha . $triv->hora) > $date)
                                            <span class="txStatusRm">En Espera</span>
                                        @endif
                                    @else
                                        @if ($triv->status == 2 && strtotime($triv->fecha . $triv->hora) > $date)
                                            <span class="txStatusRm">Unirse al juego</span>
                                        @elseif($triv->status == 3)
                                            <span class="txStatusRm">En Juego</span>
                                        @endif
                                    @endif
                                </div>
                                <div id="bg_txid" class="contTimeInitGm"> 7D 3H 20M 5S
                                    {{-- <i class="fa-regular fa-clock"></i> --}}
                                </div>
                            </div>
                            <img class="imgRoom" alt="Imagen Promocional"
                                src="{{ asset('imgSystem/imgTrivia/' . $triv->imagen) }}">
                        </div>
                        <section class="contInfoUnder">
                            <div class="contTxBoat" title="jackpot Acumulado">
                                <small class="mr-1">jackpot:</small>
                                <span class="txSymCion"></span>
                                <span id="boatRoom{{ $triv->id }}">{{ number_format($triv->jackpot, 2) }}</span>
                            </div>
                            <div class="contTxSymCoin" title="Jugadores">
                                <i class="fa-solid fa-users-line"></i>
                                <span id="playersRoom{{ $triv->id }}" class="ml-1">{{ $triv->players }}</span>
                            </div>
                        </section>
                    </div>
                </div>
        </div>
        @endforeach
    </div>
@endsection
@section('scripts')
    <script>
        const coinRoom = '<?php echo json_encode($coinRoom); ?>';

        Echo.channel('rmTrivia.' + coinRoom)
            .listen('GetRoomsTrivia', (res) => {
                const data = res.data;
                for (let key in data) {
                    const room = data[key];
                    for (let prop in room) {
                        if (prop === 'jackpot') {
                            $('#boatRoom' + room.id).text(parseFloat(room.jackpot).toFixed(2));
                        }
                    }
                }
            });
    </script>
@endsection
