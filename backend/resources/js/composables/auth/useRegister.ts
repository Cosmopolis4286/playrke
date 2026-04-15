import { reactive, ref, computed } from 'vue'
import { useToast } from 'vue-toastification'
import backendClient, { getCsrfCookie } from '@js/plugins/axios'
import { useRegisterValidation } from './useRegisterValidation'

interface RegisterForm {
  email: string
  username: string
  country: string | number
  password: string
  password_confirmation: string
  politics: boolean
}

export function useRegisterForm(initialValues?: Partial<RegisterForm>) {
  const toast = useToast()

  const form = reactive<RegisterForm>({
    email: '',
    username: '',
    country: '',
    password: '',
    password_confirmation: '',
    politics: false,
    ...initialValues,
  })

  const loading = ref(false)
  const isBusy = computed(() => loading.value)
  const submitted = ref(false)
  const touched = reactive<Record<string, boolean>>({})

  const { errors, validate } = useRegisterValidation(form)

  function markTouched(field: string) {
    touched[field] = true
  }

  const submit = async () => {
    submitted.value = true
    if (isBusy.value) return

    const valid = await validate()
    if (!valid) {
      toast.warning('Revisa los campos marcados antes de continuar.')
      return
    }

    loading.value = true
    try {
      await getCsrfCookie()
      await backendClient.post('/api/auth/register', form)
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
      } else {
        toast.error('Error inesperado. Por favor intenta nuevamente.')
      }
    } finally {
      loading.value = false
    }
  }

  return { form, errors, loading, isBusy, submit, touched, submitted, markTouched }
}
