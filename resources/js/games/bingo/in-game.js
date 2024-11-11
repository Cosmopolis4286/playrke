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

async function getGameData() {
    if (vanChange && endWin === 0) {
        try {
            const data = await $.getJSON(`${preRoute}bingocash/get/chips/${encrypted}`, {
                '_token': $("#5K3_70K").val()
            });

            // Redefinir variables
            latestChips = data.latestChips;
            allChips = data.allChips;
            timeServ = Number(data.timeServ);
            vanChange = false;

            handleLatestChips(latestChips);

        } catch (error) {
            console.error("Error al obtener los datos del juego:", error);
        }
    }
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

    renderChipsView(allChips);
    autoMarked();
    processChips();
}

let posChip = 0;

function renderChipsView(arr, arrow) {
    if (!arr.length) return false;

    // Función auxiliar para definir el tamaño del carrusel según el ancho de pantalla
    const getSizeCarrsel = () => {
        const screenWidth = screen.width;
        if (screenWidth > 745) return 12;
        if (screenWidth < 370) return 7;
        return 8;
    };

    const sizeCarrsel = getSizeCarrsel();
    let arrCrrselChips = [];
    let infoHtml = '';

    // Actualiza la posición del chip en función de la flecha
    if (arrow === 1 && posChip > 0) {
        posChip--;
    } else if (arrow === 2 && !arrCrrselChips.includes(allChips[0])) {
        posChip++;
    }

    // Genera el HTML para los chips visibles en el carrusel
    for (let i = posChip; i < arr.length && arrCrrselChips.length < sizeCarrsel; i++) {
        const chip = arr[i];
        infoHtml += `<img class="img-fluid" src="${preRoute}img/bg/chips/${chip}.png" alt="${chip}">`;
        arrCrrselChips.push(chip);
    }

    // Configura la visibilidad de las flechas de navegación
    const firstChipVisible = arrCrrselChips[0] === allChips[0];
    const lastChipVisible = arrCrrselChips[arrCrrselChips.length - 1] === arr[arr.length - 1];
    $('#left-arrow').toggle(!firstChipVisible);
    $('#right-arrow').toggle(!lastChipVisible && allChips.length > sizeCarrsel);

    // Actualiza el contenedor de chips y muestra el historial de chips
    $('#container-all-chips').html(infoHtml);
    $('#container-history-chips').show();

    return true;
}


window.onblur = () => {
    vanChange = true;
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

    if (!player) return;

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

function checkExitOfToken() {
    for (let i = 0; i < arrDraw.length; i++) {
        const chip = arrDraw[i];
        const chipTime = +chip.time;

        // Verificamos si el chip tiene un tiempo válido y está listo para salir
        if (chipTime && chipTime <= timeServ) {
            const nextChip = decryptChp(chip.code);

            // Si el chip no ha sido procesado, lo procesamos
            if (!allChipsSet.has(nextChip)) {
                arrDraw.splice(i, 1);
                getNewChip(nextChip);
                break;
            }
        }
    }
}

let intervalId = false;

function processChips() {

    if (endWin || intervalId) return; // Si ya existe un intervalo, no se crea uno nuevo

    intervalId = setInterval(() => {
        // Condiciones para detener la ejecución
        if (endWin || latestChips.includes(chpLast) || vanChange) {
            clearInterval(intervalId); // Detiene el intervalo si alguna condición es verdadera
            intervalId = null; // Resetea la referencia del intervalo
            return;
        } else {
            // Llama a la función `checkExitOfToken` para procesar los chips
            if (roomStatus == 3) checkExitOfToken();
        }

        timeServ++;
        console.log('timeServ', timeServ);
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
        allChipsSet.add(newChip);

        // Ejecutar funciones en paralelo
        renderChipsView(allChips);
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
    if (!latestChips.length) {
        processChips();
        return;
    }

    try {
        handleLatestChips(latestChips);

        // Verifica el estado de la sala para mostrar los ganadores
        if (roomStatus > 3) {
            showWinners(decryptJson(infoGame, r4nd0m), chpLast);
        }
    } catch (error) {
        console.error("Error en la inicialización:", error);
    }
});
