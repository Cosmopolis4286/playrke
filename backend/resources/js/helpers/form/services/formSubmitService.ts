import axios from '@/plugins/axios'
import { showToast } from '@/utils/toastNotify'
import { ajaxStart, ajaxStop } from '@/utils/loader'
import { showPasswordModal } from '@/helpers/user/password'
import { validateAndFocus } from '@/utils/form/fieldValidation'

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

export async function submitForm(formId: string) {
  const form = document.getElementById(formId) as HTMLFormElement | null
  if (!form) return

  const isValid = validateAndFocus(formId)

  if (!isValid) return

  try {
    ajaxStart()

    const { data } = await submitFormService({ form })

    if (!data?.success) {
      throw new Error(data?.message)
    }

    const passwordResponse = data?.password ? { data: { password: data.password } } : null
    let timeout: number | undefined = undefined

    $(`#modal-${formId}`).modal('hide')

    showToast(data.message || 'Operación exitosa', 'success')

    if (passwordResponse?.data?.password) {
      await showPasswordModal(passwordResponse.data.password)
      timeout = 2000
    }

    setTimeout(() => window.location.reload(), timeout ?? 1500)
  } catch (error: any) {
    showToast(error.response?.data?.message || 'Error al enviar formulario', 'error')
  } finally {
    ajaxStop()
  }
}
