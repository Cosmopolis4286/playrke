@extends('layouts.app_rke')
<title>El Poder del Saber - PlayRKE</title>
@section('styles')
    <link rel="stylesheet" href="{{ asset('css/styles_triv.css') }}">
@endsection
<style>
    body {
        background: url(../../img/body/space.png);
    }
</style>
@section('content')
    <div id="container_fnd_triv">
        <div id="container_triv">
            <div id="loading_screen">
                <img src="{{ asset('img/spn.svg') }}">
            </div>
            <div id="mdl_wn_triv">
                <div class="modal-header">
                    <h5 class="modal-title mt-n1">Información del Juego</h5>
                    <div class="close_rmx"><i class="fa-solid fa-xmark"></i></div>
                </div>
                <div class="modal-body">
                    <div id="tx_wins">
                        <div class="mt-n2">Total de Ganadores:
                            <span id="cnt_wns"></span>
                        </div>
                    </div>
                    <small id="com_bin">Comprobando si hay más ganadores . . .</small>
                    <div id="conten_wns" class="row"></div>
                    <div id="sin_wn" style="display: none; flex-direction:column">
                        <span class="tx_swn_bg mt-1">¡Upps, no hubó ganadores!</span>
                        <small class="txs_swn_bg">El jackpot se sumará a una próxima ronda</small>
                    </div>
                </div>
                <hr class="line_mdl">
                <div class="cont_bg_f mt-n2 mb-2">
                    <button type="button" class="col-4 btn btn-primary close_rm">Continuar</button>
                </div>
            </div>
            <div id="content_gm_triv">
                <div id="top_info_triv">
                    <div class="opc_cont_triv">
                        <div class="subcontent_items_triv">
                            <div class="cont_label_inp_triv">
                                <div class="items_triv"></div>
                                <div class="tx_gm_triv">jackpot Acumulado</div>
                            </div>
                            <div class="cont_items_inp_triv">
                                <div class="items_triv" onclick="play_pause()">
                                    <i class="fas fa-solid fa-music"></i>
                                </div>
                                <div class="inp_inf_triv">
                                    <input type="checkbox" id="ca_mon">
                                    <label for="ca_mon"></label>
                                    @if ($trivia->coin == 1)
                                        {{ number_format($trivia->jackpot, 2) }}
                                        <small class="ml-1 sym_mo">USDT</small>
                                    @elseif($trivia->coin == 2)
                                        <span id="b_bs">
                                            <small class="sym_mo">US$</small>
                                            {{ number_format($trivia->jackpot / $CommerceExchangeRate, 2) }}
                                        </span>
                                        <span id="b_usd">
                                            <small class="sym_mo">Bs&#160</small>
                                            {{ number_format($trivia->jackpot, 2) }}
                                        </span>
                                    @else
                                        <small class="mr-1 sym_mo">{{ $wallet->name }}</small>
                                        {{ number_format($trivia->jackpot, 2) }}
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="subcontent_items_triv">
                            <div class="cont_label_inp_triv">
                                <div class="items_triv"></div>
                                <div class="tx_gm_triv">Aun en Juego</div>
                            </div>
                            <div class="cont_items_inp_triv">
                                <div class="items_triv">
                                    <i class="fas fa-cog"></i>
                                </div>
                                <div class="inp_inf_triv">
                                    <span id="players">{{ $count_players }} PJ</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="img_pds_triv" class="cold">
                        <img src="{{ asset('img/pds.png') }}">
                    </div>
                    <div class="time_one timer"></div>
                    <div class="opc_cont_triv">
                        <div class="subcontent_items_triv">
                            <div class="cont_label_inp_triv">
                                <div class="tx_gm_triv">Ganadores</div>
                                <div class="items_triv"></div>
                            </div>
                            <div class="cont_items_inp_triv">
                                <div class="inp_inf_triv">
                                    <span class="win_sn count_wn">
                                        @if ($count_winners == 0)
                                            SIN DEFINIR
                                        @else
                                            <i class="mr-1 icon_inc fa-solid fa-trophy"></i>
                                            <span class="winners">{{ $count_winners }}</span>
                                            <i class="ml-1 icon_inc fa-solid fa-trophy"></i>
                                        @endif
                                    </span>
                                </div>
                                <div class="c_jump items_triv c_act resp" data-block='1'>
                                    <i class="fas fa-frog"></i>
                                    <div class="jump num_cdin">{{ $trivia->c_jump }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="subcontent_items_triv">
                            <div class="cont_label_inp_triv">
                                <div class="tx_gm_triv">Cupos Disponibles</div>
                                <div class="items_triv"></div>
                            </div>
                            <div class="cont_items_inp_triv">
                                <div class="inp_inf_triv">
                                    <span id="c_cups">
                                        @if ($trivia->start == 0)
                                            •••
                                        @else
                                            {{ $count_cupos }}
                                        @endif
                                    </span>
                                </div>
                                <div class="items_triv c_act comond" data-block='1'>
                                    <i class="fas fa-balance-scale"></i>
                                    <div class="half num_cdin">{{ $trivia->c_half }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="container_quest_answer">
                    <div id="container_quest">
                        <div class="show_gm @if ($trivia->lose_win == 0) wl_show  @else wl_hide @endif">
                            <div class="tx_qst_tiv">
                                <div class="time_gl timer"></div>
                                <div id="content_tx_quest">
                                    <div class="sym_int_lft">¿</div>
                                    @if ($trivia->start == 0)
                                        <button id="start_triv" type="button">iniciar</button>
                                    @endif
                                    <div id="cont_quest">
                                        <span id="quest_triv">
                                            @if ($trivia->start == 1)
                                                {{ $question }}
                                            @endif
                                        </span>
                                    </div>
                                    <div class="sym_int_rgt">?</div>
                                </div>
                                <div class="time_gr timer"></div>
                            </div>
                        </div>
                        <div class="show_loser @if ($trivia->lose_win > 1) wl_show  @else wl_hide @endif">
                            <div class="tx_qst_tiv">
                                <div class="qinc">
                                    <i class="mr-1 icon_inc fa-regular fa-circle-xmark"></i>
                                    <span class="msgTime @if ($trivia->lose_win == 3) wl_show @else wl_hide @endif">
                                        Se acabo el tiempo de respuesta</span>
                                    <span class="msgQInc @if ($trivia->lose_win == 4) wl_show @else wl_hide @endif">
                                        Respuesta incorrecta, inténtalo la próxima</span>
                                    <span class="msgLater @if ($trivia->lose_win == 5) wl_show @else wl_hide @endif">
                                        Respondiste muy tarde, no hay más cupos</span>
                                    <span
                                        class="msgWinner @if ($trivia->lose_win == 6) wl_show @else wl_hide @endif">
                                        Se alcanzo el maximo de ganadores</span>
                                    <i class="ml-1 icon_inc fa-regular fa-circle-xmark"></i>
                                </div>
                            </div>
                        </div>
                        <div class="show_win @if ($trivia->lose_win == 1) wl_show  @else wl_hide @endif">
                            <div class="tx_wn_triv">
                                <i class="mr-1 icon_inc fa-solid fa-trophy"></i>
                                <span id="msg_win">Felicidades Ganaste</span>
                                <i class="ml-1 icon_inc fa-solid fa-trophy"></i>
                            </div>
                        </div>
                    </div>
                    <div id="cont_btn_img_quest">
                        <div class="timer_movil timer">20</div>
                        @if ($trivia->start == 1)
                            <div id="cont_view_img_quest" data-block="0">
                                <span id="tx_view_img_quest">ver pista</span>
                                <i class="fa-solid fa-images"></i>
                            </div>
                        @else
                            <div id="cont_view_img_quest_tx">PlayRKE</div>
                        @endif
                        <div class="timer_movil timer">20</div>
                    </div>
                    <div id="cont_answer_img">
                        @if ($trivia->start == 1)
                            <div class="cont_img_quest_movil" style="display: none">
                                @if ($img == '')
                                    <img class="img_quest_triv" src="{{ asset('img/spc.png') }}">
                                @else
                                    <img class="img_quest_triv" src="{{ asset('imgSystem/imgquestions/' . $img) }}">
                                @endif
                                <img class="m_wt" src="{{ asset('img/mw.png') }}">
                            </div>
                        @endif
                        @if ($trivia->lose_win == 0 || $trivia->lose_win > 1)
                            <div class="container_opc_answer">
                                <button class="content_answer resp" id="opc_a_triv">
                                    <span>A.</span>
                                    <div id="r_opc_a" class="show_gm opc_rt">
                                        @if ($trivia->start == 1 && $trivia->lose_win == 0)
                                            {{ $answers['0'] }}
                                        @endif
                                    </div>
                                </button>
                                <button class="content_answer resp" id="opc_c_triv">
                                    <span class="q_cc">C.</span><span class="q_cb">B.</span>
                                    <span id="r_opc_c" class="show_gm opc_rt">
                                        @if ($trivia->start == 1 && $trivia->lose_win == 0)
                                            {{ $answers['2'] }}
                                        @endif
                                    </span>
                                </button>
                            </div>
                            <div class="cont_img_quest">
                                <div
                                    class="msgQInc msgTime @if ($trivia->status == 3 && $numpp < 7 && $trivia->lose_win > 1) wl_show @else wl_hide @endif">
                                    <button class="resp" id="b_lfe">
                                        @if ($lives == null)
                                            <span class="mb-n2" style="font-size: 16.5px;">Comprar vida</span>
                                            <span style="font-size: 14px;">Precio: {{ $b_life }}
                                                {{ $wallet->name }}
                                                <i class="fa-solid fa-heart-pulse"></i>
                                            </span>
                                        @else
                                            <span>
                                                <i class="fa-solid fa-shield-heart"></i>
                                                Usar vida
                                                <i class="fa-solid fa-shield-heart"></i>
                                            </span>
                                        @endif
                                    </button>
                                </div>
                                @if ($trivia->start == 0)
                                    <img class="img_quest_triv" src="{{ asset('img/spc.png') }}">
                                @else
                                    @if ($img == '')
                                        <img class="img_quest_triv" src="{{ asset('img/spc.png') }}">
                                    @else
                                        <img class="img_quest_triv" src="{{ asset('imgSystem/imgquestions/' . $img) }}">
                                    @endif
                                @endif
                                <img class="m_wt" src="{{ asset('img/mw.png') }}">
                                <div class="n_quest num_qst"><span class="num_qt">{{ $numpp }}</span>/10</div>
                            </div>
                            <div class="container_opc_answer">
                                <button class="content_answer resp" id="opc_b_triv">
                                    <span class="q_cc">B.</span><span class="q_cb">C.</span>
                                    <span id="r_opc_b" class="show_gm opc_rt">
                                        @if ($trivia->start == 1 && $trivia->lose_win == 0)
                                            {{ $answers['1'] }}
                                        @endif
                                    </span>
                                </button>
                                <button class="content_answer resp" id="opc_d_triv">
                                    <span>D.</span>
                                    <span id="r_opc_d" class="show_gm opc_rt">
                                        @if ($trivia->start == 1 && $trivia->lose_win == 0)
                                            {{ $answers['3'] }}
                                        @endif
                                    </span>
                                </button>
                            </div>
                        @endif
                        @if ($trivia->lose_win == 1)
                            <div class="cont_img_wn cold">
                                <img id="img_win_lft" class="img_quest_triv" src="{{ asset('img/arc.jpg') }}">
                                <img class="m_wt" src="{{ asset('img/mw.png') }}">
                                <button class="cont_tx_view_info_triv">
                                    <div class="tx_view_info_triv">¡Ver Información!</div>
                                </button>
                            </div>
                            <div class="cont_inf_wn_triv">
                                <div style="font-size: 20.5px; color:#0074e0;">
                                    Ranking Pos. <span id="num_rank">{{ $rank }}</span>
                                </div>
                                <div style="font-size: 17.5px; color:#0052cc; margin-top: -3.4%">
                                    TIEMPO TOTAL <span id="tm_end_win">{{ $end_gm }}</span> <small>SEG</small>
                                </div>
                                <div style="margin:0 auto;">
                                    <button id="b_united">Unirse a otro juego 15% OFF</button>
                                </div>
                                <div class="mt-1 text_prizes">
                                    Los premios se envian al final del juego
                                </div>
                            </div>
                            <div class="cont_img_wn cold">
                                <img class="img_quest_triv" src="{{ asset('img/arc.jpg') }}">
                                <img class="m_wt" src="{{ asset('img/mw.png') }}">
                                <button class="cont_tx_view_info_triv">
                                    <div class="tx_view_info_triv">¡Ver Información!</div>
                                </button>
                            </div>
                        @endif
                    </div>
                    <div class="nm_quest_end num_qst"><span class="num_qt">{{ $numpp }}</span>/10</div>
                </div>
                <audio id="track">
                    <source src="{{ asset('sounds/trivia/' . $sound_trv[0]->file) }}" type="audio/mp3">
                </audio>
                <audio id="s_correct">
                    <source src="{{ asset('sounds/trivia/' . $sound_trv[1]->file) }}" type="audio/mp3">
                </audio>
                <audio id="s_inct">
                    <source src="{{ asset('sounds/trivia/' . $sound_trv[2]->file) }}" type="audio/mp3">
                </audio>
                <input type="hidden" id="xyz" value="{{ $xyz_w['1'] }}">
                <input type="hidden" id="afo" value="{{ $xyz_w['2'] }}">
            </div>
        </div>
    @endsection
    @section('scripts')
        <script>
            var start_val = {{ $trivia->start }},
                lose_win = {{ $trivia->lose_win }},
                united = {{ $dist }},
                rep_s = '',
                win = 0,
                triv_sts = {{ $trivia->status }},
                half_ck = {{ $trivia->c_half_use }},
                jump_ck = 0,
                half_r = {{ $trivia->c_half }},
                jump_r = {{ $trivia->c_jump }},
                xyz = $("#xyz").val(),
                afo = $("#afo").val(),
                num_quest = {{ $numpp }},
                track = document.getElementById('track'),
                s_inct = document.getElementById('s_inct'),
                s_correct = document.getElementById('s_correct'),
                screen = $('#loading_screen'),
                tm_quest = {{ $contador }},
                play_sound = false,
                c_desc = {{ $van_time }};
            const options = ['a', 'b', 'c', 'd'];
            if (start_val == 1) {
                resp_a = "<?php echo $answers['0']; ?>",
                    resp_b = "<?php echo $answers['1']; ?>",
                    resp_c = "<?php echo $answers['2']; ?>",
                    resp_d = "<?php echo $answers['3']; ?>";
            } else {
                resp_a = resp_b = resp_c = resp_d = '';
            }

            if (c_desc == 1 && lose_win == 0) {
                start_cont();
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
            var title_gn = document.title;
            window.onblur = function() {
                document.title = "¡Hey, vuelve aquí!";
            }
            window.onfocus = function() {
                document.title = title_gn;
            }

            function play_pause() {
                if (play_sound == false) {
                    track.play();
                    play_sound = true;
                } else {
                    track.pause();
                    play_sound = false;
                }
            }

            function sound_inct() {
                s_inct.play();
                track.pause();
                play_sound = false;
            }

            function refresh_cmnd() {
                if (half_ck == num_quest) {
                    hide_r();
                }
                $.each($('.c_act'), function() {
                    if (start_val == 1) {
                        $('#b_track').css('pointer-events', 'auto');
                        $('#b_track').css('cursor', 'pointer');
                    }
                    if (start_val == 0) {
                        $(this).data("block", '1');
                        $(this).css('cursor', 'not-allowed');
                        $('#b_track').css('pointer-events', 'none');
                        $('#b_track').css('cursor', 'not-allowed');
                        console.log(start_val, 'sss', lose_win);

                    }
                    if (start_val == 1 && lose_win == 0 && num_quest < 6) {
                        console.log(start_val, 'lose', lose_win);
                        $(this).data("block", '0');
                        $(this).css('pointer-events', 'auto');
                        $(this).css('cursor', 'pointer');
                    }
                    if (num_quest >= 6 || lose_win > 0) {
                        $(this).data("block", '1');
                        $(this).css('cursor', 'not-allowed');
                    }
                    if (num_quest > 6) {
                        $("#b_lfe").css('display', 'none');
                    }
                });
            }

            if (@json($trivia->coin == 1)) {
                var ruta = "{{ route('trivia.rooms_usdt') }}";
            } else {
                var ruta = "{{ route('trivia.rooms') }}";
            }

            function return_rm(rtime) {
                //inf_wn();
                if (rtime == 0) {
                    rtime = 16000;
                } else {
                    rtime = 8000;
                }
                localStorage.clear();
                // setTimeout(function() {
                //     window.location.href = ruta;
                // }, rtime);
            }

            $(".close_rmx").click(function() {
                $("#mdl_wn_triv").hide();
            });
            $(".close_rm").click(function() {
                c_inf = 0;
                //inf_wn();
            });

            $(".cont_tx_view_info_triv").click(function() {
                $("#mdl_wn_triv").show();
            });

            $("#cont_view_img_quest").click(function() {
                if ($('#cont_view_img_quest').data("block") == 0) {
                    $(".cont_img_quest_movil").show();
                    $('#cont_view_img_quest').data("block", '1');
                    $('#tx_view_img_quest').html('Cerrar');
                } else {
                    $(".cont_img_quest_movil").hide();
                    $('#cont_view_img_quest').data("block", '0');
                    $('#tx_view_img_quest').html('Ver Pista');
                }
            });

            ///Consultar Info
            function consult() {
                let init = setInterval(function() {
                    if (triv_sts == 4) {
                        clearInterval(init);
                    }
                    $.ajax({
                        url: "{{ asset('') }}trivia/update_info/" + @json($trivia->id),
                        headers: {
                            'X-CSRF-TOKEN': $("#5K3_70K").val()
                        },
                        type: 'GET',
                        dataType: 'json',
                        beforeSend: function() {
                            screen.hide();
                        },
                        success: function(data) {
                            if (data['wns'] > 0) {
                                $(".win_sn").hide();
                                $(".count_wn").show();
                                $(".winners").html(data['wns']);
                            }
                            if (data['snd'] >= 4) {
                                triv_sts = data['snd'];
                                console.log(data['winns']);
                                view_wns(data['winns']);
                                return_rm();
                            }
                            $("#c_cups").html(data['cups']);
                            $("#players").html(data['pj'] + ' PJ');
                        },
                        error: function() {}
                    });
                }, 4000);
            }
            window.onload = consult(), refresh_cmnd();

            ///Consultar Info
            ///Insertar valores
            $("#start_triv").click(function() {
                $.ajax({
                    url: "{{ route('trivia.start') }}",
                    headers: {
                        'X-CSRF-TOKEN': $("#5K3_70K").val()
                    },
                    type: 'post',
                    dataType: 'json',
                    data: {
                        id: @json($trivia->id),
                    },
                    beforeSend: function() {},
                    success: function(data) {
                        play_pause();
                        start_val = 1;
                        $("#start_triv").hide();
                        $("#quest_triv").html(data['pr']);
                        if (data['img'] == "") {
                            $(".img_quest_triv").attr('src', "{{ asset('') }}" +
                                'template/dist/img/d.png');
                        } else {
                            $(".img_quest_triv").attr('src', "{{ asset('') }}" +
                                'imgSystem/imgquestions/' + data['img']);
                        }
                        resp_a = data['question'][0];
                        $("#r_opc_a").html(data['question'][0]);
                        resp_b = data['question'][1];
                        $("#r_opc_b").html(data['question'][1]);
                        resp_c = data['question'][2];
                        $("#r_opc_c").html(data['question'][2]);
                        resp_d = data['question'][3];
                        $("#r_opc_d").html(data['question'][3]);
                        refresh_cmnd();
                        start_cont();
                    },
                    error: function() {}
                });
            });
            ///Insertar valores

            function start_cont() {
                let init = setInterval(function() {
                    $(".timer").html(tm_quest);
                    tm_quest--;
                    if (tm_quest <= 0 && lose_win == 0) {
                        clearTimeout(stop);
                        $("#b_lfe").show();
                        end_TimeQ();
                        $(".timer").html(0);
                    }
                    if (lose_win > 0) {
                        clearInterval(init);
                    }
                }, 1000);
            }

            $(".c_jump").click(function() {
                if (jump_ck == 0 && jump_r > 0 && $(this).data("block") == '0') {
                    jump_ck = 1;
                }
            })
            $('#opc_a_triv').click(function() {
                rep_s = resp_a;
            })
            $('#opc_b_triv').click(function() {
                rep_s = resp_b;
            })
            $('#opc_c_triv').click(function() {
                rep_s = resp_c;
            })
            $('#opc_d_triv').click(function() {
                rep_s = resp_d;
            })

            $(".resp").click(function() {
                if (rep_s != '' || jump_ck == 1 &&
                    ($('#r_opc_d').html() != '' && start_val == 1 && lose_win == 0)) {
                    $.ajax({
                        url: "{{ route('trivia.respuestas') }}",
                        headers: {
                            'X-CSRF-TOKEN': $("#5K3_70K").val()
                        },
                        type: 'post',
                        dataType: 'json',
                        data: {
                            resp: rep_s,
                            id: @json($trivia->id),
                            vc_jump: jump_ck
                        },
                        beforeSend: function() {
                            half_ck = 0;
                            screen.show();
                        },
                        success: function(data) {
                            rep_s = '';
                            //Revive+C_Quest
                            if (data['van'] == 0 || data['van'] == 5 || data['van'] == 6) {
                                if (data['van'] == 6) {
                                    jump_ck = 2;
                                }
                                $('.opc_rt').show();
                                xyz = data['xyz_w'][1], afo = data['xyz_w'][2], tm_quest = 25;
                                if (data['rv'] == 1) {
                                    lose_win = 0;
                                    start_cont();
                                    $(".info-box-number").html((data['mn']));
                                    $(".timer").html('');
                                    $(".show_loser").hide();
                                    $(".show_gm").show();
                                    $("#b_lfe").hide();
                                }
                                if (data['van'] == 5) {
                                    console.log('soy yo', data['pr']);
                                }
                                $("#quest_triv").html(data['pr']);

                                options.forEach(option => {
                                    const respKey = 'resp_' + option;
                                    window[respKey] = data['question'][options.indexOf(option)];
                                    $(`#r_opc_${option}`).html(window[respKey]);
                                });

                                // resp_a = data['question'][0];
                                // $("#r_opc_a").html(data['question'][0]);
                                // resp_b = data['question'][1];
                                // $("#r_opc_b").html(data['question'][1]);
                                // resp_c = data['question'][2];
                                // $("#r_opc_c").html(data['question'][2]);
                                // resp_d = data['question'][3];
                                // $("#r_opc_d").html(data['question'][3]);


                                // $('.resp').prop("disabled", false);
                                $(".num_qst").html(data['numpp'] + '/10');
                                num_quest = data['numpp'];
                                if (data['img'] == "") {
                                    $(".img_quest_triv").attr('src', "{{ asset('') }}" +
                                        'template/dist/img/d.png');
                                } else {
                                    $(".img_quest_triv").attr('src', "{{ asset('') }}" +
                                        'imgSystem/imgquestions/' +
                                        data[
                                            'img']);
                                }
                                s_correct.play();
                                consult_cm();
                            } else if (data['van'] == 1) {
                                //Ganador
                                lose_win = 1;
                                $(".show_gm").hide();
                                $(".show_win").show();
                                $("#num_rank").html(data['num_rank']);
                                $("#tm_end_win").html(data['tm_end_win']);
                            } else if (data['van'] == 2) {
                                //PerdedorQuest
                                sound_inct();
                                $("#b_lfe").show();
                                lose_win = 4;
                                show_over();
                                $(".msgQInc").show();
                            } else if (data['van'] == 3) {
                                //PerdedorCupos
                                lose_win = 5;
                                show_over();
                                $(".msgLater").show();
                            } else if (data['van'] == 4) {
                                //TopeWinner
                                lose_win = 6;
                                show_over();
                                $(".msgWinner").show();
                            }
                            refresh_cmnd();
                        },
                        error: function() {}
                    });
                }

            });

            // function consult_cm() {
            //     if (half_ck > 0 && half_r > 0) {
            //         half_r--;
            //         $(".half").html(half_r);
            //     }
            //     if (jump_ck == 2 && jump_r > 0) {
            //         jump_r--;
            //         $(".jump").html(jump_r);
            //         jump_ck = 0;
            //     }
            // }

            function consult_cm() {
                if (half_ck > 0 && half_r > 0) $(".half").html(--half_r);

                if (jump_ck === 2 && jump_r > 0) {
                    $(".jump").html(--jump_r);
                    jump_ck = 0;
                }
            }

            $(".comond").click(function() {
                if (half_ck == 0 && $(this).data("block") == 0) {
                    $.ajax({
                        url: "{{ route('trivia.comond_rest') }}",
                        headers: {
                            'X-CSRF-TOKEN': $("#5K3_70K").val()
                        },
                        type: 'post',
                        dataType: 'json',
                        data: {
                            id: @json($trivia->id),
                            cdin_h: $(this).data('cdin_h')
                        },
                        beforeSend: function() {
                            half_ck = num_quest;
                        },
                        success: function(data) {
                            if (data['van'] == 1) {
                                hide_r();
                                consult_cm();
                            }
                        },
                        error: function() {}
                    });
                }
            });

            // function hide_r() {
            //     answers = [];
            //     if (xyz == resp_a || afo == resp_a) {
            //         $("#r_opc_a").hide();
            //         answers.push('a');
            //     }
            //     if (xyz == resp_b || afo == resp_b) {
            //         $("#r_opc_b").hide();
            //         answers.push('b');
            //     }
            //     if (xyz == resp_c || afo == resp_c) {
            //         $("#r_opc_c").hide();
            //         answers.push('c');
            //     }
            //     if (xyz == resp_d || afo == resp_d) {
            //         $("#r_opc_d").hide();
            //         answers.push('d');
            //     }
            //     $.each(answers, function(ind, elem) {
            //         $('#opc_' + elem + '_triv').prop("disabled", true);
            //     })
            // }
            function hide_r() {
                options.forEach(option => {
                    if (xyz == window['resp_' + option] || afo == window['resp_' + option]) {
                        $(`#r_opc_${option}`).hide();
                        $(`#opc_${option}_triv`).prop("disabled", true);
                    }
                });
            }

            function show_over() {
                track.pause();
                lose_win = 4;
                $(".show_gm").hide();
                $(".show_loser").show();
            }

            function end_TimeQ() {
                $.ajax({
                    url: "{{ route('trivia.liveUp') }}",
                    headers: {
                        'X-CSRF-TOKEN': $("#5K3_70K").val()
                    },
                    type: 'post',
                    dataType: 'json',
                    data: {
                        id: @json($trivia->id),
                    },
                    beforeSend: function() {},
                    success: function(data) {
                        if (data['van'] == 1) {
                            $(".show_gm").hide();
                            $(".show_loser").show();
                            $(".msgTime").show();
                        }
                    },
                    error: function() {}
                });
            }

            if (united == null) {
                $("#b_united").click(function(e) {
                    $.ajax({
                        url: "{{ route('trivia.united') }}",
                        headers: {
                            'X-CSRF-TOKEN': $("#5K3_70K").val()
                        },
                        type: 'post',
                        dataType: 'json',
                        data: {
                            id: @json($trivia->id),
                        },
                        beforeSend: function() {},
                        success: function(data) {
                            if (data['van'] == 1) {
                                $(".show_gm").hide();
                                $(".msgTime").show();
                                $(".show_loser").show();
                                united = 1;
                            }
                        },
                        error: function() {}
                    });
                });
            }

            function view_wns(arr_info) {
                ar_size = arr_info['name'].length;
                if (ar_size == 0) {
                    $("#tx_wins").hide();
                    $('#sin_wn').show();
                    return_rm(1);
                } else {
                    $('#cnt_wns').html(ar_size);
                    content = '';
                    if ($('#conten_wns').html() == '') {
                        date = new Date(arr_info['hour'][0] * 1000);
                        hour = date.getHours();
                        minutes = date.getMinutes();
                        if (hour < 10) {
                            hour = '0' + hour;
                        }
                        if (date.getMinutes() < 10) {
                            minutes = '0' + minutes;
                        }
                        $('#tm_close_win').html(hour + ':' + minutes);
                    }
                    for (i = 0; i < ar_size; i++) {
                        img = arr_info['foto'][i];
                        if (img == null) {
                            img = 'rke.png';
                        }
                        data =
                            '<div class="col-4 mt-3" style="display: grid; justify-content:center;">' +
                            '<a href="##" class="cont_img_uswin" onclick="view_ctn(' + arr_info['ctns'][i] +
                            ')" title="Ver Cartón">' +
                            '<i class="eyevwctn fa-solid fa-eye"></i>' + '<img class="img_us" src="' +
                            '{{ asset('') }}imgSystem/imgUsers/' + img + '">' +
                            '</a>' + '<div class="center_tx_bg name_us fw-bold badge bg-success">' +
                            arr_info['name'][i] + '</div>' +
                            '<div class="center_tx_bg name_us fw-bold mont_bg badge bg-navy">' +
                            arr_info['gain'][i] + '&nbsp' + @json($wallet->name) + '</div>' + '</div>';
                        content += data;
                    }
                    $("#tx_wins").show();
                    $('#conten_wns').html(content);
                }
                if (win == 2) {
                    $('#com_bin').hide();
                    return_rm(0);
                }
                $("#mdl_wn_triv").show();
            }
        </script>
    @endsection
