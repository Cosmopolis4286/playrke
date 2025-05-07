function adjustHeightBoxes() {
    let widthNumberBox = $('.number-box').first().width();
    $('.number-box').height(widthNumberBox);
}

$(function () {

    if (roomStatus != 2) return;

    adjustHeightBoxes();
    $('.container-carton').css('visibility', 'visible');

    window.addEventListener('resize', () => {
        adjustHeightBoxes();
    });
});
window.adjustHeightBoxes = adjustHeightBoxes;