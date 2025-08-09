import Swal, { SweetAlertIcon } from 'sweetalert2';
import { handleOtherCases } from './formCrud.js';

const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3500,
    timerProgressBar: true,
    didOpen: (toast: HTMLElement) => {
        toast.addEventListener('mouseenter', Swal.stopTimer)
        toast.addEventListener('mouseleave', Swal.resumeTimer)
    }
});

export function showToast(text: string, icon: SweetAlertIcon): void {
    Toast.fire({ icon, title: text });
}

export function showToastOptionBtns(
    text: string,
    icon: SweetAlertIcon,
    confirmButtonText: string,
    route: string
): void {
    Swal.fire({
        title: '¿Confirmas esta acción?',
        text,
        icon,
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText,
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
    }).then((result) => {
        if (result.isConfirmed) {
            handleOtherCases(route);
        } else if (result.isDismissed) {
            Swal.fire('Los cambios no se guardaron', '', 'info');
        }
    });
}

// Opcional: si quieres exponerlas globalmente en window:
declare global {
    interface Window {
        showToast: typeof showToast;
        showToastOptionBtns: typeof showToastOptionBtns;
    }
}
