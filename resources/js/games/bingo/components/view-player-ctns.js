function goToCtns(player, mainSeller) {
    window.open(`${$('#rout-rke').val()}bingocash/comm/${mainSeller}/${player}`, "_blank");
}

$(document).on("click", ".show-ctns", function () {
    const PJLink = $(this).data('player_link');
    goToCtns(PJLink, commerce);
});

window.goToCtns = goToCtns;