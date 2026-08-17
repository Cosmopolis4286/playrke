import { reactive, ref, computed } from 'vue'
import { useToast } from 'vue-toastification'
import backendClient, { getCsrfCookie } from '@js/plugins/axios'

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
  const toast = useToast()

  const form = reactive<ResetPasswordPayload>({
    token,
    email,
    password: '',
    password_confirmation: '',
  })
  const loading = ref(false)
  const isBusy = computed(() => loading.value)

  const submit = async (): Promise<void> => {
    if (isBusy.value) return

    if (!form.password || !form.password_confirmation) {
      toast.warning('Completa todos los campos antes de continuar.')
      return
    }

    if (form.password !== form.password_confirmation) {
      toast.warning('Las contraseñas no coinciden.')
      return
    }

    loading.value = true

    try {
      await getCsrfCookie()
      await backendClient.post('/resetPassword', { ...form })
      toast.success('Contraseña restablecida. Ya puedes iniciar sesión.')
      window.location.replace('/login')
    } catch (error: any) {
      const status = error?.response?.status

      if (status === 422) {
        toast.error('El link de recuperación es inválido o ya expiró.')
      } else if (status === 429) {
        toast.warning('Demasiados intentos. Espera unos minutos.')
      } else {
        toast.error('Error inesperado. Por favor intenta nuevamente.')
      }
    } finally {
      loading.value = false
    }
  }

  return { form, loading, isBusy, submit }
}
