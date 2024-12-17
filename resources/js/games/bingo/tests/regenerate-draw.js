$(document).on("click", "#btn-regenerate-draw", function () {

    if (!$('#keyword-test').val()) return false;

    $.post({
        url: "{{ route('seller.getPlayer') }}",
        headers: {
            'X-CSRF-TOKEN': $("#5K3_70K").val()
        },
        data: {
            keyword: $('#keyword-test').val(),
            room: encrypted
        },
        dataType: 'json',
        success: function (data) {

        }
    });
});