if (restChips.length) {
    for (const chip of restChips) {
        images.push(new Image().src = `${preRoute}img/bg/chips/${chip}.png`);
    }
}

$(document).ajaxStart(function () {
    $('#loaderCtn').show();
});

$(document).ajaxStop(function () {
    $('#loaderCtn').hide();
});

document.addEventListener('dragstart', function (evt) {
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

    if (allChips.length && roomStatus > 2) {
        renderChipsView(allChips);
    }
}

window.addEventListener('resize', handleResize, true);

document.body.addEventListener('click', function () {
    if (actSound == 0 && endWin == 0 && lastSixChips && roomStatus == 3) {
        $('#icon-volume').toggleClass('fa-volume-high fa-volume-xmark');
        actSound = 1;
        soundNext.muted = false;
        showToast('¡Sonido Activado!', 'info', 1);
    }
});

function endGame() {
    // setTimeout(() => {
    //     window.location.href = closingRoute;
    // }, 10000);
}

// Salir de la sala
$(document).on("click", ".leave-room", function () {
    $("#modal-info-winners").modal('hide');
});

$(document).on("click", "#btn-close-carton-awarded", function () {
    $("#cont-ctn-award-winning").data('status', false).hide();
});

$('#btn-volume').click(function () {
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

            await Promise.all([
                handleLatestChips(data.lastSixChips),
                renderChipsView(data.allChips),
                timeServ = Number(data.timeServ),
                vanChange = 0
            ]);

        } catch (error) {
            console.error("Error al obtener los datos del juego:", error);
        }
    }
}

function handleLatestChips(latestChips) {
    return new Promise((resolve) => {

        $("#cont-last-chips").empty();

        $.each(latestChips.slice(0, 6), function (ind, elem) {
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
                `<img class="img-fluid" src="${preRoute}img/bg/chips/${chip}.png" alt="${chip}">`;
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

window.onfocus = function () {
    if (roomStatus == 3) getGameData();
};

// Ajustar tamaño de las casillas
function adjustHeightBoxesCtnAward() {
    let widthNumberBoxViewCtn = $('.number-box-view-ctn').first().width();
    $('.number-box-view-ctn').height(widthNumberBoxViewCtn);
}

function adjustHeightBoxes() {
    let widthNumberBox = $('.number-box').first().width();
    $('.number-box').height(widthNumberBox);
}

$(document).ready(function () {

    if (!player) return false;

    adjustHeightBoxes();
    $('.container-carton').css('visibility', 'visible');

    // Llama a la función cada vez que se redimensiona la ventana
    $(window).resize(function () {
        adjustHeightBoxes();
        if (roomStatus > 2 && $('#cont-ctn-award-winning').data('status') == true) {
            adjustHeightBoxesCtnAward();
        }
    });
});

$(document).on("click", ".cont-arrow-history-chips", function () {
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
    if (roomStatus != 3) return;

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

            // Incrementar `timeServ
            timeServ++;

            // Recargar la página si el tiempo es igual
            if (startTime === timeServ) {
                location.reload();
            }
        }, 1000);
    }
}

const r4nd0m = $('#random').val();

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
                alt="${newChip}" src="${preRoute}img/bg/chips/${newChip}.png">
            </div>`;

        // Añadir el nuevo chip
        $('#cont-last-chips').prepend(newChipHtml);

        // Manejar el evento de carga de la imagen
        $(`#chp_${newChip}`).on('load', function () {
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
            $('#text-check-bingo').show();
            endWin = 1;
            setTimeout(() => {
                showWinners(decryptJson(infoGame, r4nd0m), chpLast);
            }, 3000);
            //getResult();
        }
    }
}

function getWinners(arrInfo, arrSize) {
    let content = '';
    for (let i = 0; i < arrSize; i++) {

        let img = arrInfo.photo ? `imgSystem/imgUsers/${arrInfo.photo[i]}` : logo;

        content +=
            `<div class="col-4 my-1 cont-win-info">
                    <button class="cont-win-img" type="button" data-id="${arrInfo.ctn[i]}" title="Ver Cartón">
                        <i class="eye-icon fa-solid fa-eye"></i>
                        <img class="user-img" src="${preRoute}${img}">
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
    if (roomStatus == 3) $('#text-check-bingo').hide();

    const arrSizeJackpot = arrInfo.jackpotWinners.size;
    const arrSizeSerials = arrInfo.serialsWinners.size ?? 0;

    const countWinners = arrSizeJackpot + arrSizeSerials;

    if (countWinners === 0) {
        $("#cont-info-winners").hide() && $('#sin_wn').show();
    } else {
        $('#count-winners').html(`<b>${countWinners}</b>`);

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

    $('#draw-date').text(convertAndDisplay(startTime));

    $("#modal-info-winners").modal('show');
}

let getViewCtn = false;

$(document).on("click", ".cont-win-img", function () {
    if (getViewCtn) return;
    $.get({
        url: `${preRoute}bingocash/viewCtn/${$(this).data('id')}`,
        dataType: 'json',
        data: {
            '_token': $("#token").val()
        },
        beforeSend: function () {
            getViewCtn = true;
        },
        success: function (res) {
            const {
                ctn,
                playerName,
                serialCtn
            } = res;
            ctn.forEach((elem, ind) => $(`#ctn-vchip${ind}`).html(elem));
            $("#ctn-player-name").text(playerName);
            $("#serial-ctn").text('Serial: ' + serialCtn);
            $("#cont-ctn-award-winning").data('status', true).show();
            adjustHeightBoxesCtnAward();
        },
        error: function (xhr, status, error) {
            console.error("Error en la solicitud:", error);
        }, complete: function () {
            getViewCtn = false;
        }
    });
});

// function getResult() {
//     try {
//         const res = $.getJSON(`${preRoute}bingocash/get/result/${encrypted}/${player}`, {
//             '_token': $("#token").val()
//         });
//         showWinners(res, chpLast);
//         $("#btn-view-winners").prop('disabled', false);
//         $('#text-check-bingo').hide();
//     } catch (error) {
//         console.log("Error:", error);
//     }
// }

$(document).on("click", "#btn-view-winners", function () {
    $("#modal-info-winners").modal('show');
});

$(async function () {
    try {
        if (lastSixChips.length) {
            await Promise.all([
                handleLatestChips(lastSixChips),
                renderChipsView(allChips)
            ]);

            if (roomStatus > 3) {
                showWinners(decryptJson(infoGame, r4nd0m), chpLast);
                $("#btn-view-winners").prop('disabled', false);
            }
        } else {
            processChips();
        }
    } catch (error) {
        console.error("Error en la inicialización:", error);
    }
});
