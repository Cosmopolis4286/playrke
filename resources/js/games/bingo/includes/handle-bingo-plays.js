function checkBoxes(boxes) {
    $('.ctn-plays').removeClass("number-box-circle");
    $('.ctn-plays').each(function () {
        if (boxes.includes($(this).text().trim())) {
            $(this).addClass("number-box-circle");
        }
    });
}

function cleanBoxes() {
    $('.ctn-plays').removeClass('number-box-circle');
    bingoBoxes = [];
    namePlay.val('');
}

window.checkBoxes = checkBoxes;
window.cleanBoxes = cleanBoxes;