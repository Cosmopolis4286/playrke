import { reactive, ref, computed } from 'vue'
import { useToast } from 'vue-toastification'
import backendClient, { getCsrfCookie } from '@js/plugins/axios'
import { useRegisterValidation } from './useRegisterValidation'
import type { Country } from '@js/types/country'

interface RegisterForm {
  email: string
  username: string
  country: string | number
  phone: string
  password: string
  password_confirmation: string
  terms: boolean
}

export function useRegisterForm(countries: Country[], initialValues?: Partial<RegisterForm>) {
  const toast = useToast()

  const form = reactive<RegisterForm>({
    email: '',
    username: '',
    country: '',
    phone: '',
    password: '',
    password_confirmation: '',
    terms: false,
    ...initialValues,
  })

  const loading = ref(false)
  const isBusy = computed(() => loading.value)
  const submitted = ref(false)
  const touched = reactive<Record<string, boolean>>({})

  const selectedIso2 = computed<string | null>(() => {
    if (!form.country) return null
    // eslint-disable-next-line eqeqeq
    const match = countries.find(c => c.id == form.country)
    return match?.iso_code_2 ?? null
  })

  const { errors, validate } = useRegisterValidation(form, selectedIso2)

  function markTouched(field: string) {
    touched[field] = true
  }

  const submit = async (turnstileToken?: string) => {
    submitted.value = true
    if (isBusy.value) return

    const valid = await validate()
    if (!valid) {
      toast.warning('Revisa los campos marcados antes de continuar.')
      return
    }

    if (!turnstileToken) {
      toast.warning('Completa la verificación de seguridad.')
      return
    }

    loading.value = true
    try {
      await getCsrfCookie()
      await backendClient.post('register', {
        ...form,
        email: form.email.trim(),
        username: form.username.trim(),
        phone: form.phone.trim(),
        'cf-turnstile-response': turnstileToken,
      })
      toast.success('¡Cuenta creada! Redirigiendo...')
      window.location.replace('/dashboard')
    } catch (error: any) {
      const status = error?.response?.status

      if (status === 422) {
        const serverErrors = error.response.data.errors ?? {}
        Object.assign(errors, serverErrors)
        // Mostrar el primer error del servidor como toast
        const first = Object.values(serverErrors)[0]
        if (first) toast.error(Array.isArray(first) ? first[0] : String(first))
        else toast.error('Los datos ingresados no son válidos.')
      } else if (status === 429) {
        toast.warning('Demasiados intentos. Espera un momento e inténtalo de nuevo.')
      } else {
        toast.error('Error inesperado. Por favor intenta nuevamente.')
      }
    } finally {
      loading.value = false
    }
  }

  return { form, errors, loading, isBusy, submit, touched, submitted, markTouched, selectedIso2 }
}
