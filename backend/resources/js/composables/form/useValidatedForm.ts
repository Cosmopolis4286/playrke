import { reactive, ref, computed } from 'vue'
import { useToast } from 'vue-toastification'
import type { ZodType } from 'zod'

/**
 * Base compartida por formularios públicos y de Auth (login, register,
 * forgot-password, reset-password, verify-card): validación Zod por campo,
 * estado touched/submitted para feedback progresivo, y clasificación
 * uniforme de errores HTTP (422/403/404/429/network/generic) — antes cada
 * composable duplicaba este switch con pequeñas inconsistencias (p.ej. solo
 * useRegister pintaba los errores 422 del backend en los campos; los demás
 * solo mostraban un toast genérico).
 */

export interface HttpErrorMessages {
  /** 422 - Unprocessable Entity: validación rechazada por el backend. */
  validation?: string
  /** 403 - Forbidden: la acción fue rechazada explícitamente (no es un error de validación). */
  forbidden?: string
  /** 404 - Not Found. */
  notFound?: string
  /** 429 - Too Many Requests. */
  throttled?: string
  /** Sin respuesta del servidor: timeout, offline, DNS, CORS, etc. */
  network?: string
  /** Cualquier otro status (500, etc). */
  generic?: string
}

const DEFAULT_MESSAGES: Required<HttpErrorMessages> = {
  validation: 'Los datos ingresados no son válidos.',
  forbidden: 'No se pudo completar la acción.',
  notFound: 'No encontramos lo que buscas.',
  throttled: 'Demasiados intentos. Espera un momento e inténtalo de nuevo.',
  network: 'No pudimos conectar con el servidor. Revisa tu conexión e inténtalo de nuevo.',
  generic: 'Error inesperado. Por favor intenta nuevamente.',
}

export function useValidatedForm<T extends Record<string, any>>(
  form: T,
  schema: ZodType<any> | (() => ZodType<any>)
) {
  const toast = useToast()

  const loading = ref(false)
  const isBusy = computed(() => loading.value)
  const errors = reactive<Record<string, string>>({})
  const touched = reactive<Record<string, boolean>>({})
  const submitted = ref(false)

  function markTouched(field: string) {
    touched[field] = true
  }

  /** Un campo muestra su error solo tras submit o tras haber perdido foco una vez. */
  function fieldError(field: string): string | null {
    if (!submitted.value && !touched[field]) return null
    return errors[field] ?? null
  }

  function validate(): boolean {
    Object.keys(errors).forEach(key => delete errors[key])

    const resolvedSchema = typeof schema === 'function' ? schema() : schema
    const result = resolvedSchema.safeParse(form)
    if (result.success) return true

    for (const issue of result.error.issues) {
      const path = issue.path[0]
      if (typeof path === 'string' && !errors[path]) {
        errors[path] = issue.message
      }
    }
    return false
  }

  /**
   * Clasifica y reporta (toast + errores por campo) una excepción de axios.
   * Devuelve el status HTTP para que el composable llamante reaccione a
   * casos particulares (p.ej. useLogin activando Turnstile en un 422).
   */
  function reportHttpError(error: any, messages?: HttpErrorMessages): number | undefined {
    const msg = { ...DEFAULT_MESSAGES, ...messages }
    const status = error?.response?.status

    if (status === undefined) {
      // Sin `response`: la request nunca llegó a completarse (offline,
      // timeout, CORS) — distinto de un error HTTP real del backend.
      toast.error(msg.network)
      return undefined
    }

    if (status === 422) {
      const serverErrors = error?.response?.data?.errors
      if (serverErrors && typeof serverErrors === 'object') {
        Object.assign(errors, serverErrors)
        const first = Object.values(serverErrors)[0]
        toast.error(first ? (Array.isArray(first) ? first[0] : String(first)) : msg.validation)
      } else {
        toast.error(error?.response?.data?.message ?? msg.validation)
      }
    } else if (status === 403) {
      toast.error(error?.response?.data?.message ?? msg.forbidden)
    } else if (status === 404) {
      toast.error(error?.response?.data?.message ?? msg.notFound)
    } else if (status === 429) {
      toast.warning(msg.throttled)
    } else {
      toast.error(msg.generic)
    }

    return status
  }

  return {
    loading,
    isBusy,
    errors,
    touched,
    submitted,
    markTouched,
    fieldError,
    validate,
    reportHttpError,
    toast,
  }
}
