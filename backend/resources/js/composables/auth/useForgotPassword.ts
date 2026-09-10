import { reactive } from 'vue'
import backendClient, { getCsrfCookie } from '@js/plugins/axios'
import { forgotPasswordSchema } from './forgotPasswordSchema'
import { useValidatedForm } from '@js/composables/form/useValidatedForm'

export function useForgotPassword() {
  const form = reactive({ email: '' })

  const { loading, isBusy, errors, touched, submitted, markTouched, fieldError, validate, reportHttpError, toast } =
    useValidatedForm(form, forgotPasswordSchema)

  const submit = async (): Promise<void> => {
    submitted.value = true
    if (isBusy.value) return

    if (!validate()) {
      toast.warning('Ingresa una dirección de correo electrónico válida.')
      return
    }

    loading.value = true

    try {
      await getCsrfCookie()
      await backendClient.post('/forgot-password', { email: form.email.trim() })
      // Mensaje siempre genérico, sin importar si el email existe — el
      // backend deliberadamente no distingue (evita enumeración de cuentas
      // registradas), así que el frontend tampoco debe hacerlo.
      toast.success('Si el correo está registrado, te enviamos un link de recuperación.')
      form.email = ''
      submitted.value = false
      touched.email = false
    } catch (error: any) {
      reportHttpError(error, { validation: 'Ingresa un correo electrónico válido.' })
    } finally {
      loading.value = false
    }
  }

  return { form, errors, touched, submitted, markTouched, fieldError, loading, isBusy, submit }
}
