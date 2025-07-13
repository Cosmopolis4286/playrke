@extends('layouts.layout-online')
<title>Juegos USDT - PlayRKE</title>
@section('fnd-content')
    <div class="content-wrapper" style="background-image: url(../../img-noise-480x400.png);">
    @endsection
    @section('content')
        <div class="row">
            <div class="col-md-12">
                <div class="bg-rke">
                    <div class="card-header" id="headingOne" style="color:#DADADA;">
                        <h1 class="text-center my-n1 mb-n3">SALAS DE JUEGO</h1>
                    </div>
                    <div class="card-body-tl">
                        <div class="list_game mb-n4">
                            <div class="row">
                                @foreach ($trivias as $trivia)
                                    @if (count($users_tri) == 0)
                                        @php
                                            $bandera = '0';
                                        @endphp
                                    @else
                                        @foreach ($users_tri as $user)
                                            @if ($trivia->id == $user->room)
                                                @php
                                                    $bandera = '1';
                                                @endphp
                                            @break;
                                        @else
                                            @php
                                                $bandera = '0';
                                            @endphp
                                        @endif
                                    @endforeach
                                @endif
                                <div class="col-sm-6 col-md-4">
                                    <div class="card-game mb-4">
                                        <div class="t_info_card">
                                            <div class="na_card">{{ $trivia->nombre }}</div>
                                        </div>
                                        @php
                                            $fech = date('Y-m-d');
                                            $hora = date('h:i A', time());
                                            $nuevahora = date('h:i A', strtotime($trivia->hora));
                                            $horac = date('h:i A', strtotime($trivia->hora_c));
                                        @endphp
                                        @if ($bandera == '1')
                                            <div class="cont_img_lt">
                                                <img class="img-card-game"
                                                    src="{{ asset('imgSystem/imgTrivia/' . $trivia->imagen) }}"
                                                    alt="{{ $trivia->imagen }}">
                                                @if ($trivia->fecha == '')
                                                    <a href="#" data-id="{{ $trivia->id }}"
                                                        data-idusers="{{ $user->usuario_id }}" class="previo"
                                                        data-fecha='{{ $trivia->fecha }}'
                                                        data-horac='{{ $trivia->hora_c }}'
                                                        data-hora='{{ $trivia->hora }}'>
                                                        <div class="tq-gm tri-p">En Espera</div>
                                                    </a>
                                                @elseif($fech == $trivia->fecha && $nuevahora >= $hora && $hora <= $horac)
                                                    <a href="{{ url('trivia/' . $trivia->id) }}"
                                                        class="t-jp">JUGAR</a>
                                                @elseif($hora > $horac)
                                                    <div class="tq-gm tri-f">Finalizada</div>
                                                @else
                                                    <a href="#" data-id="{{ $trivia->id }}"
                                                        data-idusers="{{ $user->usuario_id }}" class="previo"
                                                        data-fecha='{{ $trivia->fecha }}'
                                                        data-horac='{{ $trivia->hora_c }}'
                                                        data-hora='{{ $trivia->hora }}'>
                                                        <div class="tq-gm tri-p">En Espera</div>
                                                    </a>
                                                @endif
                                            </div>
                                        @else
                                            <div class="cont_img_lt">
                                                <img class="img-card-game"
                                                    src="{{ asset('imgSystem/imgTrivia/' . $trivia->imagen) }}"
                                                    alt="{{ $trivia->imagen }}">
                                                @if ($trivia->fecha == '' || $trivia->fecha == null)
                                                    <a href="#" data-id="{{ $trivia->id }}" data-idusers="0"
                                                        class="previo" data-precio='{{ $trivia->precio }}'>
                                                        <div class="tq-gm">Unirse</div>
                                                    </a>
                                                @else
                                                    @if ($fech > $trivia->fecha)
                                                        <div class="tq-gm tri-f">Cerrada</div>
                                                    @elseif($trivia->fecha <= $fech)
                                                        @if ($hora > $nuevahora)
                                                            <div class="tq-gm tri-f">Cerrada</div>
                                                        @else
                                                            <a href="#" data-id="{{ $trivia->id }}"
                                                                data-idusers="0" class="previo"
                                                                data-precio='{{ $trivia->precio }}'>
                                                                <div class="tq-gm">Unirse</div>
                                                            </a>
                                                        @endif
                                                    @endif
                                                @endif
                                            </div>
                                        @endif
                                        <div class="p_card" title="Boleto Entrada">
                                            <div class="mont_card"></div>
                                            <div class="mont_card_s">
                                                <span class="usdt">{{ number_format($trivia->precio, 2) }}
                                                    USDT</span>
                                            </div>
                                        </div>
                                        <div class="b_info_card">
                                            <div class="bot_card" title="prize-pool Acumulado">
                                                <input type="checkbox" id="evento-click">
                                                <label for="evento-click"></label>
                                                <i class="icon-min fa-solid fa-coins mr-rke1"></i>
                                                <span class="b_usdt">{{ number_format($trivia->prize_pool_r, 2) }}
                                                    <img class="mb-1" src="{{ asset('imgSystem/tether.svg') }}"
                                                        style="width:13.5px;"></span>
                                            </div>
                                            <div class="mj_card" title="Jugadores">
                                                <span class="m_pj">{{ $trivia->pj }}/{{ $trivia->min_pj }}</span>
                                                <i class="icon-min fa-solid fa-circle-user"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <style>
        body {
            background: url(../../img-noise-480x400.png);
        }
    </style>
@endsection