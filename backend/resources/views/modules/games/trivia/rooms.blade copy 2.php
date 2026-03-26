@extends('layouts.app_rke')
<title>PlayRKE - El Poder del Saber</title>
@section('styles')
    <link rel="stylesheet" href="{{ asset('css/stylesTriv.css') }}">
@endsection
<style>
    body {
        background: url(../img/body/space.png) !important;
    }

    .txRoomGM {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-transform: uppercase;
    }

    .txRoomGM span {
        color: #DADADA;
        font-size: 2vmax;
        margin-bottom: -6px;
    }

    .txRoomGM small {
        font-size: 1em;
    }

    .contInfoGame {
        position: relative;
        padding-top: 3px;
        background-color: blueviolet;
        width: 200px;
        height: 120px;
        border-radius: 9px;
        font-size: 1.35em;
    }

    .contInfSup {
        position: relative;
        display: flex;
        justify-content: space-between;
        align-items: center;
        height: 15%;
        padding-left: 8px;
        padding-right: 8px;
    }

    .contTxTypeRoom {
        position: relative;
        /* width: 70%; */
        height: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .txTypeRoom {
        gap: 5px;
    }

    .contTxPriceRoom {
        background-color: sienna;
        position: relative;
        /* width: 25%; */
        height: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .txPriceRoom {
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .iconUsdtLittle {
        height: 80%;
    }

    .contImgRoom {
        position: relative;
        background-color: blue;
        width: 100%;
        height: 70%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .imgRoom {
        max-width: 100%;
        max-height: 100%;
    }

    .contTxStatusRoom {
        position: absolute;
        width: 100%;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }
    .txStatusRm{
        width: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        background-color: rgb(4, 0, 29);
    }

    .contInfUnder {
        position: relative;
        width: 100%;
        height: 15%;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-left: 8px;
        padding-right: 8px;
    }

    .contBoat {
        background-color: saddlebrown;
        position: relative;
        display: flex;
        justify-content: center;
        align-items: center;
        /* width: 80%; */
        height: 100%;
        gap: 5px;
    }

    .contPlayers {
        position: relative;
        display: flex;
        justify-content: center;
        align-items: center;
        /* width: 20%; */
        height: 100%;
    }
</style>
@section('content')
    <div class="container">
        <div class="col-12">
            <div class="txRoomGM">
                <span>Salas de Juego</span>
                <a href="{{ route('trivia.rooms', ['coinSym' => $wallet->Coin->name]) }}">
                    <small>Cambiar a {{ $wallet->Coin->info }}</samll>
                </a>
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="row">
            @foreach ($trivMulti as $trivia)
                <div id="room_{{ $trivia->id }}" class="col-sm-4 col-lg">
                    <div class="container">
                        <div class="contInfoGame">
                            <div class="contInfSup">
                                <div class="contTxTypeRoom">
                                    <div class="txTypeRoom">
                                        <span>Trivia Múltiple</span>
                                    </div>
                                </div>
                                <div class="contTxPriceRoom">
                                    <img class="iconUsdtLittle" src="{{ asset('imgSystem/tether.svg') }}">
                                    <span class="txPriceRoom">{{ $trivia->precio }}</span>
                                </div>
                            </div>
                            <div class="contImgRoom">
                                <img class="imgRoom" alt="Imagen Promocional"
                                    src="{{ asset('imgSystem/imgTrivia/' . $trivia->imagen) }}">
                                <div class="contTxStatusRoom">
                                    <a href="#" data-id="{{ $trivia->id }}" data-action="0" class="on_rm"
                                        data-precio='{{ $trivia->precio }}' data-comision='{{ $trivia->com }}'>
                                        <div id="tx_info_triv{{ $trivia->id }}" class="txStatusRm"><span
                                                class="text_gm">Unirse al juego</span></div>
                                    </a>
                                </div>
                            </div>
                            <div class="contInfUnder">
                                <div class="contBoat">
                                    <span>jackpot:</span>
                                    <div class="txBoatRoom">
                                        <span id="bot_triv{{ $trivia->id }}">
                                            {{ $trivia->jackpot }} <small>{{ $trivia->Coin->name }}</small>
                                        </span>
                                    </div>
                                </div>
                                <div class="contPlayers">12</div>
                            </div>

                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    {{-- <div class="row mt-1 content_triv">
        @foreach ($triv_multi as $trivia)
                <input type="hidden" id="room_dt{{ $trivia->id }}">
                <div class="card_game mb-4">
                    <div class="t_info_card">
                        <input id="cat" type="hidden">
                        
                            <span class="sym_div">
                                <img class="icon_usdt_triv" src="{{ asset('imgSystem/tether.svg') }}">
                                {{ $trivia->precio }}
                            </span>
                        </div>
                    </div>
                    <div class="cont_img_lt">
                        <div href="#" data-id="{{ $trivia->id }}" data-precio='{{ $trivia->precio }}'>
                            <img class="img_card_game" alt="{{ $trivia->imagen }}"
                                src="{{ asset('imgSystem/imgTrivia/' . $trivia->imagen) }}">
                        </div>
                        <div class="info_trv">
                            @if ($trivia->user == $user)
                                @if ($trivia->status == 2 && strtotime($trivia->fecha . $trivia->hora) > strtotime($date_serv))
                                    <a href="#" class="on_rm" data-action="1"
                                        data-ftemp='{{ $trivia->fecha . ' ' . $trivia->hora }}'>
                                        <div id="tx_info_triv{{ $trivia->id }}" class="content_tx_gm triv_wait">En Espera
                                        </div>
                                    </a>
                                @elseif($trivia->status == 3 && strtotime($trivia->fecha . $trivia->hora) <= strtotime($date_serv))
                                    <a href="{{ url('trivia/room/' . $trivia->id) }}">
                                        <div id="tx_info_triv{{ $trivia->id }}" class="content_tx_gm triv_play">
                                            <span class="text_gm">JUGAR</span>
                                        </div>
                                    </a>
                                @endif
                            @else
                                @if ($trivia->status == 2)
                                    <a href="#" data-id="{{ $trivia->id }}" data-action="0" class="on_rm"
                                        data-precio='{{ $trivia->precio }}' data-comision='{{ $trivia->com }}'>
                                        <div id="tx_info_triv{{ $trivia->id }}" class="content_tx_gm"><span
                                                class="text_gm">Unirse al juego</span></div>
                                    </a>
                                @elseif ($trivia->status == 3)
                                    <div id="tx_info_triv{{ $trivia->id }}" class="content_tx_gm triv_wait">
                                        En Juego
                                    </div>
                                @endif
                            @endif
                            <div class="mont_card_s">
                                @if ($trivia->fecha != '' && $trivia->hora != '')
                                    <span id="dt_triv{{ $trivia->id }}"></span>
                                @else
                                    <span style="color:D1D1D1;">EL PODER DEL SABER</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="b_info_card">
                        <input type="checkbox" id="evento-click">
                        <label for="evento-click"></label>
                        <div class="contBoatRoom">jackpot:</div>
                        <div class="cont_bot_num">
                            <span id="bot_triv{{ $trivia->id }}">
                                {{ $trivia->jackpot }}
                            </span><small>&#160USDT</small>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div> --}}
@endsection
@section('scripts')
@endsection
