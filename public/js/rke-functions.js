function ajaxStart() {
    $('#loader, #loader-sm').show();
    $('.btn').prop('disabled', true);
}

function ajaxStop() {
    $('#loader, #loader-sm').hide();
    $('.btn:not([data-block])').prop('disabled', false);
}
// Manejador global para iniciar la animación del cargador en peticiones AJAX
$(document).ajaxStart(function () {
    ajaxStart();
});

// Manejador global para detener la animación del cargador en peticiones AJAX
$(document).ajaxStop(function () {
    ajaxStop();
});

// Manejador para iniciar la animación del cargador al enviar formularios
$(document).on("submit", "form", function () {
    ajaxStart();
});

// Manejador para iniciar la animación del cargador al enviar formularios
$("form").on("submit", function () {
    ajaxStart();
});

function copyInfo(text, elem = false) {
    if (!elem) elem = $("#info-to-copy");
    navigator.clipboard.writeText(elem.val());
    showToast('success', text);
}

function deleteSessionFlash(arr = []) {
    $.ajax({
        url: "{{ route('clear.flash.messages') }}",
        type: 'POST',
        headers: {
            'X-CSRF-TOKEN': $("#token").val()
        },
        data: {
            message_keys: arr // Array de nombres de mensajes flash a eliminar
        },
        success: function (response) {
        },
        error: function (xhr, status, error) {
        }
    });
}

function submitForm(formName, requiredFields = []) {
    $(document).on("click", "#btn-confirm", function () {
        let isValid = true;

        // Recorre los campos requeridos y verifica si todos tienen valor
        requiredFields.forEach(function (fieldId) {
            if (!$(`#${fieldId}`).val()) {
                console.log(fieldId);
                isValid = false;
            }
        });

        if (isValid) {
            $(`#form-${formName}`).submit();
        } else {
            showToast('warning', 'Toda la información es requerida');
        }
    });
}

let modalName;
let insert = update = false;
let encryptedId;

function formCRUD(elem, name, title, route, otherCasesMap = '') {
    modalName = $(`#modal-${name}`);
    const modalLabel = $(`#modal-${name}-label`);
    const formName = $(`#form-${name}`);
    formName[0].reset();
    formName.find('select').empty();
    formName.find('input[name="_method"]').remove();

    let type;
    if (!Array.isArray(elem)) {
        const data = elem.data();
        type = data.type;
        if (type > 1) {
            encryptedId = data.id;
        }
    } else {
        type = elem[0];
        encryptedId = elem[1];
    }

    switch (type) {
        case 1:
            modalLabel.text(`Registar ${title}`);
            formName.attr('action', `${route}/create`);

            insertInfo(type) // type, data = '';
            break;
        case 2:
            modalLabel.text(`Editar ${title}`);

            formName.attr('action', `${route}/update/${encryptedId}`)
                .append('<input type="hidden" name="_method" value="PUT">');

            getInfo(route, encryptedId) // ID encryptado;
            break;
        case 3:
            deleteInfo(encryptedId); // ID encryptado;
            break;
        default:
            if (otherCasesMap[type]) {
                otherCasesMap[type](); // Ejecutar la función correspondiente
            }
            break;
    }
}

function getInfo(route, id) {
    const url = `${route}/getInfo/:id`.replace(':id', id);
    $.getJSON({
        url: url,
        data: {
            '_token': $("#token").val()
        },
        success: function (data) {
            insertInfo(2, data);
        },
        error: function (jqXHR, textStatus, errorThrown) {
            // Manejar errores aquí si es necesario
        }
    });
}

