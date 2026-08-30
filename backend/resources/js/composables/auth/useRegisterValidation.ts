import { reactive, watch } from 'vue'
import * as yup from 'yup'

/**
 * Composable para validar el formulario de registro usando Yup.
 * Realiza validación reactiva y acumula mensajes de error.
 *
 * @param {object} form Estado reactivo del formulario.
 * @returns {object} errors reactivo y función validate para validar manualmente.
 */
export function useRegisterValidation(form: Record<string, any>) {
  // Errores reactivos indexados por campo
  const errors = reactive<Record<string, string>>({})

  // Esquema Yup con validaciones específicas y mensajes personalizados
  const schema = yup.object({
    email: yup.string().required('El E-mail es obligatorio').email('El E-mail no es válido'),
    username: yup
      .string()
      .required('El nombre de usuario es obligatorio')
      .min(3, 'Debe tener al menos 3 caracteres'),
    country: yup.string().required('Debe seleccionar un país'),
    phone: yup.string().optional(),
    password: yup
      .string()
      .required('La contraseña es obligatoria')
      .min(8, 'La contraseña debe tener al menos 8 caracteres')
      .matches(/[A-Z]/, 'Debe contener al menos una letra mayúscula')
      .matches(/[a-z]/, 'Debe contener al menos una letra minúscula')
      .matches(/\d/, 'Debe contener al menos un número')
      .matches(/[!@#$%^&*(),.?":{}|<>]/, 'Debe contener al menos un carácter especial'),
    password_confirmation: yup
      .string()
      .required('Debe repetir la contraseña')
      .oneOf([yup.ref('password')], 'Las contraseñas no coinciden'),
    terms: yup.bool().oneOf([true], 'Debe aceptar los términos y condiciones')
  })

  /**
   * Valida el formulario usando el esquema Yup.
   * Actualiza el objeto errors con los mensajes correspondientes.
   *
   * @returns {Promise<boolean>} true si es válido, false si hay errores.
   */
  const validate = async (): Promise<boolean> => {
    try {
      await schema.validate(form, { abortEarly: false })
      // Limpiar errores previos
      Object.keys(errors).forEach(key => delete errors[key])
      return true
    } catch (validationErrors: any) {
      // Limpiar errores previos
      Object.keys(errors).forEach(key => delete errors[key])
      // Mapear los errores del esquema a errors reactivo
      validationErrors.inner.forEach((err: any) => {
        errors[err.path] = err.message
      })
      return false
    }
  }

  // Validación reactiva automática: valida cada vez que el formulario cambia
  watch(
    form,
    () => {
      validate()
    },
    { deep: true }
  )

  return {
    errors,
    validate
  }
}
