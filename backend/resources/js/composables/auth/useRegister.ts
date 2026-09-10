import { reactive, computed } from 'vue'
import backendClient, { getCsrfCookie } from '@js/plugins/axios'
import { buildRegisterSchema } from './registerSchema'
import { useValidatedForm } from '@js/composables/form/useValidatedForm'
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

  const selectedIso2 = computed<string | null>(() => {
    if (!form.country) return null
    // eslint-disable-next-line eqeqeq
    const match = countries.find(c => c.id == form.country)
    return match?.iso_code_2 ?? null
  })

  const { loading, isBusy, errors, touched, submitted, markTouched, fieldError, validate, reportHttpError, toast } =
    useValidatedForm(form, () => buildRegisterSchema(selectedIso2.value))

  const submit = async (turnstileToken?: string) => {
    submitted.value = true
    if (isBusy.value) return

    if (!validate()) {
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
      reportHttpError(error)
    } finally {
      loading.value = false
    }
  }

  return { form, errors, loading, isBusy, submit, touched, submitted, markTouched, fieldError, selectedIso2 }
}
