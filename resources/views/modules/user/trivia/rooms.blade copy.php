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
        padding-top: 2px;
        background-color: blueviolet;
        width: 210px;
        height: 145px;
        border-radius: 9px;
        font-size: 1.35em;
    }

    .contInfSup {
        position: relative;
        display: flex;
        justify-content: space-between;
        align-items: center;
        height: 15%;
        padding-left: 6px;
        padding-right: 6px;
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

    .contInfUnder {
        background-color: seagreen;
        position: relative;
        width: 100%;
        height: 15%;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-left: 6px;
        padding-right: 6px;
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
                <a href="{{ route('trivia.rooms', ['symCoin' => $wallet->Coin->name]) }}">
                    <small>Cambiar a {{ $wallet->Coin->info }}</samll>
                </a>
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="row">
            @foreach ($trivMulti as $trivia)
                <div id="room_{{ $trivia->id }}" class="col-sm-4 col-lg-3">
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
                                <div class="contTxStatusRoom">ff
                                    {{-- @if ($trivia->user == $user)
    
                                        @endif --}}
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
        <div class="row jus_f">
            @foreach ($rooms as $key => $room)
                <div id="bg_rm{{ $room->id }}" class="ct_center col-sm-6 col-md-4 col-xl-3 mb-6 mr-4 ml-4">
                    <input type="hidden" id="bg_in{{ $room->id }}">
                    <input type="hidden" id="bg_in_sts{{ $room->id }}" value="0">
                    <a href="#" class="s_bgx @if ($room->status == 2) comp_cartn @endif"
                        data-date="{{ $room->date }}" data-time="{{ $room->time }}"
                        data-precio="{{ $room->price }}" data-idb="{{ $room->id }}"
                        data-bot="{{ number_format($room->jackpot, 2) }}" data-automc="{{ $room->val_automc }}"
                        data-type='{{ $room->type_boat }}' data-status='{{ $room->status }}'
                        data-user_automc="{{ $room->auto_mc }}" data-cantpj='{{ $room->players }}'>
                        <div class="box_rbg">
                            <div class="cont_rbg">
                                <div id="bg_tx{{ $room->id }}" class="time_bg">
                                    @if ($room->status == 2)
                                        <i class="fa-regular fa-clock"></i>
                                    @else
                                        EN JUEGO
                                    @endif
                                </div>
                                <span class="i_crt_full">Modo: Cartón Lleno</span>
                                @if ($room->ctns > 0)
                                    <div class="crtn_buy" title="Mis Cartones">
                                        +<span class="num_ct">{{ $room->ctns }}</span>
                                    </div>
                                @endif
                                @if ($room->auto_mc == 1)
                                    <div class="ic_atm" title="Tienes el Automarcador">
                                        <i class="fa-solid fa-check-to-slot"></i>
                                    </div>
                                @endif
                                <img class="img_rbg" src="{{ asset('img/bg/estelar_fd.png') }}" alt="IMG">
                                <span class="b_te">
                                    @if ($room->type_boat == 1)
                                        jackpot:
                                    @elseif($room->type_boat == 2)
                                        Premio:
                                    @endif
                                </span>
                                <span class="bot_cant">
                                    @if ($room->jackpot > 0)
                                        <div class="cont_price_bg">
                                            <img style="width:24px; margin-bottom:1px;"
                                                src="{{ asset('imgSystem/tether.svg') }}">
                                            <span id='montRoom{{ $room->id }}'>
                                                {{ number_format($room->jackpot, 2) }}
                                            </span>
                                        </div>
                                    @else
                                        <div class="cont_price_bg">
                                            <h3>ESPERANDO</h3>
                                        </div>
                                    @endif
                                </span>
                                <span class="i_crt_bs">Cartón: {{ $room->price }} USDT</span>
                                <span class="t_bg_hour" id="t_bg_hour{{ $room->id }}">
                                    @if ($room->status == 2)
                                        Comprar cartones
                                    @else
                                        No disponible
                                    @endif
                                </span>
                            </div>
                        </div>
                    </a>
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
