import axios from '@js/plugins/axios'
import { showToast } from '@js/utils/toastNotify'
import { ajaxStart, ajaxStop } from '@/js/utils/loader/loaderAjax'
import { validateAndFocus } from '@js/utils/form/fieldValidation'

export type SubmitMethod = 'post' | 'put' | 'patch' | 'delete'

interface SubmitFormOptions {
  form: HTMLFormElement
  method?: SubmitMethod
  url?: string
  headers?: Record<string, string>
}

/**
 * Servicio único y reutilizable para enviar formularios vía Axios
 * No maneja UI, loaders ni validaciones
 */
async function submitFormService({ form, method, url, headers = {} }: SubmitFormOptions) {
  if (!form.action && !url) {
    throw new Error('submitFormService: form.action o url es requerido')
  }

  const formData = new FormData(form)

  // Obtener método real: si se pasa explícitamente, usarlo,
  // si no, verificar si hay _method oculto en el formData,
  // si no, usar form.method o 'post' por defecto
  const formMethod = form.method?.toLowerCase() as SubmitMethod | undefined
  const hiddenMethod = formData.get('_method') as string | null

  const realMethod = method || (hiddenMethod ? hiddenMethod.toLowerCase() : formMethod) || 'post'

  return axios.request({
    url: url || form.action,
    method: realMethod,
    data: formData,
    headers
  })
}

/**
 * Envía un formulario HTML al backend manejando validación,
 * estado de carga, feedback visual y manejo de errores.
 *
 * @template T Tipo esperado de la respuesta (`data`) devuelta por el backend.
 *
 * @param formId - ID del elemento `<form>` en el DOM.
 *
 * @returns
 * - `T` si la operación fue exitosa (`data.success === true`)
 * - `null` si el formulario no existe, falla la validación o ocurre un error
 *
 * @remarks
 * - Ejecuta `validateAndFocus` antes de enviar el formulario.
 * - Inicia y detiene indicadores globales de carga (`ajaxStart` / `ajaxStop`).
 * - Cierra automáticamente el modal asociado (`#modal-${formId}`) en caso de éxito.
 * - Muestra notificaciones toast tanto en éxito como en error.
 *
 * @throws
 * No lanza excepciones hacia el consumidor; cualquier error es capturado
 * y gestionado internamente retornando `null`.
 *
 * @example
 * ```ts
 * const result = await submitForm<UserResponse>('user-form')
 * if (result) {
 *   console.log(result.data)
 * }
 * ```
 */
export async function submitForm<T = any>(formId: string): Promise<T | null> {
  const form = document.getElementById(formId) as HTMLFormElement | null
  if (!form) return null

  if (!validateAndFocus(formId)) return null

  ajaxStart()

  try {
    const { data } = await submitFormService({ form })

    if (!data?.success) {
      throw new Error(data?.message ?? 'Error en la operación')
    }

    $(`#modal-${formId}`).modal('hide')
    showToast(data.message ?? 'Operación exitosa', 'success')

    return data as T
  } catch (error: any) {
    const message = error?.response?.data?.message ?? error?.message ?? 'Error al enviar formulario'

    showToast(message, 'error')
    return null
  } finally {
    ajaxStop()
  }
}
