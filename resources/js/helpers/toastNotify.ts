//resources/js/helpers/toastNotify.ts
import Swal, { SweetAlertIcon } from 'sweetalert2';

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

/**
 * Muestra un SweetAlert de confirmación.
 * @param text Texto del mensaje
 * @param icon Tipo de ícono (info, question, warning, etc.)
 * @param confirmButtonText Texto del botón de confirmación
 * @returns Promise<boolean> indicando si el usuario confirmó
 */
export async function showToastConfirmAction(
    text: string,
    icon: SweetAlertIcon,
    confirmButtonText: string
): Promise<boolean> {
    const result = await Swal.fire({
        title: '¿Confirmas esta acción?',
        text,
        icon,
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText,
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
    });

    return result.isConfirmed;
}
