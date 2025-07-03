let latestChips = [];

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
    document.activeElement.blur();
    $("#modal-info-winners").modal('hide');
    endGame(false);
});

$(document).on("click", "#btn-close-carton-awarded", function () {
    $("#cont-ctn-award-winning").hide();
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
let maxPerView = 0;
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
    const chipsContainerLength = chipsContainer.children().length;

    let rightArrowWidth = rightArrow.outerWidth();
    let leftArrowWidth = 0;

    if (direction === 'R') {
        leftArrowWidth = rightArrowWidth;
        if (lastRightChip === allChips[allChips.length - 2]) {
            rightArrowWidth = 0;
        }
    } else {
        if (!direction) {
            leftArrowWidth = leftArrow.outerWidth();
        } else {
            rightArrowWidth = rightArrow.outerWidth();
            if (posChip[0] === 1) {
                leftArrowWidth = 0;
            } else {
                leftArrowWidth = rightArrowWidth ? rightArrowWidth : leftArrow.outerWidth();
            }
            if (chipsContainerLength + 1 == maxPerView) {
                rightArrowWidth = leftArrowWidth;
            }
        }
    }

    const screenWidth = containerWidth - leftArrowWidth - rightArrowWidth;
    maxPerView = Math.min(Math.floor(screenWidth / chipWidth), 12);
    const totalChips = allChips.length;

    if (!direction && !newChip) {
        const visibleChips = allChips.slice(posChip[0], posChip[0] + maxPerView);
        chipsContainer.empty();
        visibleChips.forEach(chip => chipsContainer.append(createChipDiv(chip)));
        lastRightChip = visibleChips.at(-1);
        posChip[1] = posChip[0] + maxPerView - 1;
    }
    else if (newChip) {
        isAnimating = true;
        animate(() => {
            const chipDiv = createChipDiv(newChip).addClass('fade-in-right');
            chipsContainer.prepend(chipDiv);

            if (chipsContainerLength >= maxPerView && totalChips > 6) {
                chipsContainer.children().last().remove();
            }
            animate(() => {
                chipDiv.removeClass('fade-in-right');
                isAnimating = false;
            });
        });
    }
    else if (direction === 'L' && posChip[0] > 0 && totalChips > 7) {
        isAnimating = true;
        posChip[0]--;
        let lastChip = null;

        if (chipsContainerLength >= maxPerView) {
            const children = chipsContainer.children();
            let removeLast = true;

            if (chipsContainerLength > maxPerView) {
                if (chipsContainerLength - 1 <= maxPerView) {
                    removeLast = false;
                    lastChip = children.eq(-2).addClass('fade-out-right'); // penúltimo elemento
                    children.last().remove();
                    posChip[1] = posChip[1] - 2;
                }
            }

            if (removeLast) {
                lastChip = children.last().addClass('fade-out-right');
                posChip[1]--;
            }

            lastRightChip = allChips[posChip[0] + maxPerView - 1];
        }
        animate(() => {
            lastChip?.remove();
            const chipDiv = createChipDiv(allChips[posChip[0]]).addClass('fade-in-left');
            chipsContainer.prepend(chipDiv);
            animate(() => {
                chipDiv.removeClass('fade-in-left');
                isAnimating = false;
            });
        });
    }
    else if (direction === 'R' && (posChip[0] + maxPerView) <= totalChips) {
        isAnimating = true;
        posChip[1] = posChip[0] + maxPerView;
        posChip[0]++;

        const firstChip = chipsContainer.children().first().addClass('fade-out-left');

        animate(() => {
            firstChip.remove();
            let chip = allChips[posChip[1]];

            if (chip === undefined && posChip[1] > 0) {
                chip = allChips[--posChip[1]];
            }

            if (chip === lastRightChip) {
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
    rightArrow.toggleClass('hide-elem', ((posChip[0] + maxPerView) >= totalChips) && totalChips > 6).css('opacity', '1');
    containerHistoryChips.css('visibility', 'visible');
}

// Eventos con debounce
$('#cont-left-arrow').on('click', debounce(() => renderChipsView('L'), 300));
$('#cont-right-arrow').on('click', debounce(() => renderChipsView('R'), 300));

// Ajustar tamaño de las casillas
function adjustHeightBoxesCtnAward(checkBoxes = []) {
    const widthNumberBoxViewCtn = $('.number-box-view-ctn').first().width();
    $('.number-box-view-ctn').height(widthNumberBoxViewCtn);

    if (checkBoxes.length) autoMarked(checkBoxes);
}

$(document).on("click", ".cont-arrow-history-chips", function () {
    renderChipsView($(this).data('move'));
});

function handleLatestChips(allChips) {
    const containerLastChips = $("#cont-last-chips").empty();

    const chipsHtml = allChips.slice(0, 6).map((elem, ind) => `
        <div class="cont-img-chip">
            <img id="chp_${elem}" class="img-chip${ind === 0 ? ' zoom' : ''}" alt="${elem}" src="${preRoute}img/bg/chips/${elem}.png">
        </div>
    `).join('');

    containerLastChips.html(chipsHtml);

    renderChipsView();
    autoMarked();
    processChips();

    if (roomStatus > 3 || allChips.includes(chpLast)) {
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

        allChips = data.allChips;
        timeServ = Number(data.timeServ);

        handleLatestChips(allChips);

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
        if (endWin || allChips.includes(chpLast)) {
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
    if (newChip === undefined || allChips.includes(newChip)) return;

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
    }

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

function renderPrizeSection(content, prizeType, pct) {
    return `<b>${prizeType}: <small>(<span>${pct}</span>% del ${prizePoolType})</small></b>
            <div class="row mb-1">${content}</div>`;
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

function showWinners(arrInfo, numRound, lastChip = false) {

    if (lastChip && roundNum != numRound) {
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
        $("#cont-info-winners").hide();
        return;
    }

    $('#container-prize-ctns').html('');

    const prizeType = round.prizeType;
    let prizeSectionHTML = '';

    Object.entries(prizeType).forEach(([prize, data]) => {
        if (!data || typeof data !== 'object') return;

        const players = data.players;
        const prizeVal = data.prizeVal;
        const numWinners = data.numWRPT;

        if (!players || typeof players !== 'object' || Object.keys(players).length === 0) return;

        const content = getWinners(players, numWinners);
        prizeSectionHTML += renderPrizeSection(content, prize, prizeVal);
    });

    $('#container-prize-ctns').html(prizeSectionHTML);

    $("#cont-info-winners").show();

    if (endWin) endGame();

    $('#draw-date').text(convertAndDisplay(round.startTime));

    lastChip = round.lastChip;

    $("#last-win-chip").attr({
        "src": `${preRoute}img/bg/chips/${lastChip}.png`,
        "alt": lastChip
    });

    $('#count-winners').html(`<b>${round.numWR}</b>`);

    $('#num-round-info-winners').val(numRound);

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

    $('#cont-ctn-award-winning').hide();
    $("#ctn-player-name").text('playerName');
    $("#serial-ctn").text('');
    clearMarkedBoxes(".number-box-view-ctn .number-box-number");

    // Ejecutar función con valor válido
    showWinners(decryptJson(infoGame, r4nd0m), num);
});

let getViewCtn = false;

function clearMarkedBoxes(selector, className = 'number-box-circle') {
    document.querySelectorAll(selector).forEach((el) => {
        el.textContent = '';
        el.classList.remove(className);
    });
}

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
                carton,
                playerName,
                serialCtn
            } = res;

            let checkBoxes = [];

            //Limpiar carton
            clearMarkedBoxes(".number-box-view-ctn .number-box-number");

            Object.entries(carton).forEach(([key, elem]) => {
                const target = document.querySelector(`#box-${key}`);
                if (target) {
                    target.innerHTML = elem.value;
                    checkBoxes.unshift(key);
                }
            });

            $("#ctn-player-name").text(playerName);
            $("#serial-ctn").text(`Serial: ${serialCtn}`);
            $("#cont-ctn-award-winning").show();
            adjustHeightBoxesCtnAward(checkBoxes);
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

                if ($('#cont-ctn-award-winning').is(':visible')) {
                    adjustHeightBoxesCtnAward();
                }
            }
        });

        if (!allChips.length) {
            processChips();
        } else {
            handleLatestChips(allChips);
        }
    } catch (error) {
        console.error("Error en la inicialización:", error);
    }
});
