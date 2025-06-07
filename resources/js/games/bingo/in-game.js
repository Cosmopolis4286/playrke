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
let posChip = 0;
let isAnimating = false;

function resizeImageChips(widthChip = false) {

    const sizeChipDraw = $('.img-chip').first().width();

    sizeChip = sizeChipDraw * 0.7;

    if (sizeChipDraw > containerHistoryChips.height()) {
        sizeChip = containerHistoryChips.height() * 0.8;
    }

    newMargin = sizeChip * 0.075;

    // Ajustar tamaño al redimensionar ventana
    $('.container-image-history').css({ width: sizeChip, height: sizeChip, margin: `0 ${newMargin}px` });

    if (widthChip) return sizeChip + (newMargin * 2);
}

function createChipDiv(chip) {
    const chipDiv = document.createElement('div');
    chipDiv.className = 'container-image-history';
    chipDiv.style.width = `${sizeChip}px`;
    chipDiv.style.height = `${sizeChip}px`;
    chipDiv.style.margin = `0 ${newMargin}px`;

    const img = document.createElement('img');
    img.className = 'image-history';
    img.src = `${preRoute}img/bg/chips/${chip}.png`;
    img.alt = chip;
    img.loading = 'lazy';

    chipDiv.appendChild(img);
    return chipDiv;
}

function renderChipsView(arrow = false, newChip = false) {

    if (!allChips.length || isAnimating) return;

    const widthContainerImage = resizeImageChips(true);

    const chipsContainer = $('#container-all-chips');
    const leftArrow = $('#cont-left-arrow');
    const rightArrow = $('#cont-right-arrow');

    let leftArrowWidth;

    if (arrow === 'R') {
        leftArrowWidth = rightArrow.outerWidth();
    } else if (posChip === 1) {
        leftArrowWidth = 0;
    } else {
        leftArrowWidth = leftArrow.outerWidth();
    }

    const screenWidth = $('#container-bingo-info').outerWidth() - leftArrowWidth - rightArrow.outerWidth();
    const maxPerView = Math.min(Math.floor(screenWidth / widthContainerImage), 12);
    const totalChips = allChips.length;

    console.log($('#container-bingo-info').outerWidth(), leftArrowWidth, rightArrow.outerWidth());

    if (arrow === 'L' || newChip) {

        isAnimating = true;

        if (posChip > 0) posChip--;

        const lastChip = chipsContainer.children().last();
        lastChip.addClass('fade-out-right');

        setTimeout(() => {
            lastChip.remove();
            const chip = !newChip ? allChips[posChip] : newChip;
            const chipDiv = createChipDiv(chip);
            chipDiv.classList.add('fade-in-left');
            chipsContainer.prepend(chipDiv);

            setTimeout(() => {
                chipDiv.classList.remove('fade-in-left');
                isAnimating = false;
            }, 250);

        }, 250);
    } else if (arrow === 'R') {
        isAnimating = true;
        posChip++;

        const firstChip = chipsContainer.children().first();
        firstChip.addClass('fade-out-left');

        setTimeout(() => {
            firstChip.remove();
            const chip = allChips[posChip + maxPerView - 1];
            const chipDiv = createChipDiv(chip);
            chipDiv.classList.add('fade-in-right');
            chipsContainer.append(chipDiv);

            setTimeout(() => {
                chipDiv.classList.remove('fade-in-right');
                isAnimating = false;
            }, 250);

        }, 250);

    } else {
        // Render inicial sin animación
        const visibleChips = allChips.slice(posChip, posChip + maxPerView);
        chipsContainer.empty();
        visibleChips.forEach(chip => {
            chipsContainer.append(createChipDiv(chip));
        });
    }

    leftArrow.toggleClass('hide-elem', posChip === 0);
    rightArrow.toggleClass('hide-elem', (posChip + maxPerView) >= totalChips).css('opacity', '1');

    containerHistoryChips.css('visibility', 'visible');
}

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
        showWinners(decryptJson(infoGame, r4nd0m), chpLast);
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
            showWinners(decryptJson(infoGame, r4nd0m), chpLast);
        }, 3500);

        endWin = 1;

        if (round > 1 && round < numRounds) {
            setTimeout(() => {
                window.location.reload();
            }, 10000); // 10 segundos
        }
    }
}

function getWinners(arrInfo, arrSize) {

    let content = '';

    for (let i = 0; i < arrSize; i++) {

        let img = arrInfo.photo ? `private/user/img/${arrInfo.photo[i]}` : logo;

        content +=
            `<div class="col-4 my-1 cont-win-info">
                <div class="cont-win-img" data-id="${arrInfo.ctn[i]}" title="Ver Cartón">
                    <i class="eye-icon fa-solid fa-eye"></i>
                    <img class="user-img" src="${preRoute}${img}">                  
                    <div class="winner-info-text fw-bold badge badge-success w-100">${arrInfo.name[i]}</div>
                    <div class="winner-info-text winner-prize-amount fw-bold badge bg-navy w-100">${coinSym} ${arrInfo.gain[i]}</div>
                </div>
            </div>`;
    }
    return content;
}

function showWinners(arrInfo, chipWin) {

    if (loaderChip) $('#cont-loader').hide();
    if (roomStatus == 3) $('#text-check-bingo').hide();

    const arrSizefullHouse = arrInfo.fullHouseWinners.size ?? 0;
    const arrSizeSerials = arrInfo.serialsWinners.size ?? 0;
    const arrSizeBingoPlays = arrInfo.bingoPlaysWinners.size ?? 0;

    const countWinners = arrSizefullHouse + arrSizeSerials;

    if (countWinners === 0) {
        $("#cont-info-winners").hide() && $('#sin_wn').show();
    } else {
        $('#count-winners').html(`<b>${countWinners}</b>`);

        const chpWinSrc = `${preRoute}img/bg/chips/${chipWin}.png`;
        $("#last-win-chip").attr({
            "src": chpWinSrc,
            "alt": chipWin
        });

        if (arrSizefullHouse > 0) {
            const content = getWinners(arrInfo.fullHouseWinners, arrSizefullHouse);
            $('#full-house-winners').html(content);
            $('#full-house-value').text(arrInfo.prizesValues[0]);
        }

        if (arrSizeBingoPlays > 0) {
            const content = getWinners(arrInfo.bingoPlaysWinners, arrSizeBingoPlays);
            $('#bingo-plays-winners').html(content);
            $('#bingo-plays-value').text(arrInfo.prizesValues[1]);
            $('#cont-bingo-plays').removeClass('hide-elem');
        }

        if (arrSizeSerials > 0) {
            const content = getWinners(arrInfo.serialsWinners, arrSizeSerials);
            $('#serials-winners').html(content);
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
