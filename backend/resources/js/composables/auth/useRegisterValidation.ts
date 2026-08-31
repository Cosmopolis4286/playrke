import { reactive, watch } from 'vue'
import { buildRegisterSchema } from './registerSchema'

/**
 * Composable para validar el formulario de registro usando Zod.
 * Realiza validación reactiva y acumula mensajes de error por campo.
 *
 * @param form Estado reactivo del formulario.
 * @param isoCode2 Ref/computed con el ISO-2 del país seleccionado (para validar el patrón de phone).
 */
export function useRegisterValidation(
  form: Record<string, any>,
  isoCode2?: { value: string | null | undefined }
) {
  const errors = reactive<Record<string, string>>({})

  const validate = async (): Promise<boolean> => {
    const schema = buildRegisterSchema(isoCode2?.value)
    const result = schema.safeParse(form)

    Object.keys(errors).forEach(key => delete errors[key])

    if (result.success) {
      return true
    }

    for (const issue of result.error.issues) {
      const path = issue.path[0]
      if (typeof path === 'string' && !errors[path]) {
        errors[path] = issue.message
      }
    }

    return false
  }

  watch(
    form,
    () => {
      validate()
    },
    { deep: true }
  )

  return {
    errors,
    validate,
  }
}
