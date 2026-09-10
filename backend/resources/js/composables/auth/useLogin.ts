import { reactive } from 'vue'
import backendClient, { getCsrfCookie } from '@js/plugins/axios'
import { loginSchema } from './loginSchema'
import { useRememberedLogin } from './useRememberedLogin'
import { useValidatedForm } from '@js/composables/form/useValidatedForm'
import { ref } from 'vue'

interface LoginPayload {
  login: string
  password: string
  remember: boolean
}

export function useLogin() {
  const rememberedLogin = useRememberedLogin()
  const form = reactive<LoginPayload>({ login: rememberedLogin.get(), password: '', remember: false })

  const { loading, isBusy, errors, touched, submitted, markTouched, fieldError, validate, reportHttpError, toast } =
    useValidatedForm(form, loginSchema)

  // Turnstile solo se exige tras intentos fallidos previos (ver
  // App\Http\Requests\Auth\LoginRequest::rules()) — este flag se activa
  // cuando el backend responde 422 pidiendo 'cf-turnstile-response', y la
  // vista reacciona mostrando el widget para el siguiente intento.
  const requiresTurnstile = ref(false)

  const submit = async (turnstileToken?: string): Promise<void> => {
    submitted.value = true
    if (isBusy.value) return

    if (!validate()) {
      toast.warning('Revisa los campos marcados antes de continuar.')
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
      const serverErrors = error?.response?.data?.errors
      if (serverErrors?.['cf-turnstile-response']) {
        requiresTurnstile.value = true
      }

      reportHttpError(error, { validation: 'Credenciales incorrectas. Verifica tu usuario y contraseña.' })
    } finally {
      loading.value = false
    }
  }

  return { form, errors, touched, submitted, markTouched, fieldError, loading, isBusy, requiresTurnstile, submit }
}
