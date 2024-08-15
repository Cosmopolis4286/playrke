@extends('layouts.app_rke_provider')
@section('title', 'BingoCash')
@section('styles')
    <link rel="stylesheet" href="{{ asset('css/styles_bg.css') }}">
@endsection<style>
    body {
        background: url(../../img/body/space.png);
    }
    
</style>
@section('content')
    <button type="button" class="btn btn-success" id="prueba">prueba</button>
    <div class="cont_mdl_inf_game">
        <div id="info_game">
            <div id="info_game_body"></div>
            <div class="cont_bg_f">
                <button id="cls_mdl_inf_gm" type="button" class="btn btn-secondary">Cerrar</button>
            </div>
        </div>
        <div id="list_players_bingo">
            <div id="list_players_bingo_body"></div>
            <div class="cont_bg_f mt-n1">
                <button id="cls_mdl_inf_players" type="button" class="btn btn-secondary">Cerrar</button>
            </div>
        </div>
        <div style="position: relative">
            <div id="tx_rm_triv">SALAS DE JUEGO
                {{-- @if ($wallet->coin > 0)
                    <a href="{{ route('bingo.rooms', ['symCoin' => $wallet->Coin->name]) }}">
                        <span>Cambiar a {{ $wallet->Coin->info }}</span>
                    </a>
                @endif --}}
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
                <form id="info_buy" action="{{ route('bingo.carton_store') }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="bg_body_mdl">
                        <div class="cont_info_bing_mdl">
                            <a href="#" id="ck_inf_gm">
                                <i class="inf_lg_bing_mdl fa-solid fa-circle-info"></i>
                                Info. del Juego
                            </a>
                            <div class="cont_pjs_bing">
                                <span id="cant_pjs_bing"></span>
                                <input type="hidden" id="val_pjs_bing">
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
                                        <input id="cantidad" name="cantidad" type="number" step="1" max="25"
                                            min="1" value="1">
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
{{-- @section('scripts')
    <script>
        const coinRoom = '<?php echo json_encode($coinRoom); ?>';

        $("#prueba").click(function() {
            $.ajax({
                url: "{{ route('bingo.time_serv') }}",
                headers: {
                    'X-CSRF-TOKEN': $("#token").val()
                },
                method: 'get',
                dataType: 'json',
                success: function(data) {
                    now = new Date(data);
                }
            });
        });

        Echo.channel('rmBingo.' + coinRoom)
            .listen('GetRoomsBingo', (res) => {
                const data = res.data;
                for (let key in data) {
                    const room = data[key];
                    for (let prop in room) {
                        if (prop === 'jackpot') {
                            $('#montRoom' + room.id).text(parseFloat(room.jackpot).toFixed(2));
                        }
                    }
                }
            });

        $(document).ready(() => @json($rooms->count()) > 0 && rm_timer());

        function sald_insf() {
            if (!$(".swal2-toast").html()) {
                Toast.fire({
                    icon: 'error',
                    title: '¡Saldo Insuficiente!'
                });
            }
        }

        if ($("#idmolda").val()) {
            $("#exampleModal").modal('show');
        }

        $('.quantity').each(function() {
            const spinner = $(this),
                input = spinner.find('input[type="number"]'),
                btnUp = spinner.find('.quantity-up'),
                btnDown = spinner.find('.quantity-down'),
                min = parseFloat(input.attr('min')) || 0,
                max = parseFloat(input.attr('max')) || Infinity,
                step = parseFloat(input.attr('step')) || 1;

            const updateInput = (value) => input.val(value).trigger('change');

            btnUp.click(() => {
                const newValue = Math.min(+input.val() + step, max);
                updateInput(newValue);
                btnDown.toggle(newValue !== 1);
            });

            btnDown.click(() => {
                const newValue = Math.max(+input.val() - step, min);
                updateInput(newValue);
                btnDown.toggle(newValue !== 1);
            });

            btnDown.toggle(+input.val() !== 1);
        });

        var van_pjs_bing = 0;

        function view_players(arr_info) {
            ar_size = arr_info['name'].length;
            content = '';
            for (i = 0; i < ar_size; i++) {
                img = arr_info['foto'][i];
                if (img == null) {
                    img = 'rke.png';
                }
                // name = arr_info['name'][i];
                // if (name == $("#tx_username").html().trim()) {
                //     name = 'Tú';
                // }
                data = '<div class="cont_inf_player_bing">' +
                    '<div class="cont_img_pj_bing">' +
                    '<img class="img_us" style="height:31px " src="' +
                    '{{ asset('') }}imgSystem/imgUsers/' + img + '">' + '</div>' +
                    '<div class="name_player_bing_mdl">' +
                    arr_info['name'][i] + '</div>' + '</div>'
                content += data;
            }
            $("#list_players_bingo_body").html(content);
            $("#list_players_bingo").show();
            $("#cant_pjs_bing").html(ar_size);
            van_pjs_bing = 0;
        }

        $(".cont_pjs_bing").click(function() {
            if (van_pjs_bing == 0 && $("#val_pjs_bing").val() >= 1) {
                van_pjs_bing = 1;
                $.ajax({
                    url: "{{ route('bingo.consult_players') }}",
                    headers: {
                        'X-CSRF-TOKEN': $("#token").val()
                    },
                    method: 'post',
                    data: {
                        room: $("#id_bing").val()
                    },
                    dataType: 'json',
                    success: function(data) {
                        view_players(data['players']);
                    }
                });
            }
        });

        $("#cls_mdl_inf_gm").click(function() {
            $("#info_game").hide();
        });

        $("#cls_mdl_inf_players").click(function() {
            $("#list_players_bingo").hide();
        });

        $("#ck_inf_gm").click(function() {
            $.post("{{ asset('info/bingo.blade.php') }}", function(ext) {
                $("#mc_buy").hide();
                $("#info_game_body").html(ext).parent().show();
            });
        });

        var total = auto = 0;
        $(".comp_cartn").click(function() {
            if (Number(@json($wallet->usdt)) >= Number($(this).data("precio"))) {
                $("#info_game").hide();
                if ($(this).data("status") == 2 && $("#bg_in_sts" + $(this).data("idb")).val() == 0) {
                    $("#mc_buy").show();
                    $("#dat_f").html(new Date($(this).data("date")).toLocaleDateString('en-GB').replace('/20',
                        '/'));
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
                    $("#val_pjs_bing").val($(this).data("cantpj"));
                    if ($(this).data("type") == 2) {
                        $("#cont_pjs_bing").hide();
                    } else {
                        $("#cant_pjs_bing").html($(this).data("cantpj"));
                    }
                    $("#type_bt").val($(this).data("type"));
                    if ($(this).data("type") == 2) {
                        type_bot = 'Premio:';
                    } else {
                        type_bot = 'jackpot:';
                    }
                    $('.bot_tx').html(type_bot);
                    $("#v_bot").html($(this).data("bot"));
                    $(".total").val($(this).data("precio")).html($(this).data("precio").toLocaleString("en-US"));
                    $("#cantidad").val(1);
                    $("#id_bing").val($(this).data("idb"));
                    if ($(this).data("user_automc") > 0) {
                        $('#aum_buy').show();
                    } else {
                        $('#aum_in_use').show();
                    }
                }
            } else {
                sald_insf();
            }
        });

        $("#cantidad").on("keyup change", function() {
            $(".quantity-up").show();
            operacion();
        });

        $("#buy_autom").click(function() {
            if ((Number(total) + Number(auto)) > Number(@json($wallet->usdt))) {
                sald_insf();
                $('#buy_autom').prop('checked', false).val('0');
            } else {
                $('#buy_autom').val('1');
                operacion();
            }
        })

        function operacion() {
            const cantidad = $("#cantidad"),
                precio = $("#precio"),
                buyAutom = $('#buy_autom');

            cantidad.val(Math.max(1, cantidad.val()));

            const montCtn = precio.val() * cantidad.val(),
                total = buyAutom.prop('checked') ? montCtn + Number(auto) : montCtn,
                usdt = Number(@json($wallet->usdt));

            $(".quantity-up").toggle(usdt >= total + Number(precio.val()));

            if (total > usdt) {
                sald_insf();
            } else {
                $(".total").val(total).html(total.toLocaleString("en-US"));
            }
        }

        $('input[type=number]').keypress(function(key) {
            return key.charCode >= 48 && key.charCode <= 57;
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
        var id_elems = [];
        var _second = 1000,
            _minute = _second * 60,
            _hour = _minute * 60,
            _day = _hour * 24;
        var now = new Date(@json($date));
        var c_timer = 0;

        function rm_timer() {
            $.getJSON("{{ asset('') }}bingo/timer/1", {
                    'X-CSRF-TOKEN': $("#token").val()
                })
                .done(res => res.forEach(value => {
                    id_elems.push(value.id);
                    $(`#bg_in${value.id}`).val(`${value.date} ${value.time}`);
                }));
        }

        function c_tmsvr() {
            $.ajax({
                url: "{{ route('bingo.time_serv') }}",
                headers: {
                    'X-CSRF-TOKEN': $("#token").val()
                },
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
                        if (distance <= 0) {
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
                                            '<div class="time_dt">' + '<span>' + minutes +
                                            '</span>' + '<span class="tx_time">MIN</span>' + '</div>' +
                                            '<div class="time_dt">' + '<span>' + seconds +
                                            '</span>' + '<span class="tx_time">SEG</span>' + '</div>');
                                    }
                                } else {
                                    $("#bg_tx" + elem).html(
                                        '<div class="time_dt">' + '<span>' + hours + '</span>' +
                                        '<span class="tx_time">HRS</span>' + '</div>' +
                                        '<div class="time_dt tm_minutes">' + '<span>' + minutes +
                                        '</span>' + '<span class="tx_time">MIN</span>' + '</div>' +
                                        '<div class="time_dt">' + '<span>' + seconds +
                                        '</span>' + '<span class="tx_time">SEG</span>' + '</div>');
                                    if (minutes == 0) {
                                        $('.tm_minutes').hide()
                                    }
                                }
                            } else {
                                $("#bg_tx" + elem).html(
                                    '<div class="time_dt">' + '<span>' + days +
                                    '</span>' + '<span class="tx_time">DIAS</span>' + '</div>' +
                                    '<div class="time_dt tm_hours">' + '<span>' + hours + '</span>' +
                                    '<span class="tx_time">HRS</span>' + '</div>' +
                                    '<div class="time_dt  tm_minutes">' + '<span>' + minutes +
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
                url: "{{ asset('') }}bingo/status/" + id_sala,
                headers: {
                    'X-CSRF-TOKEN': $("#token").val()
                },
                method: 'get',
                dataType: 'json',
                success: function(data) {
                    if (data['van'] == 1) {
                        window.location.href = 'room/' + id_sala;
                    }
                    if (data['rfs'].length > 0) {
                        $.each(data['rfs'], function(key, value) {
                            $("#bg_rm" + value).hide();
                            if ($("#id_bing").val() == value) {
                                $("#mc_buy").hide();
                            }
                        });
                    }
                }
            });
        }
    </script>
@endsection --}}
