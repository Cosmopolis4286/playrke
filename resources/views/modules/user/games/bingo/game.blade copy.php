@extends('players.layouts.app_bg')
<title>BingoCash</title>
@section('content')
    <div id="container_fnd_bingo">
        <div id="content_gm_bg">
            <div class="row">
                <div id="container_sup_chips" class="mt-2">
                    <div id="cont_chips">
                        <div class="cont_loader">
                            <div class="lds-dual-ring"></div>
                        </div>
                    </div>
                    <div id="cont_tx_check_bing">
                        <div id="tx_check_bing">Comprobando si hay "BINGO"</div>
                    </div>
                </div>
                <div class="bing_cont_bt">
                    <div id="mdl_view_ctn">
                        <div class="modal-header">
                            <h5 class="modal-title mt-n1">Cartón</h5>
                            <div class="btn_close_bg_md close_ctn">X</div>
                        </div>
                        <div class="cont_ctn_gm notranslate" translate="no">
                            <div class="b_go">
                                <div class="tx_bg br_tl_bg">B</div>
                                <div class="num_bg">
                                    <span id="ct_v0" class="bt_num_vw"></span>
                                </div>
                                <div class="num_bg">
                                    <span id="ct_v1" class="bt_num_vw"></span>
                                </div>
                                <div class="num_bg">
                                    <span id="ct_v2" class="bt_num_vw"></span>
                                </div>
                                <div class="num_bg">
                                    <span id="ct_v3" class="bt_num_vw"></span>
                                </div>
                                <div class="num_bg br_bl_bg">
                                    <span id="ct_v4" class="bt_num_vw"></span>
                                </div>
                            </div>
                            <div class="b_go">
                                <div class="tx_bg">I</div>
                                <div class="num_bg">
                                    <span id="ct_v5" class="bt_num_vw"></span>
                                </div>
                                <div class="num_bg">
                                    <span id="ct_v6" class="bt_num_vw"></span>
                                </div>
                                <div class="num_bg">
                                    <span id="ct_v7" class="bt_num_vw"></span>
                                </div>
                                <div class="num_bg">
                                    <span id="ct_v8" class="bt_num_vw"></span>
                                </div>
                                <div class="num_bg">
                                    <span id="ct_v9" class="bt_num_vw"></span>
                                </div>
                            </div>
                            <div class="b_go">
                                <div class="tx_bg">N</div>
                                <div class="num_bg">
                                    <span id="ct_v10" class="bt_num_vw"></span>
                                </div>
                                <div class="num_bg">
                                    <span id="ct_v11" class="bt_num_vw"></span>
                                </div>
                                <div class="num_bg">
                                    <img class="ctn_lgo elem_block" src="{{ asset('img/logo.png') }}">
                                </div>
                                <div class="num_bg">
                                    <span id="ct_v12" class="bt_num_vw"></span>
                                </div>
                                <div class="num_bg">
                                    <span id="ct_v13" class="bt_num_vw"></span>
                                </div>
                            </div>
                            <div class="b_go">
                                <div class="tx_bg">G</div>
                                <div class="num_bg">
                                    <span id="ct_v14" class="bt_num_vw"></span>
                                </div>
                                <div class="num_bg">
                                    <span id="ct_v15" class="bt_num_vw"></span>
                                </div>
                                <div class="num_bg">
                                    <span id="ct_v16" class="bt_num_vw"></span>
                                </div>
                                <div class="num_bg">
                                    <span id="ct_v17" class="bt_num_vw"></span>
                                </div>
                                <div class="num_bg">
                                    <span id="ct_v18" class="bt_num_vw"></span>
                                </div>
                            </div>
                            <div class="b_go">
                                <div class="tx_bg br_tr_bg">O</div>
                                <div class="num_bg">
                                    <span id="ct_v19" class="bt_num_vw"></span>
                                </div>
                                <div class="num_bg">
                                    <span id="ct_v20" class="bt_num_vw"></span>
                                </div>
                                <div class="num_bg">
                                    <span id="ct_v21" class="bt_num_vw"></span>
                                </div>
                                <div class="num_bg">
                                    <span id="ct_v22" class="bt_num_vw"></span>
                                </div>
                                <div class="num_bg br_br_bg">
                                    <span id="ct_v23" class="bt_num_vw"></span>
                                </div>
                            </div>
                        </div>
                        <div id="cont_nm_uctn">
                            <span id="name_pj_view_ctn"></span>
                        </div>
                    </div>
                    <div id="win_mdl">
                        <div class="modal-header">
                            <h5 class="modal-title mt-n1">Información del Juego</h5>
                            <div class="btn_close_bg_md close_rm">X</div>
                        </div>
                        <div class="modal-body">
                            <div id="tx_wins">
                                <div class="mt-n2">Total de Ganadores:
                                    <span id="cnt_wns"></span>
                                </div>
                                <div>
                                    con la ficha&nbsp
                                    <img id="chp_wn_bing">
                                </div>
                                <div style="display: flex; align-items:center">
                                    a las&nbsp<i class="fa-regular fa-clock"></i>
                                    <span id="tm_close_win"></span>
                                </div>
                            </div>
                            <div id="com_bin">Comprobando todos los "BINGO" . . .</div>
                            <div id="conten_wns" class="row"></div>
                            <div id="sin_wn">
                                <span class="tx_swn_bg mt-1">¡Upps, no hubó ganadores!</span>
                                <small class="txs_swn_bg">El jackpot se sumará a una próxima ronda</small>
                            </div>
                        </div>
                        <hr class="line_mdl">
                        <div class="cont_bg_f mt-n2 mb-2">
                            <button type="button" class="col-4 btn btn-primary close_rm">Continuar</button>
                        </div>
                    </div>
                    <div class="cont_bt_rm">
                        <div class="content_cfe_bg">
                            <img class="cfe_bg elem_block" src="{{ asset('img/cofre.png') }}">
                        </div>
                        <div id="cont_tx_bot">
                            <div id="cont_bot_bg_rm">
                                <span class="bot_bg_rm">
                                    @if ($room->jackpot_acumulativo == 1)
                                        jackpot:
                                    @elseif($room->jackpot_acumulativo == 0)
                                        Premio:
                                    @endif
                                </span>
                                <div id="cont_icon_sound">
                                    <i id="control_sound" class="fa-solid fa-volume-xmark"></i>
                                </div>
                            </div>
                            <span class="num_bt_rm">
                                {{ $div->Simbologia }}{{ number_format($room->jackpot, 2) }}
                            </span>
                        </div>
                    </div>
                    <div id="tx_tm_stop"></div>
                    <div class="cont_img_rm">
                        <div class="cont_img_bg">
                            <img class="img_bg elem_block"src='{{ asset('/img/bg/sf_estelar.png') }}'>
                            <div class="md_gm_rm">Modo: Cartón Lleno</div>
                        </div>
                    </div>
                </div>
                <div id="container_list_chips">
                    <div class="arrow_list_chips" data-move="1">
                        <i id="arrow_left" class="fa-regular fa-circle-left"></i>
                    </div>
                    <div id="content_list_chips"></div>
                    <div class="arrow_list_chips" data-move="2">
                        <i id="arrow_right" class="fa-regular fa-circle-right"></i>
                    </div>
                </div>
            </div>
            <div class="row mb-3">
                @foreach ($cartons as $ctns)
                    <div class="col-6 col-md-4 col-xl-3 mt-2 notranslate" translate="no">
                        <div class="cont_ctn_gm" data-valctn="0">
                            <div class="b_go">
                                <div class="tx_bg br_tl_bg">B</div>
                                <div class="num_bg">
                                    <button class="bt_num" data-cod="{{ $ctns->id }}"
                                        data-num_ctn="{{ $ctns->b1 }}">{{ $ctns->b1 }}</button>
                                </div>
                                <div class="num_bg">
                                    <button class="bt_num" data-cod="{{ $ctns->id }}"
                                        data-num_ctn="{{ $ctns->b2 }}">{{ $ctns->b2 }}</button>
                                </div>
                                <div class="num_bg">
                                    <button class="bt_num" data-cod="{{ $ctns->id }}"
                                        data-num_ctn="{{ $ctns->b3 }}">{{ $ctns->b3 }}</button>
                                </div>
                                <div class="num_bg">
                                    <button class="bt_num" data-cod="{{ $ctns->id }}"
                                        data-num_ctn="{{ $ctns->b4 }}">{{ $ctns->b4 }}</button>
                                </div>
                                <div class="num_bg br_bl_bg">
                                    <button class="bt_num" data-cod="{{ $ctns->id }}"
                                        data-num_ctn="{{ $ctns->b5 }}">{{ $ctns->b5 }}</button>
                                </div>
                            </div>
                            <div class="b_go">
                                <div class="tx_bg">I</div>
                                <div class="num_bg">
                                    <button class="bt_num" data-cod="{{ $ctns->id }}"
                                        data-num_ctn="{{ $ctns->i1 }}">{{ $ctns->i1 }}</button>
                                </div>
                                <div class="num_bg">
                                    <button class="bt_num" data-cod="{{ $ctns->id }}"
                                        data-num_ctn="{{ $ctns->i2 }}">{{ $ctns->i2 }}</button>
                                </div>
                                <div class="num_bg">
                                    <button class="bt_num" data-cod="{{ $ctns->id }}"
                                        data-num_ctn="{{ $ctns->i3 }}">{{ $ctns->i3 }}</button>
                                </div>
                                <div class="num_bg">
                                    <button class="bt_num" data-cod="{{ $ctns->id }}"
                                        data-num_ctn="{{ $ctns->i4 }}">{{ $ctns->i4 }}</button>
                                </div>
                                <div class="num_bg">
                                    <button class="bt_num" data-cod="{{ $ctns->id }}"
                                        data-num_ctn="{{ $ctns->i5 }}">{{ $ctns->i5 }}</button>
                                </div>
                            </div>
                            <div class="b_go">
                                <div class="tx_bg">N</div>
                                <div class="num_bg">
                                    <button class="bt_num" data-cod="{{ $ctns->id }}"
                                        data-num_ctn="{{ $ctns->n1 }}">{{ $ctns->n1 }}</button>
                                </div>
                                <div class="num_bg">
                                    <button class="bt_num" data-cod="{{ $ctns->id }}"
                                        data-num_ctn="{{ $ctns->n2 }}">{{ $ctns->n2 }}</button>
                                </div>
                                <div class="num_bg">
                                    <img class="ctn_lgo elem_block" src="{{ asset('img/logo.png') }}">
                                </div>
                                <div class="num_bg">
                                    <button class="bt_num" data-cod="{{ $ctns->id }}"
                                        data-num_ctn="{{ $ctns->n3 }}">{{ $ctns->n3 }}</button>
                                </div>
                                <div class="num_bg">
                                    <button class="bt_num" data-cod="{{ $ctns->id }}"
                                        data-num_ctn="{{ $ctns->n4 }}">{{ $ctns->n4 }}</button>
                                </div>
                            </div>
                            <div class="b_go">
                                <div class="tx_bg">G</div>
                                <div class="num_bg">
                                    <button class="bt_num" data-cod="{{ $ctns->id }}"
                                        data-num_ctn="{{ $ctns->g1 }}">{{ $ctns->g1 }}</button>
                                </div>
                                <div class="num_bg">
                                    <button class="bt_num" data-cod="{{ $ctns->id }}"
                                        data-num_ctn="{{ $ctns->g2 }}">{{ $ctns->g2 }}</button>
                                </div>
                                <div class="num_bg">
                                    <button class="bt_num" data-cod="{{ $ctns->id }}"
                                        data-num_ctn="{{ $ctns->g3 }}">{{ $ctns->g3 }}</button>
                                </div>
                                <div class="num_bg">
                                    <button class="bt_num" data-cod="{{ $ctns->id }}"
                                        data-num_ctn="{{ $ctns->g4 }}">{{ $ctns->g4 }}</button>
                                </div>
                                <div class="num_bg">
                                    <button class="bt_num" data-cod="{{ $ctns->id }}"
                                        data-num_ctn="{{ $ctns->g5 }}">{{ $ctns->g5 }}</button>
                                </div>
                            </div>
                            <div class="b_go">
                                <div class="tx_bg br_tr_bg">O</div>
                                <div class="num_bg">
                                    <button class="bt_num" data-cod="{{ $ctns->id }}"
                                        data-num_ctn="{{ $ctns->o1 }}">{{ $ctns->o1 }}</button>
                                </div>
                                <div class="num_bg">
                                    <button class="bt_num" data-cod="{{ $ctns->id }}"
                                        data-num_ctn="{{ $ctns->o2 }}">{{ $ctns->o2 }}</button>
                                </div>
                                <div class="num_bg">
                                    <button class="bt_num" data-cod="{{ $ctns->id }}"
                                        data-num_ctn="{{ $ctns->o3 }}">{{ $ctns->o3 }}</button>
                                </div>
                                <div class="num_bg">
                                    <button class="bt_num" data-cod="{{ $ctns->id }}"
                                        data-num_ctn="{{ $ctns->o4 }}">{{ $ctns->o4 }}</button>
                                </div>
                                <div class="num_bg br_br_bg">
                                    <button class="bt_num" data-cod="{{ $ctns->id }}"
                                        data-num_ctn="{{ $ctns->o5 }}">{{ $ctns->o5 }}</button>
                                </div>
                            </div>
                        </div>
                        <button disabled class="bt_bingo send" data-cod="{{ $ctns->id }}"><span>Bingo</span></button>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @foreach ($sort as $key => $sort)
        <input type="hidden" class="chips" id="{{ $sort->id }}"
            value="{{ $sort->chip }}"data-time="{{ $sort->time }}">
    @endforeach
    <audio muted="true" id="next">
        <source src="{{ asset('sounds/bing/next.mp3') }}" type="audio/mp3">
    </audio>
    <audio id="select">
        <source src="{{ asset('sounds/bing/select.mp3') }}" type="audio/mp3">
    </audio>
    <audio id="winner">
        <source src="{{ asset('sounds/bing/winner.mp3') }}" type="audio/mp3">
    </audio>
@endsection
@section('scripts')
    <script type="text/javascript">
        var arr_list_chips = '';
        window.addEventListener('resize', function(e) {
            view_width = e.currentTarget.innerWidth;
            if (view_width > 745) {
                size_crrsel = 12;
            } else {
                if (view_width < 370) {
                    size_crrsel = 7;
                } else if (view_width >= 370 && view_width <= 745) {
                    size_crrsel = 8;
                }
            }
            view_list_chips(arr_list_chips);
        }, true);

        $(document).on('dragstart', 'img', function(evt) {
            evt.preventDefault();
        });
        document.addEventListener("contextmenu", (e) => {
            e.preventDefault();
            return false;
        });
        document.addEventListener("keydown", (e) => {
            e.preventDefault();
            if (e.keyCode == 123) {
                return false;
            }
        });
        var chip_nw = '';
        var chip_nw_time = '';
        var chips_rest = @json($chips_gm_rest);
        var images = [];
        for (i = 0; i < chips_rest.length; i++) {
            images[i] = new Image();
            images[i].src = '{{ asset('') }}img/bg/chips/' + chips_rest[i] + '.png';
        }

        var room = @json($room->id);
        var songs = 0;
        var init_move = localStorage.getItem("room", room);
        var v_rtn = 0;
        var chp_win = @json($chip_win);
        var c_inf = 0;
        var act_snd = 0;
        var tm_stop = @json($tm_stop);
        var win = 0;
        var audio = document.getElementById('next')
        $(document).ready(function() {
            if (tm_stop > 0) {
                $('.bt_num').prop("disabled", true);
                $('.send').prop("disabled", true);
                penalty();
            } else {
                $('.bt_num').prop("disabled", false);
                $('.send').prop("disabled", false);
            }
        })
        $('body').on('click', function() {
            if (act_snd == 0) {
                $("#cont_icon_sound").html('<i id="control_sound" class="fa-solid fa-volume-high"></i>');
                audio.muted = false;
                act_snd = 1;
            }
        })
        $("#control_sound").click(function() {
            if (act_snd == 1) {
                $("#cont_icon_sound").html('<i id="control_sound" class="fa-solid fa-volume-xmark"></i>');
                audio.muted = true;
                act_snd = 2;
                Toast.fire({
                    icon: 'info',
                    title: '¡Sonido Desactivado!'
                })
            } else if (act_snd == 2) {
                $("#cont_icon_sound").html('<i id="control_sound" class="fa-solid fa-volume-high"></i>');
                audio.muted = false;
                act_snd = 1;
                Toast.fire({
                    icon: 'info',
                    title: '¡Sonido Activado!'
                })
            }
        })
        window.onblur = function() {
            v_rtn = 1;
        }
        window.onfocus = function() {
            if (v_rtn == 1 && win == 0) {
                $.ajax({
                    url: '{{ asset('') }}bingocash/get/chips/' + room,
                    headers: {
                        'X-CSRF-TOKEN': $("#token").val()
                    },
                    method: 'get',
                    dataType: 'json',
                    success: function(data) {
                        v_rtn = 0;
                        van_rchp = refresh = 1;
                        sort(data[0], null);
                        tm_serv = data[2];
                    }
                });
            }
        }
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2650,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });

        var auto_mc = @json($room->automarcar);
        var all_chips = [];
        var arr_ctn = @json($ctns);
        const ar_srt = $('.chips');
        var last_chip = Number(ar_srt[74].getAttribute('data-time'));
        var tm_serv = @json($tm_serv);

        function auto_marc() {
            all_chips = [];
            $.each(ar_srt, function(ind, elem) {
                chp_time = Number(ar_srt[ind].getAttribute('data-time'));
                if (chp_time > 0 && chp_time <= tm_serv && all_chips.includes(ar_srt[ind].value) == false) {
                    all_chips.push(Number(ar_srt[ind].value));
                }
            });
            $(".bt_num").each(function() {
                num_ctn = $(this).data('num_ctn');
                if (all_chips.includes(num_ctn)) {
                    for ($i = 0; $i < all_chips.length; $i++) {
                        if (num_ctn == all_chips[$i]) {
                            $(this).addClass('circle_bg');
                        }
                    }
                }
            });
        }

        if (init_move !== null) {
            for (i = 0; i < arr_ctn.length; i++) {
                comp_r = JSON.stringify(localStorage.getItem("id" + arr_ctn[i]));
                tt = comp_r.split('"').join('');
                nw_a = tt.split(',');
                $(".bt_num").each(function() {
                    for ($i = 0; $i < nw_a.length + 1; $i++) {
                        if ($(this).data('num_ctn') == nw_a[$i] && $(this).attr("data-cod") == arr_ctn[i]) {
                            $(this).addClass('circle_bg');
                        }
                    }
                });
            }
        }

        var chips_gm = @json($lt_chips);
        var arr_list_chips = @json($all_chips);
        var pos_chip = 0;
        var size_crrsel = '';
        var arr_crrsel_chps = []; // En este Array se guardan las Fichas que estan en la vista
        var next_chip_srt = 0;
        var nm_chps = van_srt_run = 0;
        var cslt_van = 0;
        var van_rchp = refresh = generate_chp = 0;
        if (win == 0) {
            if (chips_gm.length > 0) {
                nm_chps = @json($last_ps);
                van_rchp = refresh = 1;
                sort(chips_gm, null);
                view_list_chips(arr_list_chips);
            } else {
                chips_gm = [];
                ar_six_chips();
            }
        }

        //******** Funcion para consultar las fichas que salieron   
        $(".arrow_list_chips").click(function() {
            arr_crrsel_chps = [];
            view_list_chips(arr_list_chips, $(this).data('move'));
        });

        function view_list_chips(arr, arrow) {
            arr_crrsel_chps = [];
            var count_chps = 1;
            var crrsel_chps = 1;
            var info_html = '';

            if (arrow) {
                if (arrow == 1) {
                    if (pos_chip > 0) {
                        pos_chip--;
                    }
                } else {
                    if (arr_crrsel_chps.includes(ar_srt[0].value) == false) {
                        pos_chip++;
                    }
                }
            }
            if (screen.width > 745) {
                size_crrsel = 12;
            } else {
                if (screen.width < 370) {
                    size_crrsel = 7;
                } else if (screen.width >= 370 && screen.width <= 745) {
                    size_crrsel = 8;
                }
            }
            for (i = pos_chip; i < arr.length; i++) {
                if (crrsel_chps <= size_crrsel) {
                    if (count_chps > 8) {
                        elem_html =
                            '<div id="list_chip' + count_chps + '" class="content_num_list">' +
                            '<img src="{{ asset('') }}img/bg/chips/' + arr[i] + '.png" alt="' + arr[i] + '">' +
                            '</div>';
                    } else {
                        elem_html =
                            '<div class="content_num_list">' +
                            '<img src="{{ asset('') }}img/bg/chips/' + arr[i] + '.png" alt="' + arr[i] + '">' +
                            '</div>';
                    }
                    count_chps++;
                    info_html += elem_html;
                    crrsel_chps++;
                    arr_crrsel_chps.push(arr[i]); // Se insertar valor en Array Secundario
                }
            }
            sz_carrsl = arr_crrsel_chps.length;
            console.log(arr_crrsel_chps[0], 'hhh', chips_gm[0]);
            if (arr_crrsel_chps[0] != chips_gm[0]) {
                $('#arrow_left').show();
            } else {
                $('#arrow_left').hide();
            }
            if (arr_crrsel_chps[sz_carrsl - 1] != arr[arr.length - 1]) {
                if (arr_list_chips.length > size_crrsel) {
                    $('#arrow_right').show();
                }
            } else {
                $('#arrow_right').hide();
            }
            $('#content_list_chips').html(info_html);
        }

        function ar_six_chips() {
            if (win == 0 && chp_win == null) {
                van_srt_run = 1;
                let init = setInterval(function() {
                    for (i = nm_chps; i < 75; i++) {
                        if (i > 22 && i < 75 && tm_stop == 0) {
                            $(".send").prop("disabled", false);
                        }
                        chp_time = Number(ar_srt[i].getAttribute('data-time'));
                        if (chp_time != 0) { //Consultar
                            if (van_rchp == 0) {
                                arr_chps = $('.img_chips');
                                $.each(ar_srt, function(ind, elem) {
                                    if (chips_gm[0] == elem.value) {
                                        if (ar_srt[ind + 1] != undefined) {
                                            chp_dtm = ar_srt[ind + 1].getAttribute('data-time');
                                            if (chp_dtm != 0) {
                                                if (chp_dtm < tm_serv) {
                                                    nw_chip = ar_srt[ind + 1].value;
                                                    if (chips_gm.length == 0) {
                                                        generate_chp = 1;
                                                    } else {
                                                        if (chips_gm.includes(nw_chip) == false &&
                                                            'chp_' + nw_chip != arr_chps[0].getAttribute(
                                                                'id')
                                                        ) {
                                                            generate_chp = 1;
                                                        }
                                                    }
                                                    if (generate_chp == 1) {
                                                        refresh = 1;
                                                        if (i < 74) {
                                                            nm_chps += 1;
                                                        }
                                                        if (act_snd == 1) {
                                                            $("#next")[0].play();
                                                        }
                                                        sort(chips_gm, nw_chip);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                });
                                if (chp_time == tm_serv && refresh == 0) {
                                    nw_chip = Number(ar_srt[i].value);
                                    if (chips_gm.length == 0) {
                                        generate_chp = 1;
                                    } else {
                                        if (chips_gm.includes(nw_chip) == false &&
                                            'chp_' + nw_chip != arr_chps[0].getAttribute('id')) {
                                            generate_chp = 1;
                                        }
                                    }
                                    if (generate_chp == 1) {
                                        if (i < 74) {
                                            nm_chps += 1;
                                        }
                                        if (act_snd == 1) {
                                            $("#next")[0].play();
                                        }
                                        sort(chips_gm, nw_chip);
                                    }
                                }
                            }
                        } else {
                            if (@json($vn_srt->time) <= tm_serv) {
                                van_rchp = 0;
                                if (chip_nw != '' && chips_gm.includes(chip_nw) == false) {
                                    if (chip_nw_time <= tm_serv) { // Comprobar si es el momento de salir
                                        if (chips_gm.includes(chip_nw) == false &&
                                            'chp_' + chip_nw != arr_chps[0].getAttribute('id')) {
                                            generate_chp = 1;
                                        } else {
                                            generate_chp = 0;
                                        }
                                        if (generate_chp == 1) {
                                            if (i < 74) {
                                                nm_chps += 1;
                                            }
                                            if (act_snd == 1) {
                                                $("#next")[0].play();
                                            }
                                            $('#cont_tx_check_bing').hide(); // Ocultar msj de verificacion BINGO
                                            sort(chips_gm, chip_nw);
                                            $(".bt_num").each(function() {
                                                if ($(this).data('num_ctn') == chip_nw) {
                                                    $(this).addClass('circle_bg');
                                                }
                                            });
                                        }
                                    }
                                } else {
                                    if (cslt_van == 0) {
                                        cslt_van = 1;
                                        inf_wn(); // Se ejecuta consulta para verificar si hay ganador
                                    }
                                }
                            }
                        }
                    }
                    if (chip_nw == ar_srt[74].value || last_chip > 0 && last_chip <= tm_serv || win == 2) {
                        clearInterval(init);
                    }
                    tm_serv++;
                }, 1000);
            }
        }

        function sort(arr_chips, new_chp) {
            if (van_rchp == 1) {
                var sz_chps = 0;
                $("#cont_chips").html('');
                $.each(arr_chips, function(ind, elem) {
                    if (elem == @json($vn_srt->chip) || arr_chips.includes(Number(ar_srt[74].value))) {
                        $('#cont_tx_check_bing').show(); // Mostrar msj de verificacion BINGO
                        c_inf = 1;
                        inf_wn();
                    }
                    if (sz_chps < 6) {
                        const img = document.createElement("img");
                        img.setAttribute("id", 'chp_' + elem);
                        img.classList.add('img_chips');
                        if (sz_chps == 0) {
                            img.classList.add('zoom');
                        }
                        img.alt = elem;
                        img.src = '{{ asset('') }}img/bg/chips/' + elem + '.png';
                        document.getElementById('cont_chips').appendChild(img);
                        sz_chps += 1;
                    }
                });
                van_rchp = 0;
            } else {
                if (arr_chips.includes(new_chp) == false && new_chp != undefined) {
                    arr_chps = $('.img_chips');
                    if (arr_chps.length > 0) {
                        $('#' + arr_chps[0].getAttribute('id')).removeClass('zoom');
                    }
                    var url = '{{ asset('') }}img/bg/chips/' + new_chp + '.png';
                    $('#cont_chips').prepend(
                        `<img id="chp_${new_chp}" src="${url}" 
                        class="img_chips zoom chip_hide" alt="${new_chp}">`
                    );
                    const imgElement = document.getElementById('chp_' + new_chp);
                    imgElement.onload = function() {
                        $('#chp_' + new_chp).addClass('chip_show');
                        $(".cont_loader").hide();
                    }
                    arr_list_chips.unshift(new_chp); // Se inserta valor en Array del carrusel
                    view_list_chips(arr_list_chips); // Se inserta en el carrusel
                    if (arr_chps.length == 6) {
                        $('#' + arr_chps[5].getAttribute('id')).remove();
                        chips_gm.pop();
                    }
                    refresh = generate_chp = 0;
                    chips_gm.unshift(Number(new_chp));
                }
                if (new_chp == @json($vn_srt->chip) || new_chp == ar_srt[74].value) {
                    c_inf = 1;
                    inf_wn();
                }
                if (arr_list_chips.includes(new_chp) == false) {
                    arr_list_chips.unshift(Number(new_chp)); // Se inserta valor en Array del carrusel
                    view_list_chips(arr_list_chips); // Se inserta en el carrusel
                }
            }
            if (Number(@json($vn_srt->time)) + 2 <= tm_serv) {
                $('#cont_tx_check_bing').show(); // Verificacion BINGO 
            }
            if (van_srt_run == 0) {
                ar_six_chips();
            }
            if (auto_mc >= 1 && win == 0 || win == null) {
                if (init_move != null) {
                    localStorage.clear();
                }
                auto_marc();
            }
        }

        $(".bt_num").click(function() {
            if (win == 0) {
                if (init_move == null) {
                    localStorage.setItem("room", room);
                }
                val = $(this).val();
                cod = $(this).attr("data-cod");
                if (auto_mc == 0) {
                    if (val == '') {
                        $(this).addClass('circle_bg');
                        $(this).val(1);
                        $("#select")[0].play();
                        num_mc = [];
                        num_mc.push($(this).html());
                        comp_r = localStorage.getItem("id" + cod);
                        if (comp_r === null) {
                            localStorage.setItem("id" + cod, num_mc);
                        } else {
                            comp_r = localStorage.getItem("id" + cod);
                            tt = [comp_r];
                            tt.push($(this).html());
                            localStorage.setItem("id" + cod, tt);
                        }
                    } else if (val == 1) {
                        $(this).removeClass('circle_bg');
                        $(this).val('');
                        $("#select")[0].play();
                        comp_r = localStorage.getItem("id" + cod);
                        tt = comp_r.split(',');
                        const arr = tt.filter((item) => item != $(this).html());
                        localStorage.setItem("id" + cod, arr);
                    }
                }
            }
        });

        var ruta = "{{ route('bingo.rooms') }}";

        function return_rm(rtime) {
            c_inf = 0;
            inf_wn();
            if (rtime == 0) {
                rtime = 16000;
            } else {
                rtime = 8000;
            }
            localStorage.clear();
            setTimeout(function() {
                window.location.href = ruta;
            }, rtime);
        }

        $(".btn_close_bg_md").click(function() {
            $("#win_mdl").hide();
        });

        $(".close_ctn").click(function() {
            $("#mdl_view_ctn").hide();
        });

        function penalty() {
            let init = setInterval(function() {
                $("#tx_tm_stop").html(tm_stop);
                tm_stop--;
                if (tm_stop < 0) {
                    clearInterval(init);
                    $("#tx_tm_stop").html('');
                    $('.bt_num').prop("disabled", false);
                    $('.send').prop("disabled", false);
                }
            }, 1000);
        }

        $(".send").click(function() {
            if (win == 0 || win == null) {
                c_inf = ar_tm = 0;
                cod = $(this).attr("data-cod");
                nm_ct = document.getElementsByClassName("circle_bg");
                for (z = 0; z < nm_ct.length; z++) {
                    if ($(nm_ct[z]).attr("data-cod") == cod) {
                        ar_tm += 1;
                    }
                }
                if (songs > 0) {
                    van_vs = 3;
                } else {
                    van_vs = 23;
                }
                if (ar_tm > van_vs) {
                    $.ajax({
                        url: "{{ route('bingo.check_ctn') }}",
                        headers: {
                            'X-CSRF-TOKEN': $("#token").val()
                        },
                        method: 'post',
                        dataType: 'json',
                        data: {
                            ctn: cod,
                            nm_chps: ar_tm,
                            ck_time: tm_serv
                        },
                        success: function(data) {
                            if (data['van'] == 4) {
                                c_inf = 1;
                                win = 1;
                                $("#winner")[0].play();
                                Toast.fire({
                                    icon: 'success',
                                    title: '¡Felicidades Ganaste!'
                                })
                            } else if (data['van'] == 5) {
                                return_rm(1);
                                Toast.fire({
                                    icon: 'warning',
                                    title: '<span style="font-size:16px">¡Juega Limpio!</span>'
                                })
                            } else if (data['van'] == 0) {
                                $("#winner")[0].play();
                                Toast.fire({
                                    icon: 'success',
                                    title: '¡Ganaste un premio menor!'
                                })
                            } else if (data['van'] == 1) {
                                if (auto_mc >= 1) {
                                    Toast.fire({
                                        icon: 'warning',
                                        title: '<span style="font-size:16px">¡No tienes Bingo!</span>'
                                    })
                                } else {
                                    Toast.fire({
                                        icon: 'warning',
                                        title: '<span style="font-size:14px">¡Penalización, no tienes Bingo!</span>'
                                    })
                                    tm_stop = Number(data['tm_stop']) - tm_serv;
                                    $('.bt_num').prop("disabled", true);
                                    $('.send').prop("disabled", true);
                                    penalty();
                                }
                            } else if (data['van'] == 2) {
                                c_inf = 0;
                                Toast.fire({
                                    icon: 'warning',
                                    title: '<span style="font-size:13.75px">¡Fin del juego, suerte la próxima!</span>'
                                })
                            } else if (data['van'] == 3) {
                                c_inf = 0;
                                Toast.fire({
                                    icon: 'info',
                                    title: '¡Muy tarde, el jackpot fué repartido!'
                                })
                            }
                            inf_wn();
                        }
                    });
                } else {
                    Toast.fire({
                        icon: 'warning',
                        title: '<span style="font-size:14.5px">¡Faltan números en el cartón!</span>'
                    })
                }
            }
        });

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
                    $("#chp_wn_bing")
                        .attr("src", '{{ asset('') }}img/bg/chips/' + chp_win + '.png')
                        .attr("alt", chp_win);
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
                        img = 'logo.png';
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
            $("#win_mdl").show();
        }

        function view_ctn(ctnId) {
            $.ajax({
                url: "{{ route('bingo.view_ctn') }}",
                headers: {
                    'X-CSRF-TOKEN': $("#token").val()
                },
                method: 'post',
                dataType: 'json',
                data: {
                    id: ctnId
                },
                success: function(data) {
                    $.each(data['cartn'], function(ind, elem) {
                        $("#ct_v" + ind).html(elem);
                    });
                    $("#name_pj_view_ctn").html(data['nm_user']);
                    $("#mdl_view_ctn").show();
                }
            });
        }

        function inf_wn() {
            let = setTimeout(function increment() {
                if (c_inf == 1 || cslt_van == 1) {
                    c_inf = 0;
                    $.ajax({
                        url: '{{ asset('') }}bingocash/info/' + room,
                        headers: {
                            'X-CSRF-TOKEN': $("#token").val()
                        },
                        method: 'get',
                        dataType: 'json',
                        success: function(data) {
                            if (data['end'] == 1) {
                                if (win == 0) {
                                    Toast.fire({
                                        icon: 'warning',
                                        title: '¡Gracias por participar!'
                                    })
                                }
                                win = 2;
                                ar_tm = 0
                                clearTimeout(stop);
                            }
                            if (data['amount'] == 1) {
                                if (win == 2) {
                                    return_rm(1);
                                } else {
                                    $("#win_mdl").hide();
                                }
                            }
                            if (data['user_wn'] == 1 || win > 0) {
                                $('.send').prop("disabled", true);
                            }
                            if (win > 0 && data['winns']['name'].length > 0) {
                                $('#cont_tx_check_bing').hide(); // Ocultar msj de verificacion BINGO
                                c_inf = 0;
                                $('.bt_num').prop("disabled", true);
                                $('.send').prop("disabled", true);
                                chp_win = data['chp_win'];
                                view_wns(data['winns']);
                            }
                            if (data['nw_chip'].length > 0) {
                                $('#cont_tx_check_bing').hide();
                                chip_nw = data['nw_chip'][0];
                                chip_nw_time = data['nw_chip'][1];
                                win = 0;
                                ar_six_chips();
                            }
                        }
                    });
                }
                let stop = setTimeout(increment, 4500);
            }, 1000);
        }
    </script>
@endsection
