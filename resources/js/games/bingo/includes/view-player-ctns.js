function goToCtns(player, mainSeller) {
    window.open(`${$('#rout-rke').val()}bingocash/comm/${mainSeller}/${player}`, "_blank");
}

$(document).on("click", ".show-ctns", function () {
    const playerLink = $(this).data('player_link');
    goToCtns(playerLink, commerce);
});

window.goToCtns = goToCtns;