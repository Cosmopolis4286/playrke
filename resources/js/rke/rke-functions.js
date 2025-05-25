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

$(document).on('click', '[data-dismiss="modal"]', function () {
    $(this).blur();
});

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
        url: route('clear.flash.messages'),
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
    let invalidFields = [];

    // Si no hay campos requeridos especificados, seleccionarlos automáticamente
    if (!requiredFields.length) {
        requiredFields = $(`#form-${formName} input[required], #form-${formName} select[required]`)
            .map(function () {
                return this.id; // Obtener IDs de los campos
            }).get().filter(id => id); // Filtrar elementos sin ID
    }

    // Validar que todos los campos requeridos tengan un valor
    requiredFields.forEach(fieldId => {
        if (!$(`#${fieldId}`).val()) {
            invalidFields.push(fieldId);
        }
    });

    if (invalidFields.length) isValid = false;

    // Si todo es válido, enviar el formulario; si no, mostrar alerta
    if (isValid) {
        $('#loader, #loader-sm').show();
        $('.btn').prop('disabled', true);
        $(`#form-${formName}`)[0].submit();
    } else {
        showToast('Todos los campos son requeridos', 'warning');
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

function formCRUD(elem, name, title, otherCasesMap = {}) {
    const modalLabel = $(`#modal-${name}-label`);
    const formName = $(`#form-${name}`);

    formName[0].reset();
    formName.find('select').empty();

    let type = false;
    let encryptedId = '';

    // Si `elem` no es un array, extrae los datos de `elem`
    if (!Array.isArray(elem)) {
        const data = elem.data();
        type = data.type;
        if (type > 1) {
            if (extraInfo) {
                // Convierte extraInfo a un objeto
                const extraInfoObj = JSON.parse(extraInfo);
                // Crea el objeto final
                encryptedId = { id: data.id, ...extraInfoObj };
                encryptedId = encodeURIComponent(JSON.stringify(encryptedId));
            } else {
                encryptedId = data.id;
            }
        }
    } else {
        // Si `elem` es un array, usa sus valores
        type = elem[0];
        encryptedId = elem[1];
    }

    switch (type) {
        case 1: // Insertar
            modalLabel.text(`Registrar ${title}`);
            formName.find('input[name="_method"]').remove();
            formName.attr('action', route(`${name}.create`));
            insertInfo(type); // Llama a `insertInfo` sin datos adicionales
            break;

        case 2: // Editar
            modalLabel.text(`Editar ${title}`);
            formName.attr('action', route(`${name}.update`, encryptedId))
                .append('<input type="hidden" name="_method" value="PUT">');
            getInfo(name, encryptedId); // Obtén la información con el ID encriptado
            break;

        case 3: // Eliminar
            showToastOptionBtns(
                'El registro será eliminado definitivamente',
                'info',
                'Sí, eliminar',
                '#form-other-options',
                route(`${name}.delete`, encryptedId)
            );
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

function getInfo(name, id) {
    $.getJSON({
        url: route(`${name}.getInfo`, id),
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

function validateInput(input, isFloat = true, callback = null) {
    setTimeout(() => {
        const $input = $(input);
        let value = isFloat ? parseFloat($input.val()) : $input.val();
        const min = parseFloat($input.attr('min')) || 0;
        const max = parseFloat($input.attr('max')) || 100;
        let error = false;

        // Verificar si el valor es válido
        if (isNaN(value) || value < min || value > max) {
            showToast(`El valor debe estar entre ${min} y ${max}`, 'info');
            value = min; // Ajustar el valor al mínimo si hay error
            error = true;
        }

        // Actualizar el valor del input
        $input.val(isFloat ? value.toFixed(2) : value);

        // Llamar al callback si es una función, pasando el estado de error
        if (typeof callback === 'function') {
            callback(!error); // Pasa `true` si no hay error, `false` si hay error
        }

    }, 1250);
}

function stringToArray(str, separator = ';') {
    if (typeof str !== 'string' || !str.length) {
        return [];
    }

    return str.split(separator).map(item => item.trim()).filter(item => item !== '');
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
window.validateInput = validateInput;
window.stringToArray = stringToArray;