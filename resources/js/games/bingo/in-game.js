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

document.body.addEventListener('click', function () {
    if (actSound == 0 && endWin == 0 && latestChips && roomStatus == 3) {
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

let posChip = 0;

function renderChipsView(arrow) {

    if (!allChips.length) return;
    $('#container-history-chips').removeClass('hide-elem');

    const arr = allChips;

    let widthContArrow = $('#cont-right-arrow').width();

    // Determina la cantidad de fichas según el ancho de pantalla
    const screenWidth = $('#container-bingo-info').width() - (widthContArrow * 2);

    const calculateMaxItems = () => {
        // Calcula el número máximo de elementos que pueden caber en el carrousel
        const itemsByScreenWidth = Math.floor(screenWidth / widthContainerImage);
        // Asegura que no se supere el máximo permitido
        return Math.min(itemsByScreenWidth, 12);
    };

    const totalItems = calculateMaxItems();
    // Actualiza la posición según la dirección de la flecha
    if (arrow === 1 && posChip > 0) posChip--;
    if (arrow === 2 && posChip + totalItems < arr.length) posChip++;

    // Genera las fichas visibles y el HTML
    const visibleChips = arr.slice(posChip, posChip + totalItems);
    const infoHtml = visibleChips
        .map((chip) => {
            const imgSrc = `${preRoute}img/bg/chips/${chip}.png`;
            return `<div class="container-image-history">
                        <img class="image-history" src="${imgSrc}" alt="${chip}">
                    </div>`;
        }).join('');

    // Carga imágenes antes de actualizar el DOM
    const imagePromises = visibleChips.map((chip) => {
        const img = new Image();
        img.src = `${preRoute}img/bg/chips/${chip}.png`;
        return new Promise((resolve) => {
            img.onload = resolve;
            img.onerror = resolve;
        });
    });

    Promise.all(imagePromises).then(() => {
        $('#container-all-chips').html(infoHtml).css('visibility', 'visible');
        if (roomStatus > 2) adjustWidthBoxesHist();
        // Configura la visibilidad de las flechas de navegación
        $('#cont-left-arrow').toggleClass('hide-elem', posChip <= 0);
        $('#cont-right-arrow').toggleClass('hide-elem', (posChip + totalItems) > arr.length);
    });
}

function handleLatestChips(latestChips) {
    // Asegurarse de que el contenedor está vacío antes de añadir contenido
    const $container = $("#cont-last-chips").empty();

    // Generar el HTML para los chips
    const chipsHtml = latestChips.slice(0, 6).map((elem, ind) => {
        return `<div class="cont-img-chip">
                <img id="chp_${elem}" class="img-chip${ind === 0 ? ' zoom' : ''}" alt="${elem}" src="${preRoute}img/bg/chips/${elem}.png">
            </div>`;
    }).join(''); // Unir los elementos para crear una sola cadena

    // Insertar todo el HTML generado de una vez en el contenedor
    $container.html(chipsHtml);

    renderChipsView();
    autoMarked();
    processChips();

    // Muestra los ganadores si el estado de la sala lo permite
    if (roomStatus > 3 || latestChips.includes(chpLast)) {
        showWinners(decryptJson(infoGame, r4nd0m), chpLast);
    }
}

window.onfocus = function () {
    if (roomStatus == 3) getGameData();
};

async function getGameData() {
    if (endWin !== 0) return;
    try {
        const data = await $.getJSON(`${preRoute}bingocash/get/chips/${encrypted}`, {
            '_token': $("#5K3_70K").val()
        });

        // Redefinir variables
        latestChips = data.latestChips;
        allChips = data.allChips;
        timeServ = Number(data.timeServ);

        handleLatestChips(latestChips);

    } catch (error) {
        console.error("Error al obtener los datos del juego:", error);
    }
}

// Ajustar tamaño de las casillas
function adjustHeightBoxesCtnAward() {
    let widthNumberBoxViewCtn = $('.number-box-view-ctn').first().width();
    $('.number-box-view-ctn').height(widthNumberBoxViewCtn);
}

const heightContainerAllChips = $('#container-history-chips').height();
const sizeChip = heightContainerAllChips * 0.85;
const newMargin = sizeChip * 0.072;
const widthContainerImage = sizeChip + (newMargin * 2);

function adjustWidthBoxesHist() {
    $('.container-image-history').css({ width: sizeChip, height: sizeChip, margin: `0 ${newMargin}px` });
}

function adjustHeightBoxes() {
    let widthNumberBox = $('.number-box').first().width();
    $('.number-box').height(widthNumberBox);
}

$(document).on("click", ".cont-arrow-history-chips", function () {
    renderChipsView($(this).data('move'));
});

function decryptChp(code) {
    const firstThreeChars = code.substring(0, 3);
    const indexFirst = encrypt.indexOf(firstThreeChars);

    // Construir el número descifrado
    let numDecrypt = (indexFirst + 1).toString();

    if (indexFirst === -1) return false;

    if (code.length > 3) {
        const lastThreeChars = code.slice(-3);
        const indexLast = encrypt.indexOf(lastThreeChars);

        if (indexLast === -1) return false;

        numDecrypt += (indexLast + 1).toString();
    }

    return +numDecrypt;
}

function checkExitOfToken() {
    for (let i = 0; i < arrDraw.length; i++) {
        const chip = arrDraw[i];
        const chipTime = +chip.time;

        // Verificamos si el chip tiene un tiempo válido y está listo para salir
        if (chipTime && chipTime <= timeServ) {
            const nextChip = decryptChp(chip.code);
            // Si el chip no ha sido procesado, lo procesamos
            if (nextChip && !allChips.includes(nextChip)) {
                getNewChip(nextChip);
                break;
            }
            arrDraw.splice(i, 1);
        }
    }
}

let intervalId = false;

function processChips() {

    if (endWin || intervalId) return; // Si ya existe un intervalo, no se crea uno nuevo

    intervalId = setInterval(() => {
        // Condiciones para detener la ejecución
        if (endWin || latestChips.includes(chpLast)) {
            clearInterval(intervalId); // Detiene el intervalo si alguna condición es verdadera
            intervalId = null; // Resetea la referencia del intervalo
            return;
        } else {
            // Llama a la función `checkExitOfToken` para procesar los chips
            if (roomStatus == 3) checkExitOfToken();
        }

        timeServ++;
    }, 1000); // Intervalo de 1 segundo
}

const r4nd0m = $('#random').val();

function getNewChip(newChip) {
    if (newChip != undefined && !latestChips.includes(newChip)) {
        const arrDrawImg = $('.img-chip');
        const $contLastChips = $('#cont-last-chips');
        const $contLoader = $('#cont-loader');

        // Reproducir sonido y actualizar el efecto de zoom en el último chip
        if (actSound === 1) soundNext.play();
        if (arrDrawImg.length) $(arrDrawImg[0]).removeClass('zoom');

        // Ocultar loader si la sala está activa
        if (loaderChip && roomStatus > 2) {
            $contLoader.hide();
            loaderChip = false;
        }

        // Crear y añadir el nuevo chip
        const newChipHtml = `
            <div class="cont-img-chip">
                <img id="chp_${newChip}" class="img-chip zoom hide-chip" alt="${newChip}"
                src="${preRoute}img/bg/chips/${newChip}.png">
            </div>`;
        $contLastChips.prepend(newChipHtml);

        // Añadir clase `show-chip` al cargar la imagen
        $(`#chp_${newChip}`).on('load', function () {
            $(this).addClass('show-chip');
        });

        // Eliminar el chip más antiguo si ya hay 6 en pantalla
        if (arrDrawImg.length === 6) {
            $(arrDrawImg[5]).remove();
            latestChips.pop();
        }

        // Actualizar arrays y Set de chips
        newChip = Number(newChip);
        latestChips.unshift(newChip);
        allChips.unshift(newChip);

        // Ejecutar funciones en paralelo
        renderChipsView();
        autoMarked();

        // Mostrar ganadores si es el último chip
        if (newChip === chpLast) {
            $('#text-check-bingo').show();
            endWin = 1;
            setTimeout(() => {
                showWinners(decryptJson(infoGame, r4nd0m), chpLast);
            }, 3000);
        }
    }
}

function getWinners(arrInfo, arrSize) {
    let content = '';
    for (let i = 0; i < arrSize; i++) {

        let img = arrInfo.photo ? `private/user/img/${arrInfo.photo[i]}` : logo;

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

    if (endWin) $('#com_bin').hide() && endGame();

    $('#draw-date').text(convertAndDisplay(startTime));

    $("#btn-view-winners").prop('disabled', false);

    $("#modal-info-winners").modal('show');
}

let getViewCtn = false;

$(document).on("click", ".cont-win-img", function () {
    if (getViewCtn) return;
    $.get({
        url: `${preRoute}bingocash/viewCtn/${$(this).data('id')}`,
        dataType: 'json',
        data: {
            '_token': $("#5K3_70K").val()
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
            $("#serial-ctn").text(`Serial: ${serialCtn}`);
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

$(document).on("click", "#btn-view-winners", function () {
    if (!endWin) return;
    $("#modal-info-winners").modal('show');
});

$(function () {
    try {
        if (player) {
            adjustHeightBoxes();
            $('.container-carton').css('visibility', 'visible');
        }

        $(window).resize(function () {
            // Ajusta la altura de los contenedores al redimensionar la ventana         
            if (player) adjustHeightBoxes();

            if (roomStatus > 2) {
                renderChipsView();
                if ($('#cont-ctn-award-winning').data('status')) {
                    adjustHeightBoxesCtnAward();
                }
            }
        });

        if (!latestChips.length) {
            processChips();
        } else {
            handleLatestChips(latestChips);
        }
    } catch (error) {
        console.error("Error en la inicialización:", error);
    }
});
