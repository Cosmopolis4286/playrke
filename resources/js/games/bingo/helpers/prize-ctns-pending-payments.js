function insertInfoPendingPayments(data) {
    const $modal = $('#modal-other-bingo-rooms-pending-payments');
    const $modalBodyContainer = $modal.find('.modal-body .container');

    $modalBodyContainer.empty();

    if (!data.length) {
        $modalBodyContainer.html(`
            <div class="alert alert-info mb-n1 text-center" role="alert">
                <strong>¡No hay pagos pendientes!</strong>
            </div>`);
        $modal.modal('show');
        return;
    }

    let html = '<div class="row">';

    data.forEach(elem => {

        const creator = elem.creator;
        let isUnitedClass = '';
        let isUnited = `disabled`;
        let isUnitedIcon = `<i class="fa-solid fa-gamepad"></i>`;

        if (!creator.length || (creator.length && creator[0].status == 0)) {
            isUnitedClass = `btn-${name}`;
            isUnited = `data-type="5" title="Participar"`;
            isUnitedIcon = `<i class="fa-solid fa-right-to-bracket"></i>`;
        }

        const originalDate = elem.config.date;
        const formattedDate = new Date(originalDate)
            .toLocaleDateString('es-ES', {
                day: '2-digit',
                month: '2-digit',
                year: '2-digit'
            });

        html += `
            <div class="input-group">
                <div class="input-group-prepend">
                    <label class="input-group-text">
                        <small><i class="fa-solid fa-calendar-days mr-1 mb-1"></i></small>${formattedDate}
                        <small><i class="fa-solid fa-clock ml-1 mr-1 mb-1"></i></small>${elem.config.time}
                    </label>
                </div>
                <div class="form-control">Bote Actual: <b>${elem.prize_pool} <small>USD</small></b></div>
                <div class="input-group-append">
                    <button class="btn btn-outline-success ${isUnitedClass}"
                     type="button" data-id="${elem.encrypted}" ${isUnited}>
                        ${isUnitedIcon}
                    </button>
                </div>
            </div>`;
    });

    html += '</div>';

    $modalBodyContainer.html(html);
    $modal.modal('show');
}

$(document).on("click", "#btn-pending-payments", function () {
    $.getJSON({
        url: route('bingo.getInfoPublicRoomPendingPayments'),
        headers: {
            'X-CSRF-TOKEN': $("#5K3_70K").val()
        },
        success: function (data) {
            insertInfoPendingPayments(data);
        }
    });
});