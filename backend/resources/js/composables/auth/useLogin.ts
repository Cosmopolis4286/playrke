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

  // Turnstile solo se exige tras intentos fallidos previos (ver
  // App\Http\Requests\Auth\LoginRequest::rules()) — este flag se activa
  // cuando el backend responde 422 pidiendo 'cf-turnstile-response', y la
  // vista reacciona mostrando el widget para el siguiente intento.
  const requiresTurnstile = ref(false)

  const submit = async (turnstileToken?: string): Promise<void> => {
    if (isBusy.value) return

    if (!form.login || !form.password) {
      toast.warning('Completa todos los campos antes de continuar.')
      return
    }

    if (requiresTurnstile.value && !turnstileToken) {
      toast.warning('Completa la verificación de seguridad.')
      return
    }

    loading.value = true

    try {
      await getCsrfCookie()
      await backendClient.post('login', {
        login: form.login,
        password: form.password,
        ...(turnstileToken ? { 'cf-turnstile-response': turnstileToken } : {}),
      })
      toast.success('¡Bienvenido! Redirigiendo...')
      window.location.replace('/dashboard')
    } catch (error: any) {
      const status = error?.response?.status
      const errors = error?.response?.data?.errors

      if (errors?.['cf-turnstile-response']) {
        requiresTurnstile.value = true
      }

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

  return { form, loading, isBusy, requiresTurnstile, submit }
}
