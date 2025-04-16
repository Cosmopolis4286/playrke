$(document).on("click", "#btn-regenerate-draw", function () {

    if (!$('#keyword-test').val()) {
        showToast('Ingrese Palabra Clave', 'warning');
        $('#keyword-test').trigger('focus');
        return;
    }

    $.post({
        url: route('bingo.test.regenerateDraw'),
        headers: {
            'X-CSRF-TOKEN': $("#5K3_70K").val()
        },
        data: {
            keyword: $('#keyword-test').val(),
            room: encrypted
        },
        dataType: 'json',
        success: function (res) {
            showToast(res.message, res.type);
        }
    });
});