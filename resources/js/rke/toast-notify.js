let toastInExecution = false;

const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3500,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer)
        toast.addEventListener('mouseleave', Swal.resumeTimer)
    }
});

function showToast(text, icon) {
    Toast.fire({ icon: icon, title: text });
}

function showToastOptionBtns(text, icon, confirmButtonText, form, routeTemplate) {

    // Verificar si el form existe en el DOM
    if (!$(form).length) {
        showToast('El formulario no está definido', 'warning');
        return;
    }

    Swal.fire({
        title: '¿Confirmas está acción?',
        text: text,
        icon: icon, // Tipo de icono: 'success', 'error', 'warning', 'info', 'question'
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: confirmButtonText,
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            $(form).attr('action', routeTemplate);
            $(form).trigger('submit');
        } else if (result.isDismissed) {
            Swal.fire('Los cambios no se guardaron', '', 'info');
        }
    });
}

// Exponer las funciones al objeto window
window.showToast = showToast;
window.showToastOptionBtns = showToastOptionBtns;