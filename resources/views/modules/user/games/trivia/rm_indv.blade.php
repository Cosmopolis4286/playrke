@extends('layouts.app_rke')
<title>El Poder del Saber - PlayRKE</title>
<style>
    body {
        background: url(../img/body/space.png) !important;
    }

    @import url('https://fonts.googleapis.com/css2?family=Righteous&display=swap');
</style>
@section('content')
    <script>
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 1750,
            timerProgressBar: true,
        })

        function alert_spy() {
            Toast.fire({
                icon: 'warning',
                html: '<div style="padding-top:1.6% !important; font-size:1rem;">¡Cuidado, sigue jugando!</div>'
            })
        }
    </script>
    <style>
        #mdl_retire {
            position: absolute;
            z-index: 999 !important;
            background-color: rgb(8, 0, 53);
            top: 45%;
            left: 50%;
            transform: translate(-50%, -50%);
            min-width: 240px;
            border-radius: 8px;
            border: 1px solid #008f4c;
            display: grid;
            text-align: center;
            justify-content: center;
            padding: 16.5px;
        }

        .btn_triv {
            border: transparent;
            border-radius: 4px;
            background: #003ad8;
        }

        .sub_mdl_retire {
            font-size: 17.5px;
            color: rgb(179, 231, 255) !important;
            margin-top: -7px;
        }

        #tx_rest {
            margin-top: -3.5px;
            color: rgb(0, 79, 197);
        }

        #tm_ret {
            color: rgb(214, 0, 0) !important;
        }

        .rk_show {
            display: block;
        }

        .rk_hide {
            display: none;
        }
    </style>
    <div class="row">
        <div class="col-12">
            <div class="fnd-game mt-2 mb-4">
                <div id="mdl_retire" @if ($md_retire == 0) style="display: none;" @endif>
                    <div class="sub_mdl_retire">
                        ¿Deseas retirarte con Bs
                        <span id="profit"></span>?
                    </div>
                    <span id="tx_rest">Siguente question en <span id="tm_ret"></span></span>
                    <div class="mt-1" style="display: flex; margin:0 auto;">
                        <button type="button" class="btn_triv btn_retire mr-3" value="1">Retirarse</button>
                        <button type="button" class="btn_triv btn_retire" value="0">Continuar</button>
                    </div>
                </div>
                <input type="hidden" id="ruta" value="{{ asset('') }}">
                <div class="in_game">
                    <div class="top-info-pds">
                        <div id="loading-screen" style="display:none">
                            <img src="{{ asset('img/spn.svg') }}">
                        </div>
                        <div class="opc-cont-pds">
                            <div class="opc-config-pds">
                                <div class="opc-cont-item">
                                    <audio id="track">
                                        <source src="{{ asset('sounds/trivia/' . $sound_trv[0]->file) }}" type="audio/mpeg">
                                    </audio>
                                    <a href="#" id="b_track" onclick="play_pause()">
                                        <i class="fas fa-solid fa-music"></i>
                                    </a>
                                </div>
                            </div>
                            <div class="info-cont-pds">
                                <div class="info_game">
                                    <div class="lb_gm">Acumulado</div>
                                    <div class="inf_pds ">
                                        <input type="checkbox" id="ca_mon">
                                        <label for="ca_mon"></label>
                                        <div class="@if ($nm_quest > 1) r_hide @endif">- - - -</div>
                                        <span id="b_usd" class="@if ($start == 0 || $nm_quest == 1) r_hide @endif">
                                            <span class="sym_mo">Bs&#160;</span>{{ number_format($gain, 2) }}
                                        </span>
                                        <span id="b_bs" class="@if ($start == 0 || $nm_quest == 1) r_hide @endif"><span
                                                class="sym_mo">US$</span>
                                            {{ number_format($gain / $CommerceExchangeRate, 2) }}
                                        </span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="img-cont cold">
                            <img class="img-pds" src="{{ asset('img/pds.png') }}">
                        </div>
                        <div class="time_one timer">25</div>
                        <div class="opc-cont-pds">
                            <div class="info-cont-pds">
                                <div class="info_game">
                                    <div class="lb_gm">Acumulado</div>
                                    <div class="inf_pds ">
                                        <input type="checkbox" id="ca_mon">
                                        <label for="ca_mon"></label>
                                        <div class="@if ($nm_quest > 1) r_hide @endif">- - - -</div>
                                        <div id="b_usd" class="@if ($start == 0 || $nm_quest == 1) r_hide @endif">
                                            <span class="sym_mo">Bs&#160;</span>{{ number_format($gain, 2) }}
                                        </div>
                                        <span id="b_bs" class="@if ($start == 0 || $nm_quest == 1) r_hide @endif"><span
                                                class="sym_mo">US$</span>
                                            {{ number_format($gain / $CommerceExchangeRate, 2) }}
                                        </span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="opc-config-pds">
                                <a href="#" class="c_act comond" data-cdin_h='1'>
                                    <div class="opc-cont-item">
                                        <i class="fas fa-balance-scale"></i>
                                        <div class="half nm-cm">{{ $c_half }}</div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                    <audio id="s_correct">
                        <source src="{{ asset('sounds/trivia/' . $sound_trv[1]->file) }}" type="audio/mp3">
                    </audio>
                    <audio id="s_inct">
                        <source src="{{ asset('sounds/trivia/' . $sound_trv[2]->file) }}" type="audio/mp3">
                    </audio>
                    <div class="fnd-pq run_gm">
                        <div class="show_gm">
                            <div class="ct_qst">
                                <div class="time_gl timer"></div>
                                <div class="qst_r">
                                    @if ($start == 0)
                                        <button id="in_p1" onclick="ShowHide('rk_hide','rk_show')"
                                            class="rk_show iniciar">iniciar</button>
                                    @endif
                                    <div class="@if ($start == 0) rk_hide @endif">
                                        ¿<span id="quest">{{ $quest }}</span>?
                                    </div>
                                </div>
                                <div class="time_gr timer"></div>
                            </div>
                        </div>
                        {{-- Loser --}}
                        <div class="show_loser @if ($s_live == [1 || 2]) wl_show  @else wl_hide @endif">
                            <div class="ct_qst">
                                <div class="qst_gm">
                                    <div class="qinc">
                                        <i class="mr-1 icon_inc fa-regular fa-circle-xmark"></i>
                                        <span
                                            class="msgPty @if ($s_live == 1) wl_show @else wl_hide @endif">
                                            Jugador Eliminado</span>
                                        <span
                                            class="msgTime @if ($s_live == 1) wl_show @else wl_hide @endif">
                                            El tiempo ha finalizado</span>
                                        <span
                                            class="msgQInc @if ($s_live == 2) wl_show @else wl_hide @endif">
                                            Respuesta incorrecta, has perdidio
                                        </span>
                                        <i class="ml-1 icon_inc fa-regular fa-circle-xmark"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- Loser --}}
                        <div class="cont-opc_img">
                            <div class="opc_r">
                                <button class="cont_qp s_opc_a resp" id="opc_a" value="a">
                                    <div style="display:flex; align-items:center;">
                                        <span>A.</span>
                                        <span class="opc_rt @if ($start == 0) rk_hide @endif"
                                            id="r_opc_a">{{ $answers['0'] }}
                                        </span>
                                    </div>
                                </button>
                                <button class="cont_qp s_opc_c resp" id="opc_c" value="c">
                                    <div style="display:flex; align-items:center;">
                                        <span class="q_cc">C.</span><span class="q_cb">B.</span>
                                        <span
                                            class="show_gm opc_rt op_bc @if ($start == 0) rk_hide @endif"
                                            id="r_opc_c">{{ $answers['2'] }}</span>
                                    </div>
                                </button>
                            </div>
                            <style>
                                .cont_inf_wn_triv {
                                    width: 46.5%;
                                    display: grid;
                                }

                                .tx_thank_triv {
                                    display: none;
                                }

                                @media screen and (max-width: 342px) {
                                    .msgTime {
                                        font-size: 16px;
                                    }

                                    #msg_win {
                                        font-size: 18.5px;

                                    }

                                    .text_prizes {
                                        font-size: 3.15vw;
                                    }
                                }

                                @media screen and (min-width: 343px) {
                                    .msgTime {
                                        font-size: 18.5px;
                                    }

                                    #msg_win {
                                        font-size: 21px;

                                    }

                                }

                                @media screen and (min-width: 400px) {
                                    .msgTime {
                                        font-size: 22px;
                                    }

                                    #msg_win {
                                        font-size: 26px;

                                    }

                                }

                                @media screen and (max-width: 495px) {
                                    .mdl_wn_triv {
                                        width: 86.5%;
                                    }

                                    .tx_thank_triv {
                                        position: absolute;
                                        top: 108%;
                                        width: 120%;
                                        display: block;
                                        text-transform: uppercase;
                                        font-size: 3.8vw;
                                        color: #003ad8;
                                        font-weight: 500;
                                        letter-spacing: 0.5px;
                                    }

                                    .cont_inf_wn_triv {
                                        width: 100%;
                                        position: relative;
                                        top: 8.25%;
                                    }

                                    .cont_img_wn {
                                        position: relative;
                                        top: 16.5%;
                                        display: flex;
                                        justify-content: center;
                                        max-height: 95px;
                                    }

                                    .m_wt {
                                        opacity: 0.46;
                                        position: absolute;
                                        top: 50%;
                                        left: 50%;
                                        max-width: 92px;
                                    }

                                    .hid_dv {
                                        display: none;
                                    }

                                    .cont_img_p {
                                        position: absolute;
                                        top: 125.75%;
                                        max-height: 96px;
                                        width: 100%;
                                    }

                                    .icon_mv_triv {
                                        display: block;
                                        font-size: 26px;
                                        margin-top: 3.5%;
                                    }

                                    .img_p {
                                        border: 2px solid #001c85;
                                        max-width: 175px;
                                        margin: 0 2.25%;
                                    }

                                    .time_one {
                                        display: block;
                                        margin: 0 2.3%;
                                        margin-top: 88px;
                                        font-size: 37px;
                                        color: #ad1616;
                                        font-weight: bold;
                                    }

                                    .fnd-game {
                                        background-color: #030012;
                                        border-radius: 10px;
                                        border: 3px solid #080030;
                                        width: 100%;
                                        height: 100%;
                                        min-height: 30.82em;
                                        margin: 0 auto;
                                        margin-top: -4.15% !important;
                                        padding: 1.1%;
                                    }

                                    .inf_pds {
                                        background-color: #010015;
                                        color: #00AD5C;
                                        width: 105px;
                                        border-radius: 8px;
                                        border: 1px solid #070064;
                                    }
                                }
                            </style>
                            <div class="cont_img_p">
                                <div class="icon_mv_triv mr-2">
                                    <a href="#" id="b_track" onclick="play_pause()">
                                        <i class="mb-2 fas fa-solid fa-music"></i>
                                    </a>
                                    <a href="#" class="c_jump c_act" data-cdin_j='2'>
                                        <div class="opc-cont-item">
                                            <i class="fas fa-frog"></i>
                                            <div id="jump" class="nm-cm"></div>
                                        </div>
                                    </a>
                                </div>
                                @if ($start == 0)
                                    <img class="rk_show img_p" src="{{ asset('img/spc.png') }}">
                                @endif
                                @if ($img == '')
                                    <img class="@if ($start == 0) rk_hide @endif img_p"
                                        src="{{ asset('img/spc.png') }}">
                                @else
                                    <img class="@if ($start == 0) rk_hide @endif img_p"
                                        src="{{ asset('imgSystem/imgquestions/' . $img) }}">
                                @endif
                                <img class="m_wt" src="{{ asset('img/mw.png') }}">
                                <div class="n_quest">
                                    <span id="num_qt">{{ $nm_quest }}</span>/{{ $sz_qst }}
                                </div>
                                <div class="icon_mv_triv ml-2">
                                    <a href="#"><i class="mb-2 fas fa-cog"></i></a>
                                    <a href="#" class="c_act comond" data-cdin_h='1'>
                                        <div class="opc-cont-item">
                                            <i class="fas fa-balance-scale"></i>
                                            <div id="half" class="nm-cm">{{ $c_half }}</div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                            <div class="opc_r">
                                <button class="cont_qp s_opc_b resp" id="opc_b" value="b">
                                    <div style="display:flex; align-items:center;">
                                        <span class="q_cc">B.</span><span class="q_cb">C.</span>
                                        <span
                                            class="show_gm opc_rt op_bc @if ($start == 0) rk_hide @endif"
                                            id="r_opc_b">{{ $answers['1'] }}</span>
                                    </div>
                                </button>
                                <button class="cont_qp s_opc_d resp" id="opc_d" value="d">
                                    <div style="display:flex; align-items:center;">
                                        <span>D.</span>
                                        <span class="show_gm opc_rt @if ($start == 0) rk_hide @endif"
                                            id="r_opc_d">{{ $answers['3'] }}</span>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="fnd-pq show_winner @if ($s_live == 3) wl_show  @else wl_hide @endif">
                        <div class="ct_qst">
                            <div class="qst_gm">
                                <div class="qct">
                                    <i class="mr-1 icon_inc fa-solid fa-trophy"></i>
                                    <span id="msg_win">Felicidades Ganaste</span>
                                    <i class="ml-1 icon_inc fa-solid fa-trophy"></i>
                                </div>
                            </div>
                        </div>
                        <div class="cont-opc_img">
                            <div class="cont_img_wn hid_dv cold">
                                <img class="img_p" src="{{ asset('img/arc.jpg') }}">
                                <img class="m_wt" src="{{ asset('img/mw.png') }}">
                            </div>
                            <div class="cont_inf_wn_triv ml-1 mr-1">
                                <div style="font-size: 20.5px; color:#0074e0;">
                                    Ranking Pos. 3
                                </div>
                                <div style="font-size: 17.5px; color:#0052cc; margin-top: -3.4%">
                                    TIEMPO TOTAL {{ $time_gm }}" <small>SEG</small></div>
                                <div style="margin:0 auto;">
                                    <button id="b_united">Unirse a otro juego 15%OFF</button>
                                </div>
                                <div class="mt-1 text_prizes">
                                    Los premios se envian al final del juego
                                </div>
                            </div>
                            <div class="cont_img_wn cold">
                                <img class="img_p" src="{{ asset('img/arc.jpg') }}">
                                <img class="m_wt" src="{{ asset('img/mw.png') }}">
                                <div class="tx_thank_triv">¡Gracias por participar!</div>
                            </div>
                        </div>
                    </div>
                    {{-- Fin Winner --}}
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        const screen = $('#loading-screen');
        var init = @json($start),
            cnt_spy = @json($cnt_spy),
            s_live = @json($s_live),
            c_half = @json($c_half),
            use_half = @json($use_half),
            united = @json($desct),
            xyz = @json($xyz[1]),
            afo = @json($xyz[2]),
            nm_quest = @json($nm_quest),
            tim_end = @json($tim_end),
            penalty = rep_s = null,
            retire = @json($md_retire),
            tm_ret = @json($tm_ret),
            play_snd = false,
            title = document.title;

        /*window.onblur = function() {
            if (init == 1 && retire == 0) {
                if (cnt_spy == 7) {
                    spy_change();
                    let on_init = setTimeout(function incremento() {
                        document.title = "¡Hey, vuelve aquí! " + cnt_spy;
                        cnt_spy--;
                        let stop = setTimeout(incremento, 1000);
                        if (cnt_spy < 0) {
                            clearTimeout(stop);
                            cnt_spy = 0;
                            if (tim_end > 0) {
                                penalty = 1;
                                end_time();
                            }
                        }
                    }, 1000);
                } else if (cnt_spy + 7 <= Date.now()) {
                    if (tim_end > 0) {
                        penalty = 1;
                        end_time();
                    }
                }
            }
        }
        window.onfocus = function() {
            document.title = title;
            if (init == 1 && retire == 0) {
                clearTimeout(stop);
                console.log(20000);
                alert_spy();
            }
        }*/

        function spy_change() {
            $.ajax({
                url: "{{ route('triv_in.spy') }}",
                headers: {
                    'X-CSRF-TOKEN': $("#token").val()
                },
                type: 'post',
                dataType: 'json',
                data: {
                    nm_quest: nm_quest,
                },
            });
        }

        function cont_ret() {
            if (init == 1 && retire > 0) {
                if (tm_ret == 7) {
                    intm_ret();
                }
                let init = setTimeout(function incremento() {
                    $("#tm_ret").html(tm_ret);
                    tm_ret--;
                    let stop = setTimeout(incremento, 1000);
                    if (tm_ret <= 0) {
                        $("#tm_ret").html(0);
                        next_quest();
                        clearTimeout(stop);
                    }
                }, 1000);
            }
        }

        function intm_ret() {
            $.ajax({
                url: "{{ route('triv_in.time_retire') }}",
                headers: {
                    'X-CSRF-TOKEN': $("#token").val()
                },
                type: 'post',
                dataType: 'json'
            });
        }

        loader_show(screen);

        function loader_show(screen) {
            $(document)
                .ajaxStart(function() {
                    screen.fadeIn();
                })
                .ajaxStop(function() {
                    screen.fadeOut();
                });
        }

        function play_pause() {
            if (play_snd == false) {
                $('#track')[0].play()
                play_snd = true
            } else {
                $('#track')[0].pause()
                play_snd = false
            }
        }

        window.onload = refresh_cmnd();

        function refresh_cmnd() {
            $.each($('a.c_act'), function() {
                if (init == 1) {
                    $('#b_track').css('pointer-events', 'auto');
                    $('#b_track').css('cursor', 'pointer');
                }
                if (init == 0) {
                    $(this).css('pointer-events', 'none');
                    $(this).css('cursor', 'not-allowed');
                    $('#b_track').css('pointer-events', 'none');
                    $('#b_track').css('cursor', 'not-allowed');
                }
                if (init == 1 && s_live == 0 && nm_quest < 4) {
                    $(this).css('pointer-events', 'auto');
                    $(this).css('cursor', 'pointer');
                }
                if (nm_quest > 3 || s_live > 0) {
                    $(this).css('pointer-events', 'none');
                    $(this).css('cursor', 'not-allowed');
                }
            });
        }

        $(".iniciar").click(function() {
            tim_end = 20;
            start(), start_cont(), play_pause();
        });

        function ShowHide(ShowClass, HideClass) {
            $(`.${ShowClass}`).show();
            $(`.${HideClass}`).hide();
        }

        if (init == 1 && s_live == 0) {
            start_cont();
        }

        function start_cont() {
            let init = setTimeout(function incremento() {
                $(".timer").html(tim_end);
                tim_end--;
                let stop = setTimeout(incremento, 1000);
                if (tim_end <= 0 || retire > 0) {
                    $(".timer").html(0);
                    clearTimeout(stop);
                }
                if (tim_end <= 0 && penalty == null) {
                    end_time();
                }
            }, 1000);
        }

        function consult_cm() {
            if (use_half == 1) {
                hide_r();
            }
            half_r = c_half - 1;
            $(".half").html(half_r);
            use_half = 0;
        }

        function hide_r() {
            if (xyz == $("#r_opc_a").html() ||
                afo == $("#r_opc_a").html()) {
                $("#r_opc_a").hide();
            }
            if (xyz == $("#r_opc_b").html() ||
                afo == $("#r_opc_b").html()) {
                $("#r_opc_b").hide();
            }
            if (xyz == $("#r_opc_c").html() ||
                afo == $("#r_opc_c").html()) {
                $("#r_opc_c").hide();
            }
            if (xyz == $("#r_opc_d").html() ||
                afo == $("#r_opc_d").html()) {
                $("#r_opc_d").hide();
            }
        }

        function ShowQuest() {
            $('.opc_rt').show();
        }

        function show_over() {
            track.pause();
            $(".show_gm").hide();
            $(".show_loser").show();
        }
        console.log(init, s_live);

        $(".resp").click(function() {
            r_val = $(this).val();
            if (r_val == 'a') {
                rep_s = $("#r_opc_a").html();
            }
            if (r_val == 'b') {
                rep_s = $("#r_opc_b").html();
            }
            if (r_val == 'c') {
                rep_s = $("#r_opc_c").html();
            }
            if (r_val == 'd') {
                rep_s = $("#r_opc_d").html();
            }
            if (init == 1 && s_live == 0) {
                $.ajax({
                    url: "{{ route('triv_in.anwers') }}",
                    headers: {
                        'X-CSRF-TOKEN': $("#token").val()
                    },
                    type: 'post',
                    dataType: 'json',
                    data: {
                        resp: rep_s,
                        id_triv: {{ $id_triv }},
                        nm_quest: nm_quest,
                    },
                    success: function(data) {
                        ShowQuest();
                        tm_ret = 7;
                        retire = 1;
                        $('#profit').html(data['profit']);
                        if (data['van'] == 1) {
                            $('#s_correct')[0].play();
                            $('#mdl_retire').show();
                            cont_ret();
                        } else if (data['van'] == 0) {
                            $(".msgQInc").show();
                            show_over();
                        } else if (data['van'] == 2) {
                            $('.show_winner').show();
                        }
                    },
                });
            }
        });

        $(".btn_retire").click(function(e) {
            $.ajax({
                url: "{{ route('triv_in.retire') }}",
                headers: {
                    'X-CSRF-TOKEN': $("#token").val()
                },
                type: 'post',
                dataType: 'json',
                data: {
                    val_rt: $(this).val(),
                    nm_quest: nm_quest,
                    id_triv: {{ $id_triv }}
                },
                success: function(data) {
                    if (data['van'] == 1) {
                        $('#mdl_retire').hide();
                        $('#quest').html(data['questions']);
                        $('#r_opc_a').html(data['answers'][0]);
                        $('#r_opc_b').html(data['answers'][1]);
                        $('#r_opc_c').html(data['answers'][2]);
                        $('#r_opc_d').html(data['answers'][3]);
                        $('#num_qt').html(data['nm_qt']);
                        xyz = data['xyz_w'][1];
                        afo = data['xyz_w'][2];
                        nm_quest = data['nm_qt'];
                        tim_end = 20;
                        retire = 0;
                        start_cont();
                    } else if (data['van'] == 0) {
                        window.location.href = "{{ route('trivia.index') }}";
                    }
                },
            });
            e.stopPropagation();
        });

        function next_quest() {
            if (retire > 0) {
                $.ajax({
                    url: "{{ route('triv_in.retire') }}",
                    headers: {
                        'X-CSRF-TOKEN': $("#token").val()
                    },
                    type: 'post',
                    dataType: 'json',
                    data: {
                        val_rt: 0,
                        nm_quest: nm_quest,
                        id_triv: {{ $id_triv }}
                    },
                    success: function(data) {
                        if (data['van'] == 1) {
                            $('#mdl_retire').hide();
                            $('#quest').html(data['questions']);
                            $('#r_opc_a').html(data['answers'][0]);
                            $('#r_opc_a').data('vrep', data['answers'][0]);
                            $('#r_opc_b').html(data['answers'][1]);
                            $('#r_opc_b').data('vrep', data['answers'][1]);
                            $('#r_opc_c').html(data['answers'][2]);
                            $('#r_opc_c').data('vrep', data['answers'][2]);
                            $('#r_opc_d').html(data['answers'][3]);
                            $('#r_opc_d').data('vrep', data['answers'][3]);
                            $('#num_qt').html(data['nm_qt']);
                            xyz = data['xyz_w'][1];
                            afo = data['xyz_w'][2];
                            nm_quest = data['nm_qt'];
                            tim_end = 20;
                            retire = 0;
                            start_cont();
                            console.log(data['answers']);
                        }
                    },
                });
            }
        }

        function start() {
            $.ajax({
                url: "{{ route('triv_in.start') }}",
                headers: {
                    'X-CSRF-TOKEN': $("#token").val()
                },
                type: 'post',
                dataType: 'json',
                data: {
                    init: init,
                },
                success: function(data) {
                    if (data['van'] == 1) {
                        init = 1;
                        s_live = 0;
                        refresh_cmnd();
                    }
                },
            });
        }

        $(".comond").click(function(e) {
            if (use_half == 0) {
                $.ajax({
                    url: "{{ route('triv_in.c_half') }}",
                    headers: {
                        'X-CSRF-TOKEN': $("#token").val()
                    },
                    type: 'post',
                    dataType: 'json',
                    data: {},
                    success: function(data) {
                        if (data['van'] == 3) {
                            hide_r();
                            consult_cm();
                        }
                    },
                });
            }
        });

        function end_time() {
            $.ajax({
                url: "{{ route('triv_in.end_time') }}",
                headers: {
                    'X-CSRF-TOKEN': $("#token").val()
                },
                type: 'post',
                dataType: 'json',
                data: {
                    nm_quest: nm_quest
                },
                success: function(data) {
                    if (data['van'] == 0) {
                        if (penalty == 1) {
                            $(".msgPty").show();
                        } else {
                            $(".msgTime").show();
                        }
                        show_over();
                    }
                }
            });
        }
    </script>
@endsection
