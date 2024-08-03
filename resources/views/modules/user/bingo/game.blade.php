@extends('layouts.app_rke')
@section('title', 'BingoCash')
@section('styles')
<link rel="stylesheet" href="{{ asset('css/styles_bg.css') }}">@endsection

@section('content')
    <div id="container_fnd_bingo">
        <div id="content_gm_bg">
            <div class="row">
                <div id="container_sup_chips">
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
                            <div class="close_ctn">
                                <i class="fa-solid fa-xmark"></i>
                            </div>
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
                                    <img class="ctn_lgo elem_block" src="{{ asset('img/rke.png') }}">
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
                            <div class="btn_close_bg_md close_rm">
                                <i class="fa-solid fa-xmark"></i>
                            </div>
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
                                    {{ $room->type_boat == 1 ? 'jackpot:' : ($room->type_boat == 2 ? 'Premio:' : '') }}
                                </span>
                                <div id="cont_icon_sound">
                                    <i id="control_sound" class="fa-solid fa-volume-xmark"></i>
                                </div>
                                <input id="val_control_sound" type="hidden" value="0">
                            </div>
                            <span class="num_bt_rm">
                                @if ($room->coin > 1)
                                    {{ $wallet->name }}
                                @endif
                                {{ number_format($room->jackpot, 2) }}
                                @if ($room->coin == 1)
                                    <small>USDT</small>
                                @endif
                            </span>
                        </div>
                    </div>
                    <div id="tx_tm_stop"></div>
                    <div class="cont_img_rm">
                        <div class="cont_img_bg">
                            <img class="img_bg elem_block"src='{{ asset('/img/bg/estelar.png') }}'>
                            <div id="valSongs" data-id="@json($room->id)"></div>
                            <div class="md_gm_rm">Modo: Cartón Lleno</div>
                            <div id="rmd" data-id="@json($room->id)"></div>
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
            {{-- <div id="cont_players">
                <img class="img_players" src='{{ asset('/img/bg/estelar.png') }}'>
                <div style="display: grid; align-items:center">
                    <h6>Username</h6>
                    <h6>4 en Espera</h6>
                </div>
            </div> --}}
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
                                    <img class="ctn_lgo elem_block" src="{{ asset('img/rke.png') }}">
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
    <audio id="next">
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
        const soundNext = $('#next')[0];
        const soundWin = $("#winner")[0];
        soundNext.muted = true;
        soundWin.muted = true;
        var allChips = JSON.parse('<?php echo json_encode($all_chips); ?>');
        var sizeCarrsel;

        function handleResize(e) {
            const view_width = e.currentTarget.innerWidth;

            if (view_width > 745) {
                sizeCarrsel = 12;
            } else if (view_width < 370) {
                sizeCarrsel = 7;
            } else {
                sizeCarrsel = 8;
            }

            if (allChips.length > 0) {
                renderChipsView(allChips);
            }
        }
        window.addEventListener('resize', handleResize, true);

        const idRmBing = $('#rmd').data('id');
        const coinRoom = '<?php echo json_encode($room->coin); ?>';
        const valSongs = $('#valSongs').data('id');
        const autoMc = '<?php echo json_encode($room->auto_mc); ?>';
        if (autoMc >= 1) $(".bt_num").prop("disabled", true);
        const arrSort = $('.chips');
        const chpLast = arrSort[74].value;
        const chipVanTime = '<?php echo json_encode($vn_srt->time); ?>';

        var timeServ = Number('<?php echo json_encode($tm_serv); ?>');
        var timeStop = Number('<?php echo json_encode($tm_stop); ?>') || 0;
        var lastSixChip = JSON.parse('<?php echo json_encode($lt_chips); ?>');
        var lastPostChip = lastSixChip.length > 0 ? Number('<?php echo json_encode($last_ps); ?>') : 0;
        var endWin = 0;
        var vanChange = vanGetChips = actSound = 0;
        var vanNwChip = chipVanTime < timeServ ? 1 : 0;

        Echo.channel('roomBingo.' + idRmBing)
            .listen('UserJoinedRoomBingo', (event) => {
                console.log('Usuario unido a la sala de bingo', event);
            });

        function sndWin() {
            if (actSound == 1) soundWin.play();
        }

        function getNoBingoTitle() {
            return autoMc >= 1 ?
                '<span style="font-size:16px">¡No tienes Bingo!</span>' :
                '<span style="font-size:14px">¡Penalización, no tienes Bingo!</span>';
        }

        $(function() {
            if (timeStop > 0) penalty();
            if (endWin == 0) {
                if (lastPostChip > 22 && timeStop === 0) {
                    $(".send").prop("disabled", false);

                }
                if (lastSixChip) {
                    vanGetChips = 1;
                    getChipSort(lastSixChip);
                    renderChipsView(allChips);
                } else {
                    lastSixChip = [];
                    processChips();
                }
            }
        });

        // document.addEventListener('dragstart', function(evt) {
        //     if (evt.target.tagName.toLowerCase() === 'img') {
        //         evt.preventDefault();
        //     }
        // });

        // document.addEventListener("contextmenu", e => e.preventDefault());

        const restChips = JSON.parse('<?php echo json_encode($chips_gm_rest); ?>');
        const images = [];
        for (const chip of restChips) {
            images.push(new Image().src = '{{ asset('') }}img/bg/chips/' + chip + '.png');
        }

        document.addEventListener("keydown", (e) => {
            e.preventDefault();
            if (e.keyCode === 123) return false;
        });

        document.body.addEventListener('click', function() {
            if (actSound == 0) {
                $('#control_sound').toggleClass('fa-volume-high fa-volume-xmark');
                actSound = 1;
                soundNext.muted = false;
                showToast('info', '¡Sonido Activado!', 1);
            }
        });

        const ruta = coinRoom == 1 ? route('bingo.rooms_usdt') : route('bingo.rooms');
        var storeInfoBingo = [];

        function insertStoreInfo(nameKey) {
            if (!storeInfoBingo.includes(nameKey)) {
                storeInfoBingo.push(nameKey);
            }
        }

        function clearStoreGm() {
            storeInfoBingo.forEach(key => localStorage.removeItem(key));
        }

        function endGame(rTime) {
            rTime = (rTime == 0) ? 14000 : 8000;
            clearStoreGm();
            setTimeout(() => {
                window.location.href = ruta;
            }, rTime);
        }

        $('#cont_icon_sound').click(function() {
            if (actSound === 1 || actSound === 2) {
                soundNext.muted = !soundNext.muted;
                actSound = soundNext.muted ? 2 : 1;

                showToast('info', soundNext.muted ? '¡Sonido Desactivado!' : '¡Sonido Activado!', 1);

                $('#control_sound').removeClass('fa-volume-high fa-volume-xmark')
                    .addClass(soundNext.muted ? 'fa-volume-xmark' : 'fa-volume-high');
            }
        });

        window.onblur = () => vanChange = 1;

        window.onfocus = function() {
            if (vanChange === 1 && endWin === 0) {
                $.getJSON(`{{ asset('') }}bingo/get/chips/${idRmBing}`, {
                        '_token': $("#token").val()
                    })
                    .done(data => {
                        vanChange = 0;
                        timeServ = data[2]
                        getChipSort(data[0]);
                    });
            }
        };

        function autoMarked() {
            let arrAutoChips = [];
            for (const elem of arrSort) {
                const chpTime = Number(elem.getAttribute('data-time'));
                const chipValue = Number(elem.value);

                if ((chpTime > 0 && chpTime <= timeServ && !arrAutoChips.includes(chipValue)) ||
                    (nwChip && !arrAutoChips.includes(nwChip) && nwChipTime <= timeServ)) {
                    arrAutoChips.push(chipValue);
                }
            }

            $(".bt_num").each(function() {
                if (arrAutoChips.includes($(this).data('num_ctn'))) {
                    $(this).addClass('circle_bg');
                }
            });
        }

        const storeCtn = localStorage.getItem("idRmBing");
        if (storeCtn) {
            const arrCtns = JSON.parse('<?php echo json_encode($ctns); ?>');
            arrCtns.forEach((ctn) => {
                const storedData = localStorage.getItem("ctnId" + ctn);
                const nw_a = storedData ? storedData.replace(/"/g, '').split(',') : [];

                $(".bt_num[data-cod='" + ctn + "']")
                    .filter((index, element) => nw_a.includes(String($(element).data('num_ctn'))))
                    .addClass('circle_bg').val(1);
            });
            if (autoMc >= 1 && storeInfoBingo.length) clearStoreGm();
        }

        $(".arrow_list_chips").click(function() {
            renderChipsView(allChips, $(this).data('move'));
        });

        var posChip = 0;

        function renderChipsView(arr, arrow) {
            let arrCrrselChips = [];
            var countChip = sizeCrrsel = 1;
            var infoHtml = '';

            if (arrow === 1 && posChip > 0) {
                posChip--;
            } else if (arrow === 2 && !arrCrrselChips.includes(arrSort[0].value)) {
                posChip++;
            }

            sizeCarrsel = (screen.width > 745) ? 12 :
                (screen.width < 370) ? 7 :
                (screen.width >= 370 && screen.width <= 745) ? 8 : sizeCarrsel;

            for (i = posChip; i < arr.length; i++) {
                if (sizeCrrsel <= sizeCarrsel) {
                    const elem_html = '<div class="content_num_list' + (countChip > 8 ?
                            ' id="list_chip' + countChip + '"' : '') + '">' +
                        '<img src="{{ asset('') }}img/bg/chips/' + arr[i] + '.png" alt="' + arr[i] + '">' +
                        '</div>';
                    countChip++, sizeCrrsel++;
                    infoHtml += elem_html;
                    arrCrrselChips.push(arr[i]);
                }
            }
            sz_carrsl = arrCrrselChips.length;

            $('#arrow_left').toggle(arrCrrselChips[0] !== lastSixChip[0]);

            $('#arrow_right').toggle(arrCrrselChips[sz_carrsl - 1] !== arr[arr.length - 1] &&
                allChips.length > sizeCarrsel);

            $('#content_list_chips').html(infoHtml);
        }

        var nwChip = nwChipTime = '';

        function checkExitOfToken() {
            if (vanNwChip == 0) {
                for (let i = lastPostChip; i <= 74; i++) {
                    const chipTime = +arrSort[i].dataset.time || 0;
                    if (chipTime == 0 && chipVanTime <= timeServ) {
                        getNwChip();
                        break;
                    }
                    const preChip = arrSort[lastPostChip - 1];
                    if (preChip && !allChips.includes(+preChip.value)) {
                        getChipSort(lastSixChip, +preChip.value);
                    } else {
                        if (chipTime != 0 && chipTime <= timeServ) {
                            nextChip = +arrSort[i].value;
                            if ('chp_' + nextChip != arrSort[0].getAttribute('id') &&
                                !lastSixChip.includes(nextChip)) {
                                lastPostChip += (i < 74);
                                getChipSort(lastSixChip, nextChip);
                            }
                        }
                    }
                    if (lastPostChip > 22) {
                        $(".send").prop("disabled", false);
                    }
                }
            } else {
                getNwChip();
            }
        }

        function getNwChip() {
            if (nwChip != '') {
                if (nwChipTime <= timeServ && vanNwChip == 1 &&
                    'chp_' + nwChip != arrSort[0].getAttribute('id')) {
                    $('#cont_tx_check_bing').show();
                    lastPostChip += (lastPostChip < 74);
                    getChipSort(lastSixChip, nwChip);
                }
            } else {
                processGetWin();
            }
        }

        function processChips() {
            if (endWin === 0) {
                checkExitOfToken();
                let intervalId = setInterval(function() {
                    checkExitOfToken();
                    if ((nwChip == chpLast && nwChipTime <= timeServ) ||
                        lastSixChip.includes(chpLast) || endWin === 1) {
                        clearInterval(intervalId);
                    }
                    timeServ++;
                }, 1000);
            }
        }

        function getChipSort(arrChip, newChip) {
            if (vanGetChips == 1) {
                $("#cont_chips").empty();
                if (arrChip.includes(chpLast) || chipVanTime + 1 <= timeServ) {
                    $('#cont_tx_check_bing').show();
                    processGetWin();
                }
                vanGetChips = 0;
                showLatestChips(arrChip);
                processChips();
            } else {
                if (!arrChip.includes(newChip) && newChip !== undefined) {
                    const arrSort = $('.img_chips');
                    if (actSound == 1) soundNext.play();
                    arrSort.length && $(`#${arrSort[0].id}`).removeClass('zoom');

                    $('<img>', {
                        id: `chp_${newChip}`,
                        src: '{{ asset('') }}img/bg/chips/' + newChip + '.png',
                        class: 'img_chips zoom chip_hide',
                        alt: newChip
                    }).prependTo('#cont_chips');

                    $(`#chp_${newChip}`).on('load', function() {
                        $(this).addClass('chip_show');
                        $(".cont_loader").hide();
                    });

                    if (arrSort.length === 6) {
                        $(`#${arrSort[5].id}`).remove();
                        lastSixChip.pop();
                    }
                    lastSixChip.unshift(Number(newChip));
                }

                if (newChip && !allChips.includes(newChip)) {
                    if (vanNwChip == 1) vanNwChip = 0;
                    allChips.unshift(Number(newChip));
                    renderChipsView(allChips);
                }

                if (chipVanTime + 1 <= timeServ || newChip == chpLast) {
                    $('#cont_tx_check_bing').show();
                    processGetWin();
                }
            }
            (autoMc >= 1 && endWin === 0) && autoMarked();
        }

        function showLatestChips(arrChip) {
            $.each(arrChip.slice(0, 6), function(ind, elem) {
                const img = $('<img>', {
                    id: 'chp_' + elem,
                    class: 'img_chips' + (ind === 0 ? ' zoom' : ''),
                    alt: elem,
                    src: '{{ asset('') }}img/bg/chips/' + elem + '.png'
                });
                $('#cont_chips').append(img);
            });
        }

        $(".bt_num").click(function() {
            if (endWin == 0) {
                const val = $(this).val();
                const cod = $(this).data("cod");
                if (autoMc == 0) {
                    if (!storeCtn) {
                        localStorage.setItem("idRmBing", idRmBing);
                        storeInfoBingo = ["room"];
                    }
                    if (!val) {
                        $(this).addClass('circle_bg').val(1);
                        $("#select")[0].play();
                        const numMarc = [$(this).html()];
                        const existingData = localStorage.getItem("ctnId" + cod);
                        const newData = existingData ? [existingData, ...numMarc] : numMarc;
                        localStorage.setItem("ctnId" + cod, newData);
                        insertStoreInfo('ctnId' + cod);
                    } else {
                        $(this).removeClass('circle_bg').val('');
                        $("#select")[0].play();
                        localStorage.setItem("ctnId" + cod, (localStorage.getItem("ctnId" + cod) || '')
                            .split(',').filter(item => item !== $(this).html()));
                    }
                }
            }
        });

        $(".btn_close_bg_md").click(() => $("#win_mdl").hide());
        $(".close_ctn").click(() => $("#mdl_view_ctn").hide());

        function penalty() {
            $('.bt_num, .send').prop("disabled", true);

            let contenTimeStop = $("#tx_tm_stop");

            function updateCounter() {
                contenTimeStop.html(timeStop);
                timeStop--;

                if (timeStop <= 0) {
                    contenTimeStop.html('');
                    $('.bt_num, .send').prop("disabled", false);
                } else {
                    setTimeout(updateCounter, 1000);
                }
            }
            updateCounter();
        }

        var stopSend = 0;

        $(".send").click(function() {
            console.log('ctn', $(this).data("cod"));
            if (endWin == 0 && stopSend == 0 && timeStop == 0) {
                const cod = $(this).data("cod");
                const numCtn = $(".circle_bg").filter(`[data-cod="${cod}"]`).length;

                const van_vs = valSongs > 0 ? 3 : 23;
                numCtn > van_vs ? (stopSend = 1, checkCtn(cod, numCtn)) :
                    showToast('warning', '<span style="font-size:14.5px">¡Faltan números en el cartón!</span>', 1);
            } else {
                $('.bt_num, .send').prop("disabled", true);
            }
        });

        function checkCtn(codCTn, numCtn) {
            $.post({
                url: "{{ route('bingo.check_ctn') }}",
                headers: {
                    'X-CSRF-TOKEN': $("#token").val()
                },
                dataType: 'json',
                data: {
                    ctn: codCTn,
                    nm_chps: numCtn,
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
                        },
                        default: {
                            icon: 'info',
                            title: '¡Algo inesperado ocurrió!'
                        }
                    };

                    if (data.van === 0) sndWin();

                    if (data.van === 1 && autoMc === 0) {
                        timeStop = Number(data.tm_stop) - timeServ;
                        penalty();
                    }
                    if (data.van === 4) {
                        sndWin();
                        processGetWin();
                    }
                    if (data.van === 5) {
                        endGame(1);
                    }
                    const config = toastConfig[data.van] || toastConfig.default;

                    const title = config.title.includes('<span') ? config.title :
                        $('<div/>').html(config.title).text();

                    showToast(config.icon, title, 2);
                    stopSend = 0
                }
            });
        }

        function showWins(arrInfo, chipWin) {
            arrSize = arrInfo.name.length;
            if (arrSize === 0) {
                $("#tx_wins").hide() && $('#sin_wn').show() && endGame(1);
            } else {
                $('#cnt_wns').html(arrSize);
                const contenWnsHtml = $('#conten_wns').html();

                if (!contenWnsHtml) {
                    const chpWinSrc = `{{ asset('') }}img/bg/chips/${chipWin}.png`;
                    $("#chp_wn_bing").attr({
                        "src": chpWinSrc,
                        "alt": chipWin
                    });

                    const date = new Date(arrInfo.hour[0] * 1000);
                    const formattedHour = ('0' + date.getHours()).slice(-2);
                    const formattedMinutes = ('0' + date.getMinutes()).slice(-2);

                    $('#tm_close_win').html(`${formattedHour}:${formattedMinutes}`);
                }

                let content = '';
                for (let i = 0; i < arrSize; i++) {
                    let img = arrInfo.foto[i] || 'rke.png';

                    content +=
                        `<div class="col-4 mt-3" style="display: grid; justify-content:center;">
                            <a href="##" class="cont_img_uswin" onclick="viewCtn(${arrInfo.ctns[i]})" title="Ver Cartón">
                                <i class="eyevwctn fa-solid fa-eye"></i>
                                <img class="img_us" src="{{ asset('') }}imgSystem/imgUsers/${img}">
                            </a>
                            <div class="center_tx_bg name_us fw-bold badge bg-success">${arrInfo.name[i]}</div>
                            <div class="center_tx_bg name_us fw-bold mont_bg badge bg-navy">{{ $wallet->name }} ${arrInfo.gain[i]}</div>
                        </div>`;
                }
                $("#tx_wins").show();
                $('#conten_wns').html(content);
            }

            if (endWin === 1) $('#com_bin').hide() && endGame(0)
            $("#win_mdl").show();
        }

        async function viewCtn(ctnId) {
            try {
                const res = await $.getJSON(`{{ asset('') }}bingo/viewCtn/${ctnId}`, {
                    '_token': $("#token").val()
                });

                const {
                    ctn,
                    playerName
                } = res;
                ctn.forEach((elem, ind) => $(`#ct_v${ind}`).html(elem));

                $("#name_pj_view_ctn").html(playerName);
                $("#mdl_view_ctn").show();
            } catch (error) {
                console.log("Error:", error);
            }
        }
        var stopGetInfo = 0;

        function getWin() {
            return new Promise((resolve, reject) => {
                if (stopGetInfo == 0) {
                    $.get({
                        url: `{{ asset('') }}bingo/info/${idRmBing}`,
                        headers: {
                            'X-CSRF-TOKEN': $("#token").val()
                        },
                        dataType: 'json',
                        beforeSend: () => {
                            stopGetInfo = 1;
                        },
                        success: (data) => {
                            if (data.end == 0) {
                                if ((data.nextChip && vanNwChip == 0) ||
                                    (nwChip == '' && vanNwChip == 1)) {
                                    if (vanNwChip == 0) vanNwChip = 1;
                                    nwChip = data.nextChip[0];
                                    nwChipTime = data.nextChip[1];
                                    checkExitOfToken();
                                }
                            } else {
                                endWin = 1;
                                $(".send").prop("disabled", true);
                                $('#cont_tx_check_bing').hide();
                                showToast('warning', '¡Gracias por participar!', 3);
                                endGame(0);
                                showWins(data.winns, data.chipWin);
                            }
                            stopGetInfo = 0;
                            resolve();
                        },
                        error: (error) => {
                            stopGetInfo = 0;
                        }
                    });
                }
            });
        }

        async function processGetWin() {
            if (endWin === 0) {
                await getWin();
                const intervalId = setInterval(async function() {
                    if (endWin == 1) {
                        clearInterval(intervalId);
                    } else {
                        await getWin();
                    }
                }, 4500);
            }
        }
    </script>
@endsection
