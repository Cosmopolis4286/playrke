import Swal, { SweetAlertIcon, SweetAlertOptions } from 'sweetalert2';
import { showToast as showToastificationToast } from '@/js/utils/toast';

/**
 * Re-exportado desde utils/toast.ts (vue-toastification) — fuente de verdad
 * única para toasts efímeros de la app. Se mantiene esta firma y este punto
 * de import para no tocar los +30 archivos que ya importan showToast desde
 * aquí; `options` de sweetalert2 queda sin efecto (nadie lo usaba realmente,
 * verificado antes de esta migración).
 */
export function showToast(
  text: string,
  icon: SweetAlertIcon,
  _options?: Partial<SweetAlertOptions>
): void {
  showToastificationToast(text, icon);
}

/**
 * Muestra un modal de confirmación con SweetAlert2, puede aceptar texto plano o HTML.
 *
 * @param {object} params - Parámetros de configuración del modal.
 * @param {string} [params.text] - Texto plano para mostrar (no se usa si se proporciona html).
 * @param {string} [params.html] - Contenido HTML para mostrar en el mensaje.
 * @param {SweetAlertIcon} params.icon - Tipo de ícono (info, question, warning, etc.).
 * @param {string} params.confirmButtonText - Texto para el botón de confirmación.
 * @param {string} [params.title='¿Confirmas esta acción?'] - Título del modal.
 * @returns {Promise<boolean>} Retorna una promesa que se resuelve con `true` si el usuario confirmó, `false` en otro caso.
 */
export async function showToastConfirmAction({
  text,
  html,
  icon,
  confirmButtonText,
  title = '¿Confirmas esta acción?',
}: {
  text?: string;
  html?: string;
  icon: SweetAlertIcon;
  confirmButtonText: string;
  title?: string;
}): Promise<boolean> {
  if (!confirmButtonText || confirmButtonText.trim() === '') {
    throw new Error('El texto del botón de confirmación es obligatorio');
  }

  const swalOptions: SweetAlertOptions = {
    title,
    icon,
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText,
    cancelButtonText: 'Cancelar',
    reverseButtons: true, // Invierte posición botones: confirmar a la derecha
  };

  // Priorizar contenido HTML sobre texto plano
  if (html) {
    swalOptions.html = html;
  } else if (text) {
    swalOptions.text = text;
  }

  const result = await Swal.fire(swalOptions);

  // Retornar si el usuario confirmó la acción
  return result.isConfirmed ?? false;
}
