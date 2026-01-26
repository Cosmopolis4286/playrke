import Swal, { SweetAlertIcon, SweetAlertOptions } from 'sweetalert2';

/**
 * Configuración base para toasts personalizados con SweetAlert2.
 */
const Toast = Swal.mixin({
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timer: 3500,
  timerProgressBar: true,
  didOpen: (toast: HTMLElement | null) => {
    if (toast) {
      // Pausar el timer al pasar el mouse sobre el toast
      toast.addEventListener('mouseenter', Swal.stopTimer);
      // Reanudar el timer al salir el mouse
      toast.addEventListener('mouseleave', Swal.resumeTimer);
    }
  },
});

/**
 * Muestra un toast con texto e ícono, con opciones adicionales opcionales.
 *
 * @param {string} text - Texto a mostrar en el toast.
 * @param {SweetAlertIcon} icon - Tipo de ícono (success, error, warning, info, question).
 * @param {Partial<SweetAlertOptions>} [options] - Opciones adicionales para personalizar el toast.
 */
export function showToast(
  text: string,
  icon: SweetAlertIcon,
  options?: Partial<SweetAlertOptions>
): void {
  // Validar que el icono sea uno válido para evitar errores
  const validIcons: SweetAlertIcon[] = ['success', 'error', 'warning', 'info', 'question'];
  if (!validIcons.includes(icon)) {
    icon = 'info';
  }

  // Ejecutar el toast mezclando las opciones predeterminadas con las adicionales
  Toast.fire({
    icon,
    title: text,
    ...options,
  } as SweetAlertOptions);
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
