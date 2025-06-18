function checkBoxes(boxes) {
    $('.ctn-patterns').removeClass("number-box-circle");
    $('.ctn-patterns').each(function () {
        if (boxes.includes($(this).text().trim())) {
            $(this).addClass("number-box-circle");
        }
    });
}

function cleanBoxes() {
    $('.ctn-patterns').removeClass('number-box-circle');
    bingoBoxes = [];
    namePlay.val('');
}

window.checkBoxes = checkBoxes;
window.cleanBoxes = cleanBoxes;