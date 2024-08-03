$(document).on("click", ".show-ctns", function () {
    const PJLink = $(this).data('player_link');
    window.open(`${$('#rout-rke').val()}bingocash/${PJLink}`, "_blank");
});