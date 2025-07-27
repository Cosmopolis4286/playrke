function adjustHeightBoxes() {
    const widthNumberBox = $('.number-box').first().width();
    $('.number-box').height(widthNumberBox);
    $('.container-carton').css('visibility', 'visible');
}

if (roomStatus == 2) {
    $(function () {

        adjustHeightBoxes();

        window.addEventListener('resize', () => {
            adjustHeightBoxes();
        });
    });
}

window.adjustHeightBoxes = adjustHeightBoxes;