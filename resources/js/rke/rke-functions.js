// rke-functions.js
console.log('rke-functions.js cargado');

// Funciones del módulo
function ajaxStart() {
    $('#loader, #loader-sm').show();
    $('.btn').prop('disabled', true);
}

function ajaxStop() {
    $('#loader, #loader-sm').hide();
    $('.btn:not([data-block])').prop('disabled', false);
}

// Manejador global para iniciar la animación del cargador en peticiones AJAX
$(document).ajaxStart(ajaxStart);

// Manejador global para detener la animación del cargador en peticiones AJAX
$(document).ajaxStop(ajaxStop);

// Manejador para iniciar la animación del cargador al enviar formularios
$(document).on("submit", "form", ajaxStart);

function copyInfo(text, elem = false) {
    if (!elem) elem = $("#info-to-copy");
    navigator.clipboard.writeText(elem.val());
    showToast(text, 'success');
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
        success: function (response) { },
        error: function (xhr, status, error) { }
    });
}

function submitForm(formName, requiredFields = []) {
    let isValid = true;

    // Recorre los campos requeridos y verifica si todos tienen valor
    requiredFields.forEach(function (fieldId) {
        if (!$(`#${fieldId}`).val()) {
            isValid = false;
        }
    });

    if (isValid) {
        $(`#form-${formName}`).submit();
    } else {
        showToast('warning', 'Toda la información es requerida');
    }
}

let extraInfo = false;

// Función para establecer el valor de extraInfo
function setExtraInfo(value) {
    extraInfo = value;
}

function formCRUD(elem, name, title, route, otherCasesMap = '') {
    const modalName = $(`#modal-${name}`);
    const modalLabel = $(`#modal-${name}-label`);
    const formName = $(`#form-${name}`);
    formName[0].reset();
    formName.find('select').empty();
    formName.find('input[name="_method"]').remove();

    let type;
    let encryptedId;

    if (!Array.isArray(elem)) {
        const data = elem.data();
        type = data.type;
        if (type > 1) {
            encryptedId = extraInfo ? [data.id, extraInfo] : [data.id];
            encryptedId = encodeURIComponent(JSON.stringify(encryptedId));
        }
    } else {
        type = elem[0];
        encryptedId = elem[1];
    }

    switch (type) {
        case 1:
            modalLabel.text(`Registrar ${title}`);
            formName.attr('action', `${route}/create`);
            insertInfo(type); // type, data = '';
            break;
        case 2:
            modalLabel.text(`Editar ${title}`);
            formName.attr('action', `${route}/update/${encryptedId}`)
                .append('<input type="hidden" name="_method" value="PUT">');
            getInfo(route, encryptedId); // ID encriptado;
            break;
        case 3:
            deleteInfo(encryptedId); // ID encriptado;
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

// Exponer las funciones al objeto window
window.ajaxStart = ajaxStart;
window.ajaxStop = ajaxStop;
window.copyInfo = copyInfo;
window.deleteSessionFlash = deleteSessionFlash;
window.submitForm = submitForm;
window.formCRUD = formCRUD;
window.getInfo = getInfo;