@extends('layouts.app_rke')
<title>PlayRKE - El Poder del Saber</title>
@section('styles')
    <link rel="stylesheet" href="{{ asset('css/styles_triv.css') }}">
@endsection
<style>
    body {
        background: url(img/body/space.png) !important;
    }
</style>
@section('content')
    <div style="position: relative">
        <div id="tx_rm_triv">
            <span>SALAS DE JUEGO</span>
            <a href="{{ route('trivia.rooms_usdt') }}">
                <span>Cambiar a USDT
                    <img class="mb-1" style="width:13.25px;" src="{{ asset('imgSystem/tether.svg') }}">
                </span>
            </a>
        </div>
        <div class="mdl_triv" id="mdl_triv_multi">
            <div class="modal-header">
                <div id="tdx"></div>
                <a class="btn_close_bg_md close_rm">X</a>
            </div>
            <div class="modal-body" align="center">
                <div id="dbr"></div>
            </div>
        </div>
        @include('modules/user/trivia/modals_triv')
    </div>
    <div class="row mt-3 content_triv">
        <div class="col-sm-4 col-lg-3">
            <div class="card_game mb-4">
                <div class="t_info_card">
                    <div class="na_card"><small>Modo:&#160</small>Solitario</div>
                    <div class="mont_tri" title="Inscripción">
                        @if ($wallet->coin == 2)
                            <span class="bs b_color"></span>
                            <span class="bs" style="margin-top:2.7%; font-size: 11.7px;">Bs ??</span>
                            <span class="usd" style="display:none;color:#E5E5E5 !important;">
                                <span class="usd_sy">$&#160??</span>
                            </span>
                        @else
                            <span class="sym_div">{{ $wallet->name }} ??</span>
                        @endif
                    </div>
                </div>
                <a @if ($quser_type > 7) @if ($tri_ndv == null || $tri_ndv->start == 2) 
                        href="#" data-rm_ind="1" @elseif($tri_ndv->start == 1) 
                        href="{{ route('triv_in.room') }}" @endif
                @else href="#" @endif class="on_indv">
                    <div class="cont_img_lt">
                        <img class="img_card_game" src="{{ asset('img/triv/indv.jpg') }}" alt="">
                        <div class="info_trv">
                            <div class="tq-gm">
                                <span class="text_gms">
                                    @if ($quser_type < 8)
                                        NO DISPONIBLE
                                    @else
                                        @if ($tri_ndv == null || $tri_ndv->start == 2)
                                            JUGAR AHORA
                                        @else
                                            VOLVER AL JUEGO
                                        @endif
                                    @endif
                                </span>
                            </div>
                            <div class="mont_card_s">
                                <span style="color:D1D1D1;">EL PODER DEL SABER</span>
                            </div>
                        </div>
                    </div>
                </a>
                <div class="b_info_card">
                    <div class="bot_card" title="jackpot Acumulado">
                        <span>DEMUESTRA TU CAPACIDAD</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-4 col-lg-3">
            <div class="card_game mb-4">
                <div class="t_info_card">
                    <div class="na_card"><small>Modo:&#160</small>Versus</div>
                    <div class="mont_tri" title="Inscripción">
                        @if ($wallet->coin == 2)
                            <span class="bs b_color"></span>
                            <span class="bs" style="margin-top:2.7%; font-size: 11.7px;">Bs ??</span>
                            <span class="usd" style="display:none;color:#E5E5E5 !important;">
                                <span class="usd_sy">$&#160??</span>
                            </span>
                        @else
                            <span class="sym_div">{{ $wallet->name }} ??</span>
                        @endif
                    </div>
                </div>
                <a @if ($tri_pvp == null || $tri_pvp->start == 2) href="#" data-rm_ind="2"
                        @elseif($tri_pvp->start == 1) href="{{ route('triv_pvp.room') }}" @endif
                    class="on_indv">
                    <div class="cont_img_lt">
                        <img class="img_card_game" src="{{ asset('img/triv/vs_pvp.jpg') }}">
                        <div class="info_trv">
                            <div class="tq-gm">
                                <span class="text_gms">
                                    @if ($tri_pvp === null || $tri_pvp->start == 2)
                                        BUSCAR RIVAL
                                    @else
                                        VOLVER AL JUEGO
                                    @endif
                                </span>
                            </div>
                            <div class="mont_card_s">
                                <span style="color:D1D1D1;">EL PODER DEL SABER</span>
                            </div>
                        </div>
                    </div>
                </a>
                <div class="b_info_card">
                    <div class="bot_card" title="jackpot Acumulado">
                        <span style="font-size:13.25px; font-weight:400;color: #0058cc;">
                            HAZTE CON LA VICTORIA </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-1 content_triv">
        @foreach ($triv_multi as $trivia)
            <div id="room_{{ $trivia->id }}" class="col-sm-4 col-lg-3">
                <input type="hidden" id="room_dt{{ $trivia->id }}">
                <div class="card_game mb-4">
                    <div class="t_info_card">
                        <input id="cat" type="hidden">
                        <div class="na_card"><small>Modo:&#160</small>Múltiple</div>
                        <div class="mont_tri" title="Inscripción">
                            <span class="sym_div mr-1">{{ $wallet->name }}</span>
                            {{ $trivia->precio }}
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
                                        <div id="tx_info_triv{{ $trivia->id }}" class="tq-gm tri-p">En Espera</div>
                                    </a>
                                @elseif($trivia->status == 3 && strtotime($trivia->fecha . $trivia->hora) <= strtotime($date_serv))
                                    <a href="{{ url('trivia/room/' . $trivia->id) }}" class="t_jp">
                                        <div id="tx_info_triv{{ $trivia->id }}" class="tq-gm"><span
                                                class="text_gm">JUGAR</span></div>
                                    </a>
                                @endif
                            @else
                                @if ($trivia->status == 2)
                                    <a href="#" data-id="{{ $trivia->id }}" data-action="0" class="on_rm"
                                        data-precio='{{ $trivia->precio }}' data-comision='{{ $trivia->com }}'>
                                        <div id="tx_info_triv{{ $trivia->id }}" class="tq-gm"><span
                                                class="text_gm">Unirse al juego</span></div>
                                    </a>
                                @elseif ($trivia->status == 3)
                                    <div id="tx_info_triv{{ $trivia->id }}" class="tq-gm tri-p">En Juego</div>
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
                        <div class="bot_card" title="jackpot Acumulado">
                            <input type="checkbox" id="evento-click">
                            <label for="evento-click"></label>
                            <span style="font-size:14.5px; font-weight:500;color: #0087b1;">jackpot:&nbsp</span>
                            @if ($wallet->coin = 2)
                                <span id="b_usd">{{ number_format($trivia->jackpot, 2) }}<small
                                        style="margin-bottom:-2.7px;">Bs</small></span>
                                <span id="b_bs">
                                    {{ number_format($trivia->jackpot / $CommerceExchangeRate, 2) }}$
                                </span>
                            @else
                                <span class="sym_div">{{ $wallet->name }}
                                    {{ number_format($trivia->jackpot, 2) }}
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <script>
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 1800,
            timerProgressBar: true,
        })
    </script>
    @if (Session::has('message'))
        <script>
            if (@json(Session::has('buy'))) {
                Toast.fire({
                    icon: 'success',
                    html: '<div style="padding-top:1.6% !important; font-size:1rem;">{{ Session::get('message') }}</div>'
                })
            }
        </script>
    @endif
@endsection
@section('scripts')
    <script src="{{ asset('js/tempp.js') }}"></script>
    <script>
        var rooms = @json($triv_multi);
        $(document).ready(function() {
            if (rooms.length > 0) {
                rm_timer();
            }
        })
        var v_rtn = 0;
        let id_elems = [];
        var _second = 1000;
        var _minute = _second * 60;
        var _hour = _minute * 60;
        var _day = _hour * 24;
        var now = new Date(@json($date_serv));
        var c_timer = 0;

        function rm_timer() {
            $.ajax({
                url: '{{ asset('') }}trivia/timer/' + @json($wallet->coin),
                headers: {
                    'X-CSRF-TOKEN': $("#5K3_70K").val()
                },
                method: 'get',
                dataType: 'json',
                success: function(res) {
                    $.each(res, function(key, value) {
                        id_elems.push(value['id']);
                        $("#room_dt" + value['id']).val(value['fecha'] + " " + value['hora']);
                    });
                }
            });
        }

        function c_tmsvr() {
            $.ajax({
                url: "{{ route('trivia.time_serv') }}",
                method: 'get',
                dataType: 'json',
                success: function(data) {
                    now = new Date(data);
                }
            });
            c_timer = 1;
        }
        window.onblur = function() {
            v_rtn = 1;
        }
        window.onfocus = function() {
            if (v_rtn == 1) {
                c_timer = 0;
                c_tmsvr();
            }
        }

        function showRemaining() {
            if (id_elems.length > 0) {
                id_elems.forEach(function(elem, ind, array) {
                    var end = new Date($("#room_dt" + elem).val());
                    var triv_end = new Date($("#room_dt" + elem).val());
                    end_tx = new Date(Date.parse(triv_end + " " + "GMT-0400"));
                    if (c_timer == 0) {
                        c_tmsvr();
                        date_triv = end_tx.toLocaleString("en-GB", {
                            hour12: true,
                        }).replace(',', '&#160<i class="fa-solid fa-arrows-left-right"' +
                            'style="color: #2800d8d8; font-size:13.5px;"></i>').replace(':00', '');
                        $('#dt_triv' + elem).html(date_triv);
                    }
                    if (c_timer == 1) {
                        distance = end - now;
                        if (distance <= 0) {
                            $("#tx_info_triv" + elem).html('En Juego');
                            status(elem);
                        }
                    }
                });
                now.setMilliseconds(1000);
            }
        }
        let timer = setInterval(showRemaining, 1000);

        function status(room) {
            $.ajax({
                url: "{{ asset('') }}trivia/status/1",
                headers: {
                    'X-CSRF-TOKEN': $("#5K3_70K").val()
                },
                method: 'get',
                dataType: 'json',
                success: function(data) {
                    if (data['van'] == 1) {
                        window.location.href = '{{ asset('') }}trivia/room/' + room;
                    }
                    if (data['rfs'].length > 0) {
                        $.each(data['rfs'], function(key, value) {
                            $("#room_" + value).hide();
                        });
                    }
                }
            });
        }
        $(".close_rm").click(function() {
            $(".mdl_triv").hide();
        });
        $(".on_indv").click(function() {
            $('.sym_coin').html(@json($wallet->name));
            if ($(this).data('rm_ind') == "1") {
                $("#m_trinv").show();
            } else if ($(this).data('rm_ind') == "2") {
                $("#m_tripvp").show();
            }
        });
        $(".on_rm").click(function() {
            var ftime = $(this).data('ftemp');
            document.getElementById('cat').value = ftime;
            setInterval(run_timer, 1000);
            saldo = Number(@json($wallet->balance));
            price = Number($(this).data('precio'));
            if ($(this).data('action') == "0") {
                if (saldo >= price) {
                    $("#tdx").html(
                        '<i class="fa-solid fa-puzzle-piece" style="margin-right:2.5px;"></i>' +
                        '<b>UNIRSE AL JUEGO</b>' +
                        '<small> Ref ' + (price * $(this).data('comision') / 100).toFixed(2) + '</small>');
                    $("#dbr").html(
                        '<span style="font-size:15.5px;">¿Confirmas tu participación?</span>' +
                        '<a href="{{ asset('') }}Wallet/triv_multi/' + $(this).data('id') + '/' + price +
                        '">' + '<br><h5 class="if_py">Sí, quiero jugar</h5></a>');
                } else {
                    $("#tdx").html(
                        '<i class="fa-solid fa-puzzle-piece" style="margin-right:2.5px;"></i>' +
                        '<b>UNIRSE AL JUEGO</b>');
                    $("#dbr").html(
                        '!No tienes suficiente saldo!<a href="{{ route('Wallet.index') }}"><br><b>RECARGAR</b></a>'
                    )
                }
            } else {
                $("#tdx").html(
                    '<i class="fa-solid fa-circle-user" style="margin-right:2.5px;"></i><b>JUEGO EN ESPERA</b>');
                $("#dbr").html(
                    '<span style="font-size:15px">Lo sentimos, aún no se cumple la fecha y hora para dar inicio a esta' +
                    ' sala de juego</span>' +
                    '<br><div class="mt-2 mb-1 bg-navy bg-rke" style="font-size:16px" id=roxz></div>'
                );
            }
            $("#mdl_triv_multi").show();
        });
    </script>
@endsection
