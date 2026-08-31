import { reactive, ref, computed } from 'vue'
import { useToast } from 'vue-toastification'
import backendClient, { getCsrfCookie } from '@js/plugins/axios'
import { forgotPasswordSchema } from './forgotPasswordSchema'

export function useForgotPassword() {
  const toast = useToast()

  const form = reactive({ email: '' })
  const loading = ref(false)
  const isBusy = computed(() => loading.value)
  const errors = reactive<Record<string, string>>({})

  function validate(): boolean {
    Object.keys(errors).forEach(key => delete errors[key])

    const result = forgotPasswordSchema.safeParse(form)
    if (result.success) return true

    for (const issue of result.error.issues) {
      const path = issue.path[0]
      if (typeof path === 'string' && !errors[path]) {
        errors[path] = issue.message
      }
    }
    return false
  }

  const submit = async (): Promise<void> => {
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
    } catch (error: any) {
      const status = error?.response?.status

      if (status === 422) {
        toast.error('Ingresa un correo electrónico válido.')
      } else if (status === 429) {
        toast.warning('Demasiadas solicitudes. Espera unos minutos.')
      } else {
        toast.error('Error inesperado. Por favor intenta nuevamente.')
      }
    } finally {
      loading.value = false
    }
  }

  return { form, errors, loading, isBusy, submit }
}
