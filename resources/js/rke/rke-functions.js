// rke-functions.js

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
    // Verifica si el navegador soporta la API Clipboard
    if (!navigator.clipboard) return false;

    if (!elem) elem = $("#info-to-copy");
    navigator.clipboard.writeText(elem.val());
    showToast(text, 'success');
}

function deleteSessionFlash(arr = []) {
    $.ajax({
        url: "{{ route('clear.flash.messages') }}",
        type: 'POST',
        headers: {
            'X-CSRF-TOKEN': $("#5K3_70K").val()
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
        showToast('Toda la información es requerida', 'warning');
    }
}

let extraInfo = false;

function setExtraInfo(value) {
    extraInfo = value;
}

// Función para establecer el valor de extraInfo
function setVariable(name, value) {
    if (params.hasOwnProperty(name)) {
        params[name] = value;
    } else {
        console.error(`Variable ${name} no existe.`);
    }
}

function formCRUD(elem, name, title, route, otherCasesMap = '') {
    const modalName = $(`#modal-${name}`);
    const modalLabel = $(`#modal-${name}-label`);
    const formName = $(`#form-${name}`);

    formName[0].reset();
    formName.find('select').empty();
    formName.find('input[name="_method"]').remove();

    let type = false;
    let encryptedId = '';

    // Si `elem` no es un array, extrae los datos de `elem`
    if (!Array.isArray(elem)) {
        const data = elem.data();
        type = data.type;
        if (type > 1) {
            encryptedId = extraInfo ? [data.id, extraInfo] : [data.id];
            encryptedId = encodeURIComponent(JSON.stringify(encryptedId));
        }
    } else {
        // Si `elem` es un array, usa sus valores
        type = elem[0];
        encryptedId = elem[1];
    }

    switch (type) {
        case 1:
            modalLabel.text(`Registrar ${title}`);
            formName.attr('action', `${route}/create`);
            insertInfo(type); // Llama a `insertInfo` sin datos adicionales
            break;

        case 2:
            modalLabel.text(`Editar ${title}`);
            formName.attr('action', `${route}/update/${encryptedId}`)
                .append('<input type="hidden" name="_method" value="PUT">');
            getInfo(route, encryptedId); // Obtén la información con el ID encriptado
            break;

        case 3:
            deleteInfo(encryptedId); // Llama a `deleteInfo` con el ID encriptado
            break;

        default:
            // Verifica si el `type` está mapeado en `otherCasesMap` y si es una función
            if (otherCasesMap[type]) {
                otherCasesMap[type](encryptedId); // Pasa `encryptedId` como argumento
            } else {
                console.error(`Tipo de caso no manejado: ${type}`);
            }
            break;
    }
}

function getInfo(route, id) {
    const url = `${route}/getInfo/:id`.replace(':id', id);
    $.getJSON({
        url: url,
        data: {
            '_token': $("#5K3_70K").val()
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
window.setExtraInfo = setExtraInfo;
window.submitForm = submitForm;
window.formCRUD = formCRUD;
window.getInfo = getInfo;