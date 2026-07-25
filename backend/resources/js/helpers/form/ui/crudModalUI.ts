import Swal from 'sweetalert2'
import { showToast as showToastificationToast, showSuccess as showToastificationSuccess } from '@/js/utils/toast'

/**
 * Muestra confirmación con texto personalizado y botón confirmar/cancelar.
 * Devuelve true si el usuario confirma. Queda en sweetalert2: es un diálogo
 * de confirmación real (showCancelButton), no un toast efímero — distinto
 * del showToastConfirmAction de utils/toastNotify.ts, que además acepta
 * html/title. Antes ambos archivos exportaban una función con este mismo
 * nombre pero firmas distintas (colisión de nombres, no de tipos: nunca se
 * importaban desde el mismo punto, pero confundía qué wrapper era cuál).
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
  }).then(result => result.isConfirmed)
}

/**
 * Re-exportado desde utils/toast.ts (vue-toastification) — fuente de verdad
 * única para toasts efímeros. Se mantiene esta firma y este punto de import
 * para no tocar los consumidores del CRUD genérico que ya importan showToast
 * desde aquí.
 */
export function showToast(text: string, icon: 'success' | 'error' | 'warning' | 'info' = 'info') {
  showToastificationToast(text, icon)
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
  showToastificationSuccess(count)
}
