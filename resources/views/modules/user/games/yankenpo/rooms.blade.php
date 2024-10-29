@extends('layouts.app_rke')
<title>Yankenpo - PlayRKE</title>
@section('styles')
    <link rel="stylesheet" href="{{ asset('css/styles_ppt.css') }}">
@endsection
<style>
    body {
        background: url(./img/body/space.png);
    }
</style>
@section('content')
    <div id="info_game">
        <div id="info_game_body"></div>
        <div class="cont_bg_f">
            <button id="cls_mdl_inf_gm" type="button" class="btn btn-secondary">Cerrar</button>
        </div>
    </div>
    <div style="position: relative">
        <div id="tx_rm_triv">PIEDRA PAPEL O TIJERA
            @if ($wallet->coin > 1)
                <a href="{{ route('yankenpo.rooms') }}">
                    <span>Cambiar a {{ $wallet->info }}</span>
                </a>
            @endif
        </div>
        <div id="mc_buy">
            <div class="bg_bot_dv">
                <div class="cont_cfre_bot_bg">
                    <div class="cont_img_cfre">
                        <img class="img_cf" src="{{ asset('img/cofre.png') }}" alt="IMG">
                    </div>
                    <div class="cont_coin_bot_bg">
                        <div id="prize_bg_mdl">
                            <div class="bot_tx"></div>
                            <small>USDT</small>
                        </div>
                        <div id="bt_num_lt">
                            <span id="v_bot"></span>
                        </div>
                    </div>
                </div>
                <div class="date_bg">
                    <div class="cont_date_time">
                        <i class="ico_cl fa-regular fa-calendar-days"></i>
                        <span id="dat_f"></span>
                    </div>
                    <div class="cont_date_time">
                        <i class="ico_cl_rlg fa-solid fa-clock"></i>
                        <span id="da_tm"></span>
                    </div>
                </div>
            </div>
            <form id="info_buy" action="{{ route('bingo.carton_store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="bg_body_mdl">
                    <div class="cont_info_bing_mdl">
                        <a href="#" id="ck_inf_gm">
                            <i class="inf_lg_bing_mdl fa-solid fa-circle-info"></i>
                            Info. del Juego
                        </a>
                        <div class="cont_pjs_bing">
                            <span id="cant_pjs_bing"></span>
                            <i class="fa-solid fa-users"></i>
                        </div>
                    </div>
                    <div class="cont_inf_buy_bing_mdl">
                        <div class="content_line_bing">
                            <div class="colum_bg_left">
                                <input type="hidden" id="id_bing" name="bingoid">
                                <div class="buy_ctn">Compra tus cartones:</div>
                            </div>
                            <div class="colum_bg_right">
                                <div class="cont_automc_use" id="aum_buy" style="display: none">
                                    <div id="txt_automc_use">Automarcar</div>
                                    <i style="color: #004bbbe0;" class="fa-solid fa-circle-check"></i>
                                </div>
                                <div class="hide_info_bg" id="aum_in_use">
                                    <input type="checkbox" id="buy_autom" name="auto_mc" value="0">
                                    <span id="txt_automc_sn">Automarcar</span>
                                </div>
                            </div>
                        </div>
                        <div class="content_line_bing">
                            <div class="colum_bg_left cant_ctns_mdl_bg">
                                <input type="hidden" id="type_bt" name="type_bt">
                                <input type="hidden" id="precio">
                                <div class="tx_cctn">Cantidad</div>
                                <div class="quantity">
                                    <div class="quant quantity-down">
                                        <i class="fa-solid fa-minus"></i>
                                    </div>
                                    <input id="cantidad" name="cantidad" type="number" min="1" step="1"
                                        value="1">
                                    <div class="quant quantity-up">
                                        <i class="fa-solid fa-plus"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="colum_bg_right cont_tx_total">
                                <span class="tot_tx">Total:</span>
                                <div id="cant_total_mdl_bg">
                                    <span class="total"></span><small>USDT</small>
                                </div>
                                <input type="hidden" class="total" name="montop">
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <div class="cont_bg_f">
                <button id="close_mdl" type="button" class="btn btn-secondary">CANCELAR</button>
                <div class="cont_img_ctn">
                    <img src="{{ asset('img/rke.png') }}">
                </div>
                <button type="button" class="btn btn-primary" id="buy_bg">COMPRAR</button>
            </div>
        </div>
    </div>
    <style>
        #cont_ppt_bets {
            background-color: blue;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
    <div id="cont_ppt_bets">
        <div>1</div>
        <div>2</div>
        <div>3</div>
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
            if ($(this).data("status") == 2 && $("#bg_in_sts" + $(this).data("idb")).val() == 0) {
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
            if (@json($wallet->usdt) < Number(total) + Number($("#precio").val())) {
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
            if (@json($wallet->usdt) >= total) {
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
        var c_timer = 0;

        function rm_timer() {
            $.ajax({
                url: "../bingo/timer/1",
                method: 'get',
                dataType: 'json',
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
                    var end = new Date($("#bg_in" + elem).val());
                    var bing_end = new Date($("#bg_in" + elem).val());
                    new Date(bing_end + (bing_end.setSeconds(bing_end.getSeconds() + 620)));
                    if (c_timer == 0) {
                        c_tmsvr();
                    }
                    distance = end - now;
                    var days = Math.floor(distance / _day);
                    var hours = Math.floor((distance % _day) / _hour);
                    var minutes = Math.floor((distance % _hour) / _minute);
                    var seconds = Math.floor((distance % _minute) / _second);

                    if (c_timer == 1) {
                        if (distance < 0) {
                            if ($("#bg_in_sts" + elem) != 1) {
                                if (bing_end - now > 0) {
                                    $("#bg_tx" + elem).html(
                                        '<span style="font-size:15px; letter-spacing: 0.25px;">EN JUEGO</span>'
                                    );
                                } else {
                                    $("#bg_tx" + elem).html(
                                        '<span style="font-size:14px; letter-spacing: 0.25px;">FINALIZADA</span>'
                                    );
                                }
                                $("#t_bg_hour" + elem).html('No disponible');
                                $("#bg_in_sts" + elem).val(1);
                                status(elem);
                            }
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
                    }
                });
                now.setMilliseconds(1000);
            }
        }
        let timer = setInterval(showRemaining, 1000);

        function status(id_sala) {
            $.ajax({
                url: "../bingo/status/" + id_sala,
                headers: {
                    'X-CSRF-TOKEN': $("#5K3_70K").val()
                },
                method: 'get',
                dataType: 'json',
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
