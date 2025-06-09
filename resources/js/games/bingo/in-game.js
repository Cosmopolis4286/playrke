if (restChips.length) {
    restChips.forEach(chip => new Image().src = `${preRoute}img/bg/chips/${chip}.png`);
}

$('#btn-bingo-rules').prop('disabled', false);

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
    if (actSound == 0 && endWin == 0 && roomStatus == 3) {
        $('#icon-volume').toggleClass('fa-volume-high fa-volume-xmark');
        actSound = 1;
        soundNext.muted = false;
        showToast('¡Sonido Activado!', 'info', 1);
    }
});

function endGame(useTimeout = true) {
    if (!closingRoute) return;

    const redirect = () => (window.location.href = closingRoute);
    useTimeout ? setTimeout(redirect, 25000) : redirect();
}

// Salir de la sala
$(document).on("click", ".leave-room", function () {
    $("#modal-info-winners").modal('hide');
    endGame(false);
});

$(document).on("click", "#btn-close-carton-awarded", function () {
    $("#cont-ctn-award-winning").data('status', false).hide();
});

$('#btn-volume').on("click", function () {
    if (actSound === 1 || actSound === 2) {
        soundNext.muted = !soundNext.muted;
        actSound = soundNext.muted ? 2 : 1;

        showToast(soundNext.muted ? '¡Sonido Desactivado!' : '¡Sonido Activado!', 'info', 1);

        $('#icon-volume').removeClass('fa-volume-high fa-volume-xmark')
            .addClass(soundNext.muted ? 'fa-volume-xmark' : 'fa-volume-high');
    }
});

function preloadImages(chips) {
    return Promise.all(chips.map(chip => new Promise(resolve => {
        const img = new Image();
        img.src = `${preRoute}img/bg/chips/${chip}.png`;
        img.onload = img.onerror = resolve;
    })));
}

const containerHistoryChips = $('#container-history-chips');
let sizeChip = 0;
let newMargin = 0;
let posChip = [0];
let lastRightChip = false;
let isAnimating = false;

function resizeImageChips(returnWidth = false) {
    const containerHeight = containerHistoryChips.height();
    const firstChipWidth = $('.img-chip').first().width();

    sizeChip = firstChipWidth * 0.7;

    if (firstChipWidth > containerHeight) {
        sizeChip = containerHeight * 0.8;
    }

    newMargin = sizeChip * 0.07;

    $('.container-image-history').css({
        width: sizeChip,
        height: sizeChip,
        margin: `0 ${newMargin}px`
    });

    return returnWidth ? sizeChip + newMargin * 2 : undefined;
}

function createChipDiv(chip) {
    return $('<div>', {
        class: 'container-image-history',
        css: {
            width: sizeChip,
            height: sizeChip,
            margin: `0 ${newMargin}px`
        }
    }).append($('<img>', {
        class: 'image-history',
        src: `${preRoute}img/bg/chips/${chip}.png`,
        alt: chip,
        loading: 'lazy'
    }));
}

function debounce(func, delay) {
    let timeout;
    return (...args) => {
        if (timeout) return;
        timeout = setTimeout(() => timeout = null, delay);
        func(...args);
    };
}

function animate(callback, duration = 250) {
    const start = performance.now();
    function step(now) {
        if (now - start < duration) {
            requestAnimationFrame(step);
        } else {
            callback();
        }
    }
    requestAnimationFrame(step);
}

function renderChipsView(direction = null, newChip = null) {
    if (!allChips.length || isAnimating) return;

    const chipsContainer = $('#container-all-chips');
    const leftArrow = $('#cont-left-arrow');
    const rightArrow = $('#cont-right-arrow');
    const chipWidth = resizeImageChips(true);
    const containerWidth = $('#container-bingo-info').outerWidth();

    let rightArrowWidth = rightArrow.outerWidth();
    let leftArrowWidth = false;

    if (direction === 'R') {
        leftArrowWidth = rightArrowWidth;
        if (lastRightChip == allChips[allChips.length - 2]) {
            rightArrowWidth = 0;
        }
    } else {
        if (posChip[0] === 1) {
            leftArrowWidth = 0;
        } else {
            leftArrowWidth = leftArrow.outerWidth();
        }
    }

    const screenWidth = containerWidth - leftArrowWidth - rightArrowWidth;
    const maxPerView = Math.min(Math.floor(screenWidth / chipWidth), 12);
    const totalChips = allChips.length;

    if (!direction && !newChip) {
        const visibleChips = allChips.slice(posChip[0], posChip[0] + maxPerView);
        chipsContainer.empty();
        visibleChips.forEach(chip => chipsContainer.append(createChipDiv(chip)));
        lastRightChip = visibleChips.at(-1);
        posChip[1] = visibleChips.length - 1;
    }

    else if (newChip) {
        isAnimating = true;
        posChip = [posChip[0]++, posChip[0]++];
        const chipDiv = createChipDiv(newChip).addClass('fade-in-left');
        chipsContainer.prepend(chipDiv);

        if (chipsContainer.children().length > maxPerView) {
            chipsContainer.children().last().addClass('fade-out-right');
            animate(() => chipsContainer.children('.fade-out-right').remove());
        }

        animate(() => {
            chipDiv.removeClass('fade-in-left');
            isAnimating = false;
        });
    }

    else if (direction === 'L' && posChip[0] > 0 && totalChips > 7) {
        isAnimating = true;
        posChip[1]--;
        posChip[0]--;

        const lastChip = chipsContainer.children().last().addClass('fade-out-right');

        animate(() => {
            //lastChip.remove();
            const chipDiv = createChipDiv(allChips[posChip[0]]).addClass('fade-in-left');
            chipsContainer.prepend(chipDiv);
            animate(() => {
                chipDiv.removeClass('fade-in-left');
                lastRightChip = allChips[posChip[0] + maxPerView - 1];
                isAnimating = false;
            });
        });
    }

    else if (direction === 'R' && (posChip[0] + maxPerView) < totalChips) {
        isAnimating = true;
        posChip[0]++;
        posChip[1] = posChip[0] + maxPerView - 1;

        const firstChip = chipsContainer.children().first().addClass('fade-out-left');

        animate(() => {
            firstChip.remove();
            const chip = allChips[posChip[1]];

            if (!chip || chip == lastRightChip) {
                isAnimating = false;
                return;
            }

            const chipDiv = createChipDiv(chip).addClass('fade-in-right');
            chipsContainer.append(chipDiv);

            animate(() => {
                chipDiv.removeClass('fade-in-right');
                lastRightChip = allChips[posChip[1]++];
                isAnimating = false;
            });
        });
    }

    leftArrow.toggleClass('hide-elem', posChip[0] === 0);
    rightArrow.toggleClass('hide-elem', (posChip[0] + maxPerView) >= totalChips).css('opacity', '1');
    containerHistoryChips.css('visibility', 'visible');
}

// Eventos con debounce
$('#cont-left-arrow').on('click', debounce(() => renderChipsView('L'), 300));
$('#cont-right-arrow').on('click', debounce(() => renderChipsView('R'), 300));

// Ajustar tamaño de las casillas
function adjustHeightBoxesCtnAward() {
    const widthNumberBoxViewCtn = $('.number-box-view-ctn').first().width();
    $('.number-box-view-ctn').height(widthNumberBoxViewCtn);
}

$(document).on("click", ".cont-arrow-history-chips", function () {
    renderChipsView($(this).data('move'));
});

function handleLatestChips(latestChips) {
    const containerLastChips = $("#cont-last-chips").empty();

    const chipsHtml = latestChips.slice(0, 6).map((elem, ind) => `
        <div class="cont-img-chip">
            <img id="chp_${elem}" class="img-chip${ind === 0 ? ' zoom' : ''}" alt="${elem}" src="${preRoute}img/bg/chips/${elem}.png">
        </div>
    `).join('');

    containerLastChips.html(chipsHtml);

    renderChipsView();
    autoMarked();
    processChips();

    if (roomStatus > 3 || latestChips.includes(chpLast)) {
        showWinners(decryptJson(infoGame, r4nd0m), roundNum, chpLast);
    }
}

window.onfocus = () => {
    if (roomStatus == 3) getGameData();
};

async function getGameData() {
    if (endWin != 0) return;

    try {
        const data = await $.getJSON(route('bingo.getChips', encrypted), {
            _token: $("#5K3_70K").val()
        });

        latestChips = data.latestChips;
        allChips = data.allChips;
        timeServ = Number(data.timeServ);

        handleLatestChips(latestChips);

    } catch (error) {
        console.error("Error al obtener los datos del juego:", error);
    }
}

function decryptChp(code) {
    if (code.length < 3) return false;

    const firstThree = code.slice(0, 3);
    const lastThree = code.length > 3 ? code.slice(-3) : null;

    const indexFirst = encrypt.indexOf(firstThree);
    if (indexFirst === -1) return false;

    let numDecrypt = (indexFirst + 1).toString();

    if (lastThree) {
        const indexLast = encrypt.indexOf(lastThree);
        if (indexLast === -1) return false;

        numDecrypt += (indexLast + 1).toString();
    }

    return Number(numDecrypt);
}

function checkExitOfToken() {
    for (let i = 0; i < arrDraw.length; i++) {
        const { time: chipTime, code } = arrDraw[i];

        if (+chipTime && +chipTime <= timeServ) {
            const nextChip = decryptChp(code);

            if (nextChip && !allChips.includes(nextChip)) {
                getNewChip(nextChip);
                arrDraw.splice(i, 1);
                break;
            }

            arrDraw.splice(i, 1);
        }
    }
}

let intervalId = null;

function processChips() {
    if (endWin || intervalId) return;

    intervalId = setInterval(() => {
        if (endWin || latestChips.includes(chpLast)) {
            clearInterval(intervalId);
            intervalId = null;
            return;
        }

        if (roomStatus == 3) checkExitOfToken();
        timeServ++;
    }, 1000);
}

const r4nd0m = $('#random').val();

function getNewChip(newChip) {
    if (newChip === undefined || latestChips.includes(newChip)) return;

    const $contLastChips = $('#cont-last-chips');
    const $contLoader = $('#cont-loader');
    const arrDrawImg = $('.img-chip');

    if (actSound === 1) soundNext.play();
    if (arrDrawImg.length) $(arrDrawImg[0]).removeClass('zoom');

    if (loaderChip && roomStatus > 2) {
        $contLoader.hide();
        loaderChip = false;
    }

    const newChipHtml = `
        <div class="cont-img-chip">
            <img id="chp_${newChip}" class="img-chip zoom hide-chip" alt="${newChip}"
            src="${preRoute}img/bg/chips/${newChip}.png">
        </div>`;
    $contLastChips.prepend(newChipHtml);

    $(`#chp_${newChip}`).on('load', function () {
        $(this).toggleClass('hide-chip show-chip');
    });

    if (arrDrawImg.length >= 6) {
        $(arrDrawImg.last()).remove();
        latestChips.pop();
    }

    latestChips.unshift(newChip);
    allChips.unshift(newChip);

    renderChipsView(false, newChip);
    autoMarked();

    if (newChip === chpLast) {
        $('#text-check-bingo').show();
        setTimeout(() => {
            showWinners(decryptJson(infoGame, r4nd0m), roundNum, chpLast);
        }, 3500);

        endWin = 1;

        if (roundNum > 1 && roundNum < numRounds) {
            setTimeout(() => {
                window.location.reload();
            }, 10000); // 10 segundos
        }
    }
}

function getWinners(players, numWRPT = '') {

    let content = '';

    Object.entries(players).forEach(([serial, ctnInfo]) => {

        const img = logo;

        content +=
            `<div class="col-4 my-1 cont-win-info">
                <div class="cont-win-img" data-id="${ctnInfo.ctn}" title="Ver Cartón">
                    <i class="eye-icon fa-solid fa-eye"></i>
                    <img class="user-img" src="${preRoute}${img}">                  
                    <div class="winner-info-text fw-bold badge badge-success w-100">${serial}</div>
                    <div class="winner-info-text winner-prize-amount fw-bold badge bg-navy w-100">${coinSym} ${ctnInfo.gain}</div>
                </div>
            </div>`;
    });

    return content;
}

function showWinners(arrInfo, numRound, chipWin = false) {

    if (chipWin && roundNum != numRound) {
        if (loaderChip) $('#cont-loader').hide();
        if (roomStatus === 3) $('#text-check-bingo').hide();
    }

    if (!arrInfo || typeof arrInfo !== 'object') {
        console.error('arrInfo no es válido:', arrInfo);
        return;
    }

    const rounds = arrInfo.rounds;
    if (!rounds || typeof rounds !== 'object') {
        console.error('arrInfo.rounds no es válido:', rounds);
        return;
    }

    const round = rounds[numRound];
    if (!round || typeof round !== 'object') {
        console.warn(`No hay datos para la ronda ${numRound}`);
        return;
    }

    if (round.numWR === 0) {
        $("#cont-info-winners").hide();
        return;
    }

    if (!chipWin) {
        chipWin = round.chipWin;
        startTime = round.startTime;
    }

    $('#count-winners').html(`<b>${round.numWR}</b>`);

    $("#last-win-chip").attr({
        "src": `${preRoute}img/bg/chips/${chipWin}.png`,
        "alt": chipWin
    });

    Object.entries(round).forEach(([prizeType, data]) => {
        const players = data.players;
        if (!players || typeof players !== 'object') return;

        const prizeVal = data.prizeVal;

        if (prizeType === 'FLHE') {
            const content = getWinners(players, data.numWRPT);
            $('#full-house-winners').html(content);
            $('#full-house-value').text(prizeVal);
        }

        if (prizeType === 'SRL') {
            const content = getWinners(players, data.numWRPT);
            $('#serials-winners').html(content);
            $('#serial-value').text(prizeVal);
            $('#cont-serials').removeClass('hide-elem');
        }
    });

    $("#cont-info-winners").show();

    if (endWin) endGame();

    $('#num-round-info-winners').val(numRound);

    $('#draw-date').text(convertAndDisplay(startTime));

    $("#btn-view-winners").prop('disabled', false);

    $("#modal-info-winners").modal('show');
}

$(document).on("input change", "#num-round-info-winners", function () {
    let val = $(this).val().replace(/\D/g, ''); // elimina caracteres no numéricos
    val = val.slice(0, 2); // máximo 2 dígitos
    $(this).val(val); // actualiza el input con solo números válidos

    const num = parseInt(val, 10);

    // Validar que esté dentro del rango permitido
    if (isNaN(num) || num < 1 || num > numRounds) {
        $(this).val(numRounds); // restaurar último valor válido
        return;
    }

    // Ejecutar función con valor válido
    showWinners(decryptJson(infoGame, r4nd0m), num);
});

let getViewCtn = false;

$(document).on("click", ".cont-win-img", function () {

    if (getViewCtn) return;

    const ctnID = $(this).data('id');

    $.get({
        url: route('bingo.viewCtn', ctnID),
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

        adjustHeightBoxes();

        window.addEventListener('resize', () => {

            adjustHeightBoxes();

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
