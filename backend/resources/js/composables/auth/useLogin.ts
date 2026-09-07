import { reactive, ref, computed } from 'vue'
import { useToast } from 'vue-toastification'
import backendClient, { getCsrfCookie } from '@js/plugins/axios'
import { loginSchema } from './loginSchema'
import { useRememberedLogin } from './useRememberedLogin'

interface LoginPayload {
  login: string
  password: string
  remember: boolean
}

export function useLogin() {
  const toast = useToast()

  const rememberedLogin = useRememberedLogin()
  const form = reactive<LoginPayload>({ login: rememberedLogin.get(), password: '', remember: false })
  const loading = ref(false)
  const isBusy = computed(() => loading.value)
  const errors = reactive<Record<string, string>>({})

  // Turnstile solo se exige tras intentos fallidos previos (ver
  // App\Http\Requests\Auth\LoginRequest::rules()) — este flag se activa
  // cuando el backend responde 422 pidiendo 'cf-turnstile-response', y la
  // vista reacciona mostrando el widget para el siguiente intento.
  const requiresTurnstile = ref(false)

  function validate(): boolean {
    Object.keys(errors).forEach(key => delete errors[key])

    const result = loginSchema.safeParse(form)
    if (result.success) return true

    for (const issue of result.error.issues) {
      const path = issue.path[0]
      if (typeof path === 'string' && !errors[path]) {
        errors[path] = issue.message
      }
    }
    return false
  }

  const submit = async (turnstileToken?: string): Promise<void> => {
    if (isBusy.value) return

    if (!validate()) {
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
        remember: form.remember,
        ...(turnstileToken ? { 'cf-turnstile-response': turnstileToken } : {}),
      })
      rememberedLogin.set(form.login)
      toast.success('¡Bienvenido! Redirigiendo...')
      window.location.replace('/dashboard')
    } catch (error: any) {
      const status = error?.response?.status
      const serverErrors = error?.response?.data?.errors

      if (serverErrors?.['cf-turnstile-response']) {
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

  return { form, errors, loading, isBusy, requiresTurnstile, submit }
}
