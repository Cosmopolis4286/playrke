import { reactive, ref, computed } from 'vue'
import { useToast } from 'vue-toastification'
import backendClient, { getCsrfCookie } from '@js/plugins/axios'

interface LoginPayload {
  login: string
  password: string
}

export function useLogin() {
  const toast = useToast()

  const form = reactive<LoginPayload>({ login: '', password: '' })
  const loading = ref(false)
  const isBusy = computed(() => loading.value)

  const submit = async (): Promise<void> => {
    if (isBusy.value) return

    if (!form.login || !form.password) {
      toast.warning('Completa todos los campos antes de continuar.')
      return
    }

    loading.value = true

    try {
      await getCsrfCookie()
      await backendClient.post('login', { login: form.login, password: form.password })
      toast.success('¡Bienvenido! Redirigiendo...')
      window.location.replace('/dashboard')
    } catch (error: any) {
      const status = error?.response?.status

      if (status === 422) {
        toast.error('Credenciales incorrectas. Verifica tu usuario y contraseña.')
      } else if (status === 429) {
        toast.warning('Demasiados intentos. Espera un momento e inténtalo de nuevo.')
      } else {
        toast.error('Error inesperado. Por favor intenta nuevamente.')
      }
    } finally {
      loading.value = false
    }
  }

  return { form, loading, isBusy, submit }
}
