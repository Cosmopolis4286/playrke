import { reactive, computed, watch } from 'vue'
import * as yup from 'yup'

export function useRegisterValidation(form: any) {
  const errors = reactive<Record<string, string>>({})

  const schema = yup.object({
    email: yup.string().required('El E-mail es obligatorio').email('El E-mail no es válido'),
    username: yup
      .string()
      .required('El nombre de usuario es obligatorio')
      .min(3, 'Debe tener al menos 3 caracteres'),
    country: yup.string().required('Debe seleccionar un país'),
    password: yup
      .string()
      .required('La contraseña es obligatoria')
      .min(6, 'Debe tener al menos 6 caracteres'),
    password_confirmation: yup
      .string()
      .required('Debe repetir la contraseña')
      .oneOf([yup.ref('password')], 'Las contraseñas no coinciden'),
    politics: yup.bool().oneOf([true], 'Debe aceptar los términos y condiciones')
  })

  const validate = async () => {
    try {
      await schema.validate(form, { abortEarly: false })
      Object.keys(errors).forEach(k => delete errors[k])
      return true
    } catch (validationErrors: any) {
      Object.keys(errors).forEach(k => delete errors[k])
      validationErrors.inner.forEach((err: any) => {
        errors[err.path] = err.message
      })
      return false
    }
  }

  // Puedes hacer validación reactiva automática (opcional)
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
