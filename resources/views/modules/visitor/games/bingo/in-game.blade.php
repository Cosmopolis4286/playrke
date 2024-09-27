@extends('layouts.app_rke_off')
@section('title', $title)
@section('styles')
    <link rel="stylesheet" href="{{ asset('css/styles-bingo.css') }}">
@endsection

@section('content')
    <div class="row">
        <div class="col-12 col-lg-9 col-xl-10 pb-2 m-auto">
            @include('components.game.bingo.layout.container-last-chips')
            <div class="container">
                @include('components.game.bingo.layout.container-bingo-info')
                @include('components.game.bingo.layout.container-history-chips')
                <div class="container mt-1">
                    @include('components.game.bingo.layout.cartons')
                </div>
            </div>
            @include('components.game.bingo.layout.sounds')
        </div>
    </div>
    @if ($room->status == 2)
        @include('components.game.bingo.layout.modal-info-draw', ['sellerType' => $sellerType])
    @elseif($room->status >= 3)
        @include('components.game.bingo.layout.modal-info-winners')
    @endif
@endsection

@section('scripts')
    <script src="{{ asset('js/crypto-js.min.js') }}"></script>
    <script src="{{ asset('js/time-functions.js') }}"></script>
    <script>
        const preRoute = '{{ asset('') }}';
        const encrypted = '<?php echo $room->encrypted; ?>';
        const status = '<?php echo $room->status; ?>';
        const coinSym = '<?php echo $coinSym; ?>';
        const valSongs = '<?php echo $room->val_songs; ?>';
        const arrDraw = JSON.parse('<?php echo json_encode($sort); ?>');
        const chpLast = Number('<?php echo $vanDraw; ?>');
        const player = '<?php echo json_encode($player); ?>';
        const startTime = Number('<?php echo $startTime; ?>');
        const encrypt = JSON.parse('<?php echo json_encode($encrypt); ?>');
        const soundNext = $('#next')[0];
        const soundWin = $('#winner')[0];
        const images = [];
        const restChips = JSON.parse('<?php echo json_encode($chipsRest); ?>');
        var lastSixChips = JSON.parse('<?php echo json_encode($lastSixChips); ?>');
        var timeServ = Number('<?php echo $timeServ; ?>');
        var allChips = JSON.parse('<?php echo json_encode($allChips); ?>');
        var allChipsSet = new Set(allChips);
        var infoGame = JSON.parse('<?php echo json_encode($infoGame); ?>');

        soundNext.muted = true;
        soundWin.muted = true;
    </script>
    <script src="{{ asset('js/games/bingo/auto-marked.js') }}"></script>
    {{-- <script src="{{ asset('js/game-bingo.js') }}"></script> --}}
    <script>
        var loaderChip = true;
        var loadTimeServ = false;
        var vanChange = 0;
        var actSound = 0;
        var endWin = (arrDraw.length && (status === 2 || status === 3)) ? 0 : (status >= 4 ? 1 : 0);
        var sizeCarrsel;

        if (restChips.length) {
            for (const chip of restChips) {
                images.push(new Image().src = `${preRoute}img/bg/chips/${chip}.png`);
            }
        }

        $(document).ajaxStart(function() {
            $('#loaderCtn').show();
        });

        $(document).ajaxStop(function() {
            $('#loaderCtn').hide();
        });

        document.addEventListener('dragstart', function(evt) {
            if (evt.target.tagName.toLowerCase() === 'img') {
                evt.preventDefault();
            }
        });

        // document.addEventListener("contextmenu", e => e.preventDefault());

        function handleResize(e) {
            const view_width = e.currentTarget.innerWidth;

            if (view_width > 745) {
                sizeCarrsel = 12;
            } else if (view_width < 370) {
                sizeCarrsel = 7;
            } else {
                sizeCarrsel = 8;
            }

            if (allChips.length && status > 2) {
                renderChipsView(allChips);
            }
        }

        window.addEventListener('resize', handleResize, true);

        document.body.addEventListener('click', function() {
            if (actSound == 0 && endWin == 0 && lastSixChips && status == 3) {
                $('#icon-volume').toggleClass('fa-volume-high fa-volume-xmark');
                actSound = 1;
                soundNext.muted = false;
                showToast('¡Sonido Activado!', 'info', 1);
            }
        });

        function endGame() {
            // setTimeout(() => {
            //     window.location.href = "{{ route('login') }}";
            // }, 10000);
        }

        $(".exitRm").click(() => window.location.href = "{{ route('login') }}" && $("#modal-info-winners").modal('hide'));
        $(".exitRmS").click(() => $("#cont-ctn-award-winning").hide() && $("#modal-info-winners").modal('hide'));

        $(document).on("click", "#btn-close-carton-awarded", function() {
            $("#cont-ctn-award-winning").hide();
        });

        $('#cont_icon_volume').click(function() {
            if (actSound === 1 || actSound === 2) {
                soundNext.muted = !soundNext.muted;
                actSound = soundNext.muted ? 2 : 1;

                showToast(soundNext.muted ? '¡Sonido Desactivado!' : '¡Sonido Activado!', 'info', 1);

                $('#icon-volume').removeClass('fa-volume-high fa-volume-xmark')
                    .addClass(soundNext.muted ? 'fa-volume-xmark' : 'fa-volume-high');
            }
        });

        async function getGameData() {
            if (vanChange === 1 && endWin === 0) {
                try {
                    const data = await $.getJSON(`${preRoute}bingocash/get/chips/${encrypted}`, {
                        '_token': $("#token").val()
                    });

                    const [chipData, , serverTime, chips] = data;

                    ///timeServ = Number(serverTime);
                    vanChange = 0;
                    allChips = chips;

                    await Promise.all([
                        handleLatestChips(chipData),
                        renderChipsView(allChips)
                    ]);

                } catch (error) {
                    console.error("Error al obtener los datos del juego:", error);
                }
            }
        }

        function handleLatestChips(latestChips) {
            return new Promise((resolve) => {

                $("#cont-last-chips").empty();

                $.each(latestChips.slice(0, 6), function(ind, elem) {
                    $('#cont-last-chips').append(
                        $('<div>', {
                            class: 'cont-img-chip'
                        }).html(
                            $('<img>', {
                                id: 'chp_' + elem,
                                class: 'img-chip' + (ind === 0 ? ' zoom' : ''),
                                alt: elem,
                                src: `${preRoute}img/bg/chips/${elem}.png`
                            })
                        )
                    );
                });

                autoMarked();

                if (status == 3) processChips();

                resolve(); // Resuelve la promesa
            });
        }

        let posChip = 0;

        function renderChipsView(arr, arrow) {
            return new Promise((resolve) => {

                if (!arr.length) return false;

                // Inicialización de variables
                const screenWidth = screen.width;
                const sizeCarrsel = screenWidth > 745 ? 12 :
                    screenWidth < 370 ? 7 :
                    screenWidth <= 745 ? 8 : sizeCarrsel;
                let arrCrrselChips = [];
                let sizeCrrsel = 0;
                let infoHtml = '';

                // Manejo de la posición del chip en función de la flecha
                if (arrow === 1 && posChip > 0) {
                    posChip--;
                } else if (arrow === 2 && !arrCrrselChips.includes(allChips[0])) {
                    posChip++;
                }

                // Construcción del HTML y actualización de los arrays
                for (let i = posChip; i < arr.length && sizeCrrsel < sizeCarrsel; i++) {
                    const chip = arr[i];
                    const elemHtml =
                        `<img class="img-fluid" src="{{ asset('img/bg/chips') }}/${chip}.png" alt="${chip}">`;
                    infoHtml += elemHtml;
                    arrCrrselChips.push(chip);
                    sizeCrrsel++;
                }

                // Mostrar u ocultar las flechas
                const sz_carrsl = arrCrrselChips.length;
                $('#left-arrow').toggle(arrCrrselChips[0] != allChips[0]);
                $('#right-arrow').toggle(arrCrrselChips[sz_carrsl - 1] != arr[arr.length - 1] && allChips
                    .length >
                    sizeCarrsel);

                // Actualizar el contenedor de chips y mostrar el contenedor de historial
                $('#container-all-chips').html(infoHtml);
                $('#container-history-chips').show();

                resolve(); // Resuelve la promesa
            });
        }

        window.onblur = () => {
            vanChange = 1;
        };

        window.onfocus = function() {
            if (status == 3) getGameData();
        };

        // Ajustar tamano de las casillas
        $(document).ready(function() {
            function adjustHeightBoxes() {
                let widthNumberBox = $('.number-box').first().width();
                $('.number-box').height(widthNumberBox);
                $('.container-carton').css('visibility', 'visible');
            }

            // Llama a la función al cargar la página
            adjustHeightBoxes();

            // Llama a la función cada vez que se redimensiona la ventana
            $(window).resize(function() {
                adjustHeightBoxes();
            });
        });
        // Ajustar tamano de las casillas

        $(document).on("click", ".cont-arrow-history-chips", function() {
            renderChipsView(allChips, $(this).data('move'));
        });

        function decryptChp(code) {
            const firstThreeChars = code.substring(0, 3);
            const indexFirst = encrypt.indexOf(firstThreeChars);

            // Construir el número descifrado
            let numDecrypt = (indexFirst + 1).toString();

            if (indexFirst === -1) return "Código no válido";

            if (code.length > 3) {
                const lastThreeChars = code.slice(-3);
                const indexLast = encrypt.indexOf(lastThreeChars);

                if (indexLast === -1) return "Código no válido";

                numDecrypt += (indexLast + 1).toString();
            }

            return +numDecrypt;
        }

        let count = 0;

        function checkExitOfToken() {
            if (status != 3) return;

            for (let i = count; i < arrDraw.length; i++) {
                const chipTime = +arrDraw[i].time;

                if (chipTime && chipTime <= timeServ) {
                    const nextChip = decryptChp(arrDraw[i].code);

                    if (!allChipsSet.has(nextChip)) {
                        count = i + 1;
                        getNewChip(nextChip);
                    }
                }
            }
        }

        function processChips() {
            if (endWin === 0) {
                checkExitOfToken();

                // Usar setInterval para verificar condiciones periódicamente
                const intervalId = setInterval(() => {

                    checkExitOfToken();
                    // Verificar condiciones de parada de manera eficiente
                    if (lastSixChips.includes(chpLast) || endWin === 1) {
                        clearInterval(intervalId);
                        return;
                    }

                    // Incrementar `timeServ` y realizar conteo regresivo
                    timeServ++;
                    countdownt(startTime);

                    // Recargar la página si el tiempo es igual
                    if (startTime === timeServ) {
                        location.reload();
                    }
                }, 1000);
            }
            if (status == 2) {
                // Mostrar el modal si el estado de la sala es menor a 3
                $('#modal-info-draw-title').text(convertAndDisplay(startTime));
                $("#modal-info-draw").modal('show');
            }
        }

        async function getNewChip(newChip) {
            if (newChip != undefined && !lastSixChips.includes(newChip)) {

                const arrDrawImg = $('.img-chip');

                if (actSound === 1) soundNext.play();
                if (arrDrawImg.length) $(`#${arrDrawImg[0].id}`).removeClass('zoom');

                if (loaderChip && status > 2) {
                    $('#cont-loader').hide();
                    loaderChip = false;
                }

                // Crear el nuevo elemento de chip
                const newChipHtml =
                    `<div class="cont-img-chip">
                        <img id="chp_${newChip}" class="img-chip zoom hide-chip"
                        alt="${newChip}" src="${preRoute}img/bg/chips/${newChip}.png" />
                    </div>`;

                // Añadir el nuevo chip
                $('#cont-last-chips').prepend(newChipHtml);

                // Manejar el evento de carga de la imagen
                $(`#chp_${newChip}`).on('load', function() {
                    $(this).addClass('show-chip');
                });

                // Eliminar el chip más antiguo si hay 6 chips
                if (arrDrawImg.length === 6) {
                    $(`#${arrDrawImg[5].id}`).remove();
                    lastSixChips.pop();
                }

                // Actualizar los chips actuales y el set
                lastSixChips.unshift(Number(newChip));
                allChips.unshift(Number(newChip));
                allChipsSet = new Set(allChips);

                // Ejecutar funciones en paralelo
                await Promise.all([
                    autoMarked(),
                    renderChipsView(allChips)
                ]);

                // Verificar condición para mostrar el resultado
                if (newChip == chpLast) {
                    $('#cont_tx_check_bing').show();
                    endWin = 1;
                    getResult();
                }
            }
        }

        function getWinners(arrInfo, arrSize) {
            let content = '';
            for (let i = 0; i < arrSize; i++) {
                let img = arrInfo.foto[i] || 'rke.png';

                content +=
                    `<div class="col-4 my-1 cont-win-info">
                    <button class="cont-win-img" type="button" onclick="viewCtn(${arrInfo.ctns[i]})" title="Ver Cartón">
                        <i class="eye-icon fa-solid fa-eye"></i>
                        <img class="user-img" src="${preRoute}imgSystem/imgUsers/${img}">
                    </button>
                    <div class="winner-info-text fw-bold badge badge-success">${arrInfo.name[i]}</div>
                    <div class="winner-info-text winner-prize-amount fw-bold badge bg-navy">${coinSym} ${arrInfo.gain[i]}</div>
            </div>`;
            }
            return content;
        }

        function showJackpotWinners(arr, size) {
            const content = getWinners(arr, size);
            $('#jackpot-winners').html(content);
        }

        function showSeriaslWinners(arr, size) {
            const content = getWinners(arr, size);
            $('#serials-winners').html(content);
        }

        function showWinners(arrInfo, chipWin) {

            if (loaderChip) $('#cont-loader').hide();

            const arrSizeJackpot = arrInfo.jackpotWinners.size;
            const arrSizeSerials = arrInfo.serialsWinners.size;

            const countWinners = arrSizeJackpot + arrSizeSerials;

            if (countWinners === 0) {
                $("#cont-info-winners").hide() && $('#sin_wn').show();
            } else {
                $('#count-winners').html(countWinners);

                const chpWinSrc = `${preRoute}img/bg/chips/${chipWin}.png`;
                $("#last-win-chip").attr({
                    "src": chpWinSrc,
                    "alt": chipWin
                });

                showJackpotWinners(arrInfo.jackpotWinners, arrSizeJackpot);
                $('#jackpot-value').text(arrInfo.prizesValues[0]);

                if (arrSizeSerials > 0) {
                    showSeriaslWinners(arrInfo.serialsWinners, arrSizeSerials);
                    $('#serial-value').text(arrInfo.prizesValues[1]);
                    $('#cont-serials').removeClass('hide-elem');
                }

                $("#cont-info-winners").show();
            }

            if (endWin === 1) $('#com_bin').hide() && endGame();

            $("#modal-info-winners").modal('show');
        }

        let getViewCtn = false;

        function viewCtn(ctnId) {
            if (getViewCtn) {
                return;
            }
            $.get({
                url: `${preRoute}bingocash/viewCtn/${ctnId}`,
                dataType: 'json',
                data: {
                    '_token': $("#token").val()
                },
                beforeSend: function() {
                    getViewCtn = true;
                },
                success: function(res) {
                    const {
                        ctn,
                        playerName,
                        serialCtn
                    } = res;
                    ctn.forEach((elem, ind) => $(`#ctn-vchip${ind}`).html(elem));
                    $("#ctn-player-name").text(playerName);
                    $("#serial-ctn").text('Serial: ' + serialCtn);
                    $("#cont-ctn-award-winning").show();
                    getViewCtn = false;
                },
                error: function(xhr, status, error) {
                    console.error("Error en la solicitud:", error);
                }
            });
        }

        function getResult() {
            try {
                const res = $.getJSON(`${preRoute}bingocash/get/result/${encrypted}/${player}`, {
                    '_token': $("#token").val()
                });
                showWinners(res, chpLast);
                $("#btn-view-winners").prop('disabled', false);
                $('#cont_tx_check_bing').hide();
            } catch (error) {
                console.log("Error:", error);
            }
        }

        $(document).on("click", "#btn-view-winners", function() {
            $("#modal-info-winners").modal('show');
        });

        $(async function() {
            try {
                if (lastSixChips.length) {
                    // Asegúrate de que estas funciones devuelvan promesas si necesitan ejecutarse en paralelo
                    console.log(lastSixChips);
                    console.log(allChips);
                    await Promise.all([
                        handleLatestChips(lastSixChips),
                        renderChipsView(allChips)
                    ]);

                    if (status > 3) {
                        showWinners(infoGame, chpLast);
                        $("#btn-view-winners").prop('disabled', false);
                    }
                } else {
                    processChips();
                }
            } catch (error) {
                console.error("Error en la inicialización:", error);
            }
        });
    </script>
    @if ($room->status > 2)
        <script src="{{ asset('js/crypt.js') }}"></script>
        <script>
            if (endWin == 1) {
                infoGame = decryptJson(infoGame, '<?php echo $key; ?>');
            }
        </script>
    @endif
    @if ($room->status == 2)
        <script>
            let userTypeVal = '<?php echo json_encode($userTypeVal); ?>';
            let coinID = '<?php echo json_encode($coinID); ?>';
            let ctnPrice = Number('<?php echo $ctnPrice; ?>');
            let type = '<?php echo $sellerType; ?>';
            let info = false;
            let amount = ctnPrice;
        </script>
        <script src="{{ asset('js/components/switch-payment-method.js') }}"></script>
        <script src="{{ asset('js/games/bingo/bingo-buy-ctns.js') }}"></script>
        <script>
            function toggleModalInfo() {
                $("#card-form-buy-ctns").toggleClass('hide-elem');
                $("#cont-btn-buy-ctns, #game-clock").toggle();
            }

            function toggleSpinnerBtn() {
                $("#spinner-btn-buy-ctns-confirm").toggleClass('hide-elem');
            }

            function setAmount(amount) {
                if (type == 'comm') $("#sent-amount").text(`${coinSym} ${amount}`);
                $("#total-amount").text(`${coinSym} ${amount}`);
            }

            $(document).on("click", "#cont-btn-buy-ctns", function() {
                if (!info) {
                    $("#ctn-price-coin-symbol").text(coinSym);
                    $("#ctn-price-amount").text(ctnPrice);

                    if (type == 'comm') {
                        $.getJSON(`{{ url('commerce/getPaymentCommerceDetails') }}/${userTypeVal}/${coinID}`, {
                                '_token': "{{ csrf_token() }}"
                            })
                            .done(res => {
                                const data = res.data;

                                setAmount(amount);
                                $("#commerce-name").text(name);

                                if (true) {
                                    switchPaymentMethod(data.code, data);
                                    $("#commerce-details").removeClass('hide-elem');
                                }
                            })
                            .fail((jqXHR, textStatus, errorThrown) => {
                                console.error('Error:', textStatus, errorThrown);
                            });
                    } else {
                        setAmount(amount);
                    }
                }
                toggleModalInfo();
            });

            $(document).on("change", "#num-ctns", function() {
                amount = ctnPrice * $(this).val();
                setAmount(amount);
            });

            $(document).on("click", "#btn-cancel-buy-ctns", function() {
                toggleModalInfo();
            });

            $(document).on("click", "#btn-buy-ctns-confirm", function() {

                if (!$("#num-ctns").val()) {
                    $("#num-ctns").val(1);
                }

                if (type == 'comm') {
                    if (!$("#purchase-code").val()) {
                        $("#purchase-code").focus();
                        return showToast('Ingrese el Código de Compra', 'warning');
                    }

                    if (!$("#payment-proof").val()) {
                        return showToast('Cargue el Comprobante', 'warning');
                    }
                }

                $.ajax({
                    url: "{{ route('bingocash.generateCarton') }}",
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    type: 'post',
                    dataType: 'json',
                    data: {
                        room: encrypted,
                        user_type: type,
                        user_type_val: userTypeVal,
                        player: player,
                        coin: coinID,
                        num_ctns: $("#num-ctns").val()
                    },
                    beforeSend: function() {
                        toggleSpinnerBtn();
                    },
                    success: function(data) {

                        showToast(data.message, data.msg_type);

                        if (data.jackpot) {
                            $("#jackpot-amount").text(data.jackpot);
                            setTimeout(function() {
                                location.reload();
                            }, 3000);
                        }

                        toggleSpinnerBtn();
                        toggleModalInfo();
                    },
                    error: function() {}
                });
            });
        </script>
    @endif
@endsection
