import { reactive, ref, computed } from 'vue'
import backendClient, { getCsrfCookie } from '@js/plugins/axios'

/**
 * Interface que define los campos del formulario de login.
 */
interface LoginPayload {
  login: string
  password: string
}

/**
 * Composable para manejar el estado y lógica del formulario de login.
 * @returns {object} Estado reactivo, errores, loading y función submit.
 */
export function useLogin() {
  // Estado reactivo para los campos del formulario
  const form = reactive<LoginPayload>({
    login: '',
    password: ''
  })

  // Estado reactivo para almacenar errores específicos del formulario
  const errors = reactive({
    login: ''
  })

  // Indica si la petición está en curso (loading)
  const loading = ref(false)

  // Computed para simplificar el chequeo si el formulario está ocupado
  const isBusy = computed(() => loading.value)

  /**
   * Envía el formulario de login al backend.
   * Realiza validación básica y maneja errores devueltos.
   */
  const submit = async (): Promise<void> => {
    if (isBusy.value) return

    // Limpiar errores previos antes de validar
    errors.login = ''

    // Validación básica en cliente
    if (!form.login || !form.password) {
      errors.login = 'Todos los campos son obligatorios'
      return
    }

    loading.value = true

    try {
      // Obtener cookie CSRF para proteger la petición
      await getCsrfCookie()

      // Enviar credenciales al backend
      await backendClient.post('login', {
        login: form.login,
        password: form.password
      })

      // Redirección tras login exitoso
      window.location.replace('/dashboard')
    } catch (error: any) {
      // Manejo de errores del backend y errores inesperados
      errors.login =
        error?.response?.status === 422
          ? 'Credenciales inválidas'
          : 'Error inesperado. Intenta nuevamente.'
    } finally {
      loading.value = false
    }
  }

  // Retornar propiedades y funciones para usar en el componente
  return {
    form,
    errors,
    loading,
    isBusy,
    submit
  }
}
