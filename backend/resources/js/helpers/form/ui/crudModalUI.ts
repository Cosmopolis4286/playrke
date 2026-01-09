import Swal from 'sweetalert2'

/**
 * Muestra confirmación con texto personalizado y botón confirmar/cancelar.
 * Devuelve true si el usuario confirma.
 */
export async function showToastConfirmAction(
  text: string,
  icon: 'success' | 'error' | 'warning' | 'info' | 'question',
  confirmButtonText: string
): Promise<boolean> {
  return Swal.fire({
    text,
    icon,
    showCancelButton: true,
    confirmButtonText,
    cancelButtonText: 'Cancelar',
    reverseButtons: true,
    focusCancel: true
    // Opcional: estilo o configuraciones extra
  }).then(result => result.isConfirmed)
}

/**
 * Muestra un toast sencillo con un mensaje y tipo de icono.
 */
export function showToast(text: string, icon: 'success' | 'error' | 'warning' | 'info' = 'info') {
  Swal.fire({
    toast: true,
    position: 'top-end',
    icon,
    title: text,
    showConfirmButton: false,
    timer: 2500,
    timerProgressBar: true
  })
}

/**
 * Función clásica para confirmar (basada en showToastConfirmAction)
 * pero que recibe objeto caseData y cantidad para formato texto.
 */
export async function showConfirm(caseData: any, count: number) {
  const text = count > 1 ? caseData.text.replace('este', `estos ${count}`) : caseData.text

  return showToastConfirmAction(text, caseData.icon, caseData.confirmButtonText)
}

/**
 * Función clásica para mostrar éxito con texto basado en cantidad.
 */
export function showSuccess(count: number) {
  const text = count > 1 ? `Acción aplicada a ${count} registros` : 'Acción completada'

  showToast(text, 'success')
}
