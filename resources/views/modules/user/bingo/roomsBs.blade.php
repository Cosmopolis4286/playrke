@extends('layouts.app_rke')
<title>Bingo - PlayRKE</title>
@section('styles')
    <link rel="stylesheet" href="{{ asset('css/styles_bg.css') }}">
@endsection
<style>
    body {
        background: url(img/body/space.png) !important;
    }
</style>
@section('content')
    <div style="position: relative">
        <div id="tx_rm_triv">SALAS DE JUEGO
            <a href="{{ route('bingo.rooms_usdt') }}">
                <span>Cambiar a USDT
                    <img style="margin-bottom:2px; width:13.25px;" src="{{ asset('imgSystem/tether.svg') }}">
                </span>
            </a>
        </div>
        <div id="mc_buy">
            <div class="modal-body">
                <div class="bot_mdl bg_bot_dv">
                    <div style="display: flex;">
                        <div class="cont_fl">
                            <img class="img_cf" src="{{ asset('img/cofre.png') }}" alt="IMG">
                        </div>
                        <div class="cont_fl" style="display: grid;">
                            <div class="bot_tx">jackpot:</div>
                            <div class="bt_num_lt">
                                <span>{{ $wallet->name }} </span>
                                <span id="v_bot"></span>
                            </div>
                        </div>
                    </div>
                    <div class="date_bg">
                        <div>
                            <i class="ico_cl fa-regular fa-calendar-days"></i>
                            <span id="dat_f" style="font-size:16.8px;"></span>
                        </div>
                        <div>
                            <i class="ico_cl fa-solid fa-clock"></i>
                            <span id="da_tm" style="font-size:19.4px;"></span>
                        </div>
                    </div>
                </div>
                <form id="info_buy" action="{{ route('bingo.carton_store') }}" method="POST"
                    enctype="multipart/form-data">@csrf
                    <div class="bot_mdl mt-1">
                        <label class="buy_ctn">Compra tus cartones:</label>
                        <div class="cont_fl" id="aum_buy" style="display: none;">
                            <div class="mr-1" style="font-size: 1.15em">Automarcar</div>
                            <i style="color: #004bbbe0;" class="fa-solid fa-circle-check"></i>
                        </div>
                        <div id="aum" class="cont_fl" style="display: none">
                            <input class="mr-1" type="checkbox" id="buy_autom" name="auto_mc" value="0">
                            <span style="font-size: 0.88em;">Automarcar</span>
                        </div>
                    </div>
                    <div class="bot_mdl mbg-7">
                        <input type="hidden" id="type_bt" name="type_bt">
                        <input type="hidden" id="precio">
                        <div class="cont_fl">
                            <span class="tx_cctn">Cantidad</span>
                            <div class="quantity">
                                <div class="quant quantity-down">-</div>
                                <input id="cantidad" name="cantidad" type="number" min="1" step="1"
                                    value="1">
                                <div class="quant quantity-up">+</div>
                            </div>
                        </div>
                        <div class="cont_fl">
                            <div class="mr-1 tot_tx">Total:</div>
                            <div style="font-size:1.32em;">{{ $wallet->name }}&#160<span class="total"></span></div>
                            <input type="hidden" class="total" name="montop">
                        </div>
                    </div>
                    <input type="hidden" id="id_bingo" name="bingoid">
                </form>
                <div class="mensaje" style="text-align: center;"></div>
            </div>
            <hr class="line_mdl">
            <div class="cont_bg_f">
                <button id="close_mdl" type="button" style="font-size: 14.45px;"
                    class="btn btn-secondary">CANCELAR</button>
                <div class="cont_mc">
                    <img src="{{ asset('img/rke.png') }}" style="margin-left:-3.5px; max-height: 30px;">
                </div>
                <button type="button" class="btn btn-primary" id="buy_bg">COMPRAR</button>
            </div>
        </div>
    </div>
    <div class="row jus_f" style="display:flex">
        @foreach ($rooms as $key => $datos)
            <div id="bg_in{{ $datos->id }}" class="col-sm-6 col-md-4 col-lg-3 ct_center mb-6">
                <input type="hidden" id="bg_in{{ $datos->id }}">
                <a href="#" class="s_bgx @if ($datos->status == 2) comp_cartn @endif"
                    data-date="{{ $datos->date }}" data-time="{{ $datos->time }}" data-precio="{{ $datos->price }}"
                    data-idb="{{ $datos->id }}" data-bot="{{ number_format($datos->jackpot, 2) }}"
                    data-automc="{{ $datos->val_automc }}" data-type='{{ $datos->type_boat }}'
                    data-status='{{ $datos->status }}' data-user_automc="{{ $datos->auto_mc }}">
                    <div class="box_rbg">
                        <div class="cont_rbg">
                            <span id="bg_tx{{ $datos->id }}" class="time_bg"><i
                                    class="fa-regular fa-clock"></i></span>
                            <span class="i_crt_full">Modo: Cartón Lleno</span>
                            @if ($datos->ctns > 0)
                                <div class="crtn_buy" title="Mis Cartones">
                                    +<span class="num_ct">{{ $datos->ctns }}</span>
                                </div>
                            @endif
                            @if ($datos->auto_mc == 1)
                                <div class="ic_atm" title="Tienes el Automarcador">
                                    <i class="fa-solid fa-check-to-slot"></i>
                                </div>
                            @endif
                            <img class="img_rbg" src="{{ asset('img/bg/estelar_fd.png') }}" alt="IMG">
                            <span class="b_te">
                                @if ($datos->type_boat == 1)
                                    jackpot:
                                @elseif($datos->type_boat == 2)
                                    Premio:
                                @endif
                            </span>
                            <span class="bot_cant">
                                @if ($datos->jackpot > 0)
                                    <div class="cont_price_bg">
                                        <small class="bgs_bs mr-1">{{ $wallet->name }}</small>
                                        {{ number_format($datos->jackpot, 2) }}
                                    </div>
                                @else
                                    <div class="cont_price_bg">
                                        <h3>ESPERANDO</h3>
                                    </div>
                                @endif
                            </span>
                            <span class="i_crt_bs">Cartón: {{ $wallet->name }} {{ $datos->price }}</span>
                            <span class="t_bg_hour" id="t_bg_hour{{ $datos->id }}">
                                @if ($datos->status == 2)
                                    Comprar cartones
                                @else
                                    No disponible
                                @endif
                            </span>
                        </div>
                    </div>
                </a>
            </div>
            <input type="hidden" id="{{ $datos->id }}" value="{{ $datos->id }}">
        @endforeach
    </div>
    <script>
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 1650,
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
    <script>
        $(document).ready(function() {
            function checkCtn() {
                $.post({
                    url: "{{ route('bingo.check_ctn') }}",
                    headers: {
                        'X-CSRF-TOKEN': $("#token").val()
                    },
                    dataType: 'json',
                    data: {
                        ctn: cod,
                        nm_chps: ar_tm,
                        ck_time: timeServ
                    },
                    success: (data) => {
                        const toastConfig = {
                            4: {
                                icon: 'success',
                                title: '¡Felicidades Ganaste!'
                            },
                            5: {
                                icon: 'warning',
                                title: '<span style="font-size:16px">¡Juega Limpio!</span>'
                            },
                            0: {
                                icon: 'success',
                                title: '¡Ganaste un premio menor!'
                            },
                            1: {
                                icon: 'warning',
                                title: getNoBingoTitle()
                            },
                            2: {
                                icon: 'warning',
                                title: '<span style="font-size:13.75px">¡Fin del juego, suerte la próxima!</span>'
                            },
                            3: {
                                icon: 'info',
                                title: '¡Muy tarde, el jackpot fué repartido!'
                            }
                        };

                        if (data.van === 0) {
                            soundWin.play();
                        }
                        if (data.van === 1 && autoMc === 0) {
                            tm_stop = Number(data.tm_stop) - tm_serv;
                            $('.bt_num, .send').prop("disabled", true);
                            penalty();
                        }
                        if (data.van === 4) {
                            allowGetInfo = 1;
                            win = 1;
                            soundWin.play();
                        }
                        if (data.van === 5) {
                            return_rm(1);
                        }

                        const config = toastConfig[data.van];
                        if (config) {
                            showToast(config.icon, config.title);
                        }

                        getWin();
                    }
                });

                function getNoBingoTitle() {
                    return autoMc >= 1 ?
                        '<span style="font-size:16px">¡No tienes Bingo!</span>' :
                        '<span style="font-size:14px">¡Penalización, no tienes Bingo!</span>';
                }

                function showToast(icon, title) {
                    Toast.fire({
                        icon: icon,
                        title: title
                    });
                }
            }

            if (@json($rooms).length > 0) {
                rm_timer();
            }
        })

        function sald_insf() {
            if ($(".swal2-toast").html() == null || $(".swal2-toast").html() == '') {
                Toast.fire({
                    icon: 'error',
                    title: '¡Saldo Insuficiente!'
                });
            }
        }
        if ($("#idmolda").val() != undefined) {
            $("#exampleModal").modal('show');
        }

        jQuery('.quantity').each(function() {
            var spinner = jQuery(this),
                input = spinner.find('input[type="number"]'),
                btnUp = spinner.find('.quantity-up'),
                btnDown = spinner.find('.quantity-down'),
                min = input.attr('min'),
                max = input.attr('max');

            btnUp.click(function() {
                var oldValue = parseFloat(input.val());
                if (oldValue >= max) {
                    var newVal = oldValue;
                } else {
                    var newVal = oldValue + 1;
                }
                spinner.find("input").val(newVal);
                spinner.find("input").trigger("change");
            });

            btnDown.click(function() {
                var oldValue = parseFloat(input.val());
                if (oldValue <= min) {
                    var newVal = oldValue;
                } else {
                    var newVal = oldValue - 1;
                }
                spinner.find("input").val(newVal);
                spinner.find("input").trigger("change");
            });
        });
        $(".comp_cartn").click(function() {
            if ($(this).data("status") == 2 && $("#bg_in" + $(this).data("idb")).val() != 0) {
                $("#mc_buy").show();
                $("#dat_f").html(new Date($(this).data("date")).toLocaleDateString('en-GB'));
                $("#da_tm").html(new Date(Date.parse(
                        $(this).data("date") + ' ' + $(this).data("time") + " " + "GMT-0400"))
                    .toLocaleString("en-US", {
                        hour: "2-digit",
                        minute: "2-digit",
                        hour12: true,
                    }));
                auto = $(this).data("automc");
                total = $(this).data("precio");
                $("#precio").val($(this).data("precio"));
                $("#type_bt").val($(this).data("type"));
                $("#v_bot").html($(this).data("bot"));
                $(".total").val($(this).data("precio")).html($(this).data("precio").toLocaleString("en-US"));
                $("#cantidad").val(1);
                $(".mensaje").html("");
                $("#id_bingo").val($(this).data("idb"));
                if ($(this).data("user_automc") > 0) {
                    $('#aum_buy').show();
                } else {
                    $('#aum').show();
                }
            }
        });
        $("#buy_autom").click(function() {
            operacion();
        })

        function operacion() {
            if ($("#cantidad").val() < 1) {
                $("#cantidad").val(1);
            }
            var mont_ctn = $("#precio").val() * $("#cantidad").val();
            if ($('#buy_autom').prop('checked')) {
                $("#buy_autom").val(1);
                total = mont_ctn + Number(auto);
            } else {
                $("#buy_autom").val(0);
                total = mont_ctn;
            }
            $(".total").val(total).html(total.toLocaleString("en-US"));
        };

        $("#cantidad").on("keyup change", function() {
            $(".quantity-up").show();
            operacion();
            if (@json($wallet->saldo) < Number(total) + Number($("#precio").val())) {
                $(".quantity-up").hide();
                sald_insf();
            }
        });

        $('input[type=number]').keypress(function(key) {
            if (key.charCode < 48 || key.charCode > 57) return false;
        });

        $("#close_mdl").click(function() {
            $("#mc_buy").hide();
        });

        $("#buy_bg").click(function() {
            if (@json($wallet->saldo) >= total) {
                $('#info_buy').submit();
            } else {
                sald_insf();
            }
        });

        var v_rtn = 0;
        let id_elems = [];
        var _second = 1000;
        var _minute = _second * 60;
        var _hour = _minute * 60;
        var _day = _hour * 24;
        var time_server = @json($date);
        var c_timer = 0;

        function rm_timer() {
            $.ajax({
                url: "{action ('BingoController@timer')}",
                method: 'get',
                dataType: 'json',
                data: {
                    id: @json($wallet->coin)
                },
                success: function(res) {
                    $.each(res, function(key, value) {
                        id_elems.push(value['id']);
                        $("#bg_in" + value['id']).val(value['date'] + " " + value['time']);
                    });
                }
            });
        }

        function c_tmsvr() {
            $.ajax({
                url: "{{ route('bingo.time_serv') }}",
                method: 'get',
                dataType: 'json',
                success: function(data) {
                    time_server = data['van'];
                }
            });
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
                    var end = new Date($("#bg_in" + elem).val());
                    if (c_timer == 0) {
                        now = new Date(time_server);
                        c_timer = 1;
                    }
                    distance = end - now;
                    var days = Math.floor(distance / _day);
                    var hours = Math.floor((distance % _day) / _hour);
                    var minutes = Math.floor((distance % _hour) / _minute);
                    var seconds = Math.floor((distance % _minute) / _second);

                    if (distance <= 0) {
                        $("#bg_in" + elem).val(0);
                        $("#bg_tx" + elem).html(
                            '<span style="font-size:15px; letter-spacing: 0.25px;">EN JUEGO</span>'
                        );
                        $("#t_bg_hour" + elem).html('No disponible');
                        status(elem);
                    } else {
                        if (days == 0) {
                            if (hours == 0) {
                                if (minutes == 0) {
                                    if (seconds != 0) {
                                        $("#bg_tx" + elem).html('<div class="time_dt">' + '<span>' + seconds +
                                            '</span>' + '<span class="tx_time">SEG</span>' + '</div>');
                                    }
                                } else {
                                    $("#bg_tx" + elem).html(
                                        '<div class="time_dt mr-2">' + '<span>' + minutes +
                                        '</span>' + '<span class="tx_time">MIN</span>' + '</div>' +
                                        '<div class="time_dt">' + '<span>' + seconds +
                                        '</span>' + '<span class="tx_time">SEG</span>' + '</div>');
                                }
                            } else {
                                $("#bg_tx" + elem).html(
                                    '<div class="time_dt mr-2">' + '<span>' + hours + '</span>' +
                                    '<span class="tx_time">HRS</span>' + '</div>' +
                                    '<div class="time_dt mr-2 tm_minutes">' + '<span>' + minutes +
                                    '</span>' + '<span class="tx_time">MIN</span>' + '</div>' +
                                    '<div class="time_dt">' + '<span>' + seconds +
                                    '</span>' + '<span class="tx_time">SEG</span>' + '</div>');
                                if (minutes == 0) {
                                    $('.tm_minutes').hide()
                                }
                            }
                        } else {
                            $("#bg_tx" + elem).html(
                                '<div class="time_dt mr-2">' + '<span>' + days +
                                '</span>' + '<span class="tx_time">DIAS</span>' + '</div>' +
                                '<div class="time_dt mr-2 tm_hours">' + '<span>' + hours + '</span>' +
                                '<span class="tx_time">HRS</span>' + '</div>' +
                                '<div class="time_dt mr-2 tm_minutes">' + '<span>' + minutes +
                                '</span>' + '<span class="tx_time">MIN</span>' + '</div>' +
                                '<div class="time_dt">' + '<span>' + seconds +
                                '</span>' + '<span class="tx_time">SEG</span>' + '</div>');
                            if (hours == 0) {
                                $('.tm_hours').hide();
                            }
                            if (minutes == 0) {
                                $('.tm_minutes').hide()
                            }
                        }
                    }
                });
                now.setMilliseconds(1000);
            }
        }
        let timer = setInterval(showRemaining, 1000);

        function status(id_sala) {
            $.ajax({
                url: "{{action ('BingoController@changeStatusRoom')",
                headers: {
                    'X-CSRF-TOKEN': $("#token").val()
                },
                method: 'get',
                dataType: 'json',
                data: {
                    id: id_sala
                },
                success: function(data) {
                    if (data['van'] == 1) {
                        window.location.href = 'room/' + id_sala;
                    }
                    if (data['rfs'].length > 0) {
                        $.each(data['rfs'], function(key, value) {
                            $("#bg_" + value).hide();
                        });
                    }
                }
            });
        }
    </script>
@endsection
