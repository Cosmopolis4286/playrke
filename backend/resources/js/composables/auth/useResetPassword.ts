import { reactive } from 'vue'
import backendClient, { getCsrfCookie } from '@js/plugins/axios'
import { resetPasswordSchema } from './resetPasswordSchema'
import { useValidatedForm } from '@js/composables/form/useValidatedForm'

interface ResetPasswordPayload {
  token: string
  email: string
  password: string
  password_confirmation: string
}

/**
 * Envía la nueva contraseña a POST /resetPassword (NewPasswordController::store,
 * ruta 'password.store') — token+email vienen del link firmado del correo,
 * pre-rellenados por Auth/ResetPassword.vue vía props de Inertia.
 */
export function useResetPassword(token: string, email: string) {
  const form = reactive<ResetPasswordPayload>({
    token,
    email,
    password: '',
    password_confirmation: '',
  })

  const { loading, isBusy, errors, touched, submitted, markTouched, fieldError, validate, reportHttpError, toast } =
    useValidatedForm(form, resetPasswordSchema)

  const submit = async (): Promise<void> => {
    submitted.value = true
    if (isBusy.value) return

    if (!validate()) {
      toast.warning('Revisa los campos marcados antes de continuar.')
      return
    }

    loading.value = true

    try {
      await getCsrfCookie()
      await backendClient.post('/resetPassword', { ...form })
      toast.success('Contraseña restablecida. Ya puedes iniciar sesión.')
      window.location.replace('/login')
    } catch (error: any) {
      reportHttpError(error, { validation: 'El link de recuperación es inválido o ya expiró.' })
    } finally {
      loading.value = false
    }
  }

  return { form, errors, touched, submitted, markTouched, fieldError, loading, isBusy, submit }
}
