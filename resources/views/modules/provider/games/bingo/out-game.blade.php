@extends('layouts.app_rke_provider')
@section('title', $title)
@section('styles')
    <link rel="stylesheet" href="{{ asset('css/styles-bingo.css') }}">
@endsection

@section('content')
    <div class="row">
        <div class="col-12 col-lg-9 col-xl-10 pb-2 m-auto">
            @include('modules.game.bingo.container-last-chips')
            <div class="container">
                @include('modules.game.bingo.container-bingo-info')
                @include('modules.game.bingo.container-history-chips')
            </div>
            @include('modules.game.bingo.sounds')
        </div>
    </div>
    @if ($room->status == 2)
        @include('components.bingo.modal-info-draw')
    @elseif($room->status >= 3)
        @include('components.bingo.modal-info-winners')
    @endif
@endsection
@section('scripts')
    <script>
        const preRoute = '{{ asset('') }}';
        const encrypted = '<?php echo $room->encrypted; ?>';
        const roomStatus = '<?php echo $room->status; ?>';
        const infoGame = {!! json_encode($infoGame) !!};
        const coin = '<?php echo $coin; ?>';
        const arrDraw = {!! json_encode($sort) !!};
        const chpLast = Number('<?php echo $vanDraw; ?>');
        const startTime = Number('<?php echo $startTime; ?>');
        const encrypt = {!! json_encode($encrypt) !!};
        const soundNext = $('#next')[0];
        const soundWin = $("#winner")[0];
        const images = [];
        const restChips = {!! json_encode($chipsRest) !!};
        var lastSixChips = {!! json_encode($lastSixChips) !!};
        var timeServ = Number('<?php echo $timeServ; ?>');
        var allChips = {!! json_encode($allChips) !!};
        var allChipsSet = new Set(allChips);

        soundNext.muted = true;
        soundWin.muted = true;
    </script>
    <script>
        var loaderChip = true;
        var loadTimeServ = false;
        var vanChange = 0;
        var actSound = 0;
        var endWin = (arrDraw.length && (roomStatus === 2 || roomStatus === 3)) ? 0 : (roomStatus >= 4 ? 1 : 0);
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

        document.addEventListener("keydown", (e) => {
            e.preventDefault();
            if (e.keyCode === 123) return false;
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

            if (allChips.length && roomStatus > 2) {
                renderChipsView(allChips);
            }
        }

        window.addEventListener('resize', handleResize, true);

        document.body.addEventListener('click', function() {
            if (actSound == 0 && endWin == 0 && lastSixChips && roomStatus == 3) {
                $('#icon-volume').toggleClass('fa-volume-high fa-volume-xmark');
                actSound = 1;
                soundNext.muted = false;
                showToast('info', '¡Sonido Activado!', 1);
            }
        });

        function endGame() {
            // setTimeout(() => {
            //     window.location.href = "{{ route('login') }}";
            // }, 10000);
        }

        $(".exitRm").click(() => window.location.href = "{{ route('login') }}" && $("#modal-info-winners").modal('hide'));

        $(document).on("click", "#btn-close-carton-awarded", function() {
            $("#cont-ctn-award-winning").hide();
        });

        $('#cont_icon_volume').click(function() {
            if (actSound === 1 || actSound === 2) {
                soundNext.muted = !soundNext.muted;
                actSound = soundNext.muted ? 2 : 1;

                showToast('info', soundNext.muted ? '¡Sonido Desactivado!' : '¡Sonido Activado!', 1);

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

                if (roomStatus == 3) processChips();

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
            getGameData();
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

        function decrypt(code) {
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
            if (roomStatus != 3) return;

            for (let i = count; i < arrDraw.length; i++) {
                const chipTime = +arrDraw[i].time;

                if (chipTime && chipTime <= timeServ) {
                    const nextChip = decrypt(arrDraw[i].code);

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
            if (roomStatus < 3) {
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

                if (loaderChip && roomStatus > 2) {
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
                await Promise.all([renderChipsView(allChips)]);

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
                    <div class="winner-info-text winner-prize-amount fw-bold badge bg-navy">${coin} ${arrInfo.gain[i]}</div>
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
                error: function(xhr, roomStatus, error) {
                    console.error("Error en la solicitud:", error);
                }
            });
        }

        function getResult() {
            try {
                const res = $.getJSON(`${preRoute}bingocash/get/result/${encrypted}/`, {
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
                    await Promise.all([
                        handleLatestChips(lastSixChips),
                        renderChipsView(allChips)
                    ]);

                    if (roomStatus > 3) {
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
    <script src="{{ asset('js/time-functions.js') }}"></script>
    {{-- <script src="{{ asset('js/game-bingo.js') }}"></script> --}}
    @if ($room->status == 2)
        <script src="{{ asset('js/games/bingo/bingo-buy-ctns.js') }}"></script>
    @endif
@endsection
