import { reactive, ref, computed } from 'vue'
import backendClient, { getCsrfCookie } from '@js/plugins/axios'
import { useRegisterValidation } from './useRegisterValidation'

/**
 * Interfaz que define la estructura del formulario de registro.
 */
interface RegisterForm {
  email: string
  username: string
  country: string | number
  password: string
  password_confirmation: string
  politics: boolean
}

/**
 * Composable para manejar el formulario de registro:
 * - Estado reactivo del formulario
 * - Validación con yup (via useRegisterValidation)
 * - Control de loading y bloqueo de inputs
 * - Control de campos tocados (touched) para mostrar errores solo tras interacción
 * - Control de flag submitted para mostrar errores tras primer submit
 * - Envío al backend y manejo de errores
 *
 * @param initialValues Valores iniciales para el formulario (opcional)
 * @returns Objetos y métodos para manejar el formulario en componente
 */
export function useRegisterForm(initialValues?: Partial<RegisterForm>) {
  const form = reactive<RegisterForm>({
    email: '',
    username: '',
    country: '',
    password: '',
    password_confirmation: '',
    politics: false,
    ...initialValues
  })

  const loading = ref(false)
  const isBusy = computed(() => loading.value)

  const { errors, validate } = useRegisterValidation(form)

  /** Flag para saber si el usuario ya intentó enviar el formulario */
  const submitted = ref(false)

  /** Estado reactivo para marcar qué campos fueron tocados/interactuados */
  const touched = reactive<Record<string, boolean>>({})

  /**
   * Marca un campo como tocado.
   * Usar en eventos @input o @change de los inputs para activar la visualización de errores.
   *
   * @param field Nombre del campo a marcar como tocado
   */
  function markTouched(field: string) {
    touched[field] = true
  }

  /**
   * Envía el formulario al backend.
   * Ejecuta validación y evita enviar si está cargando o si la validación falla.
   * Actualiza los errores basados en la respuesta del backend.
   */
  const submit = async () => {
    submitted.value = true

    if (isBusy.value) return

    const valid = await validate()
    if (!valid) return

    loading.value = true
    try {
      await getCsrfCookie()
      await backendClient.post('/api/auth/register', form)
      window.location.replace('/dashboard')
    } catch (error: any) {
      if (error?.response?.status === 422) {
        Object.assign(errors, error.response.data.errors)
      } else {
        errors.email = 'Error inesperado. Intenta nuevamente.'
      }
    } finally {
      loading.value = false
    }
  }

  return {
    form,
    errors,
    loading,
    isBusy,
    submit,
    touched,
    submitted,
    markTouched
  }
}
