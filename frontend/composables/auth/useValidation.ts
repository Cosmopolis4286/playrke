import { ref, computed, type Ref } from 'vue'

export interface ValidationRule {
  (value: any): string | true
}

export interface ValidationRules {
  [field: string]: ValidationRule[]
}

export const useValidation = (rules: ValidationRules) => {
  const errors = ref<Record<string, string>>({})

  // Reglas de validación comunes
  const commonRules = {
    required: (message = 'Este campo es requerido') => (value: any): string | true => {
      if (!value || (typeof value === 'string' && !value.trim())) {
        return message
      }
      return true
    },

    email: (message = 'Ingresa un email válido') => (value: string): string | true => {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
      if (!emailRegex.test(value)) {
        return message
      }
      return true
    },

    minLength: (length: number, message?: string) => (value: string): string | true => {
      if (value.length < length) {
        return message || `Mínimo ${length} caracteres`
      }
      return true
    },

    maxLength: (length: number, message?: string) => (value: string): string | true => {
      if (value.length > length) {
        return message || `Máximo ${length} caracteres`
      }
      return true
    },

    password: (message = 'La contraseña debe tener al menos 8 caracteres') => (value: string): string | true => {
      if (value.length < 8) {
        return message
      }
      return true
    },

    strongPassword: (message = 'La contraseña debe contener mayúsculas, minúsculas, números y símbolos') => (value: string): string | true => {
      const strongRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/
      if (!strongRegex.test(value)) {
        return message
      }
      return true
    },

    confirmPassword: (password: Ref<string>, message = 'Las contraseñas no coinciden') => (value: string): string | true => {
      if (value !== password.value) {
        return message
      }
      return true
    },

    username: (message = 'El usuario debe tener entre 3 y 30 caracteres alfanuméricos') => (value: string): string | true => {
      const usernameRegex = /^[a-zA-Z0-9_]{3,30}$/
      if (!usernameRegex.test(value)) {
        return message
      }
      return true
    },

    phone: (message = 'Ingresa un número de teléfono válido') => (value: string): string | true => {
      const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/
      if (!phoneRegex.test(value.replace(/[\s\-\(\)]/g, ''))) {
        return message
      }
      return true
    },

    referralCode: (message = 'Código de referido inválido') => (value: string): string | true => {
      const codeRegex = /^[A-Z0-9]{6,20}$/
      if (value && !codeRegex.test(value)) {
        return message
      }
      return true
    },

    twoFactorCode: (message = 'Código de 6 dígitos requerido') => (value: string): string | true => {
      const codeRegex = /^\d{6}$/
      if (!codeRegex.test(value)) {
        return message
      }
      return true
    },

    terms: (message = 'Debes aceptar los términos y condiciones') => (value: boolean): string | true => {
      if (!value) {
        return message
      }
      return true
    }
  }

  // Validar un campo específico
  const validateField = (field: string, value: any): string | true => {
    const fieldRules = rules[field] || []
    
    for (const rule of fieldRules) {
      const result = rule(value)
      if (result !== true) {
        errors.value[field] = result
        return result
      }
    }

    // Si pasó todas las validaciones, limpiar error
    delete errors.value[field]
    return true
  }

  // Validar todos los campos
  const validateForm = (formData: Record<string, any>): boolean => {
    let isValid = true
    
    for (const field in rules) {
      const result = validateField(field, formData[field])
      if (result !== true) {
        isValid = false
      }
    }
    
    return isValid
  }

  // Limpiar errores
  const clearErrors = () => {
    errors.value = {}
  }

  const clearFieldError = (field: string) => {
    delete errors.value[field]
  }

  // Obtener error de un campo
  const getError = (field: string): string => {
    return errors.value[field] || ''
  }

  // Verificar si un campo tiene error
  const hasError = (field: string): boolean => {
    return !!errors.value[field]
  }

  // Verificar si hay errores
  const hasErrors = computed(() => {
    return Object.keys(errors.value).length > 0
  })

  return {
    errors,
    commonRules,
    validateField,
    validateForm,
    clearErrors,
    clearFieldError,
    getError,
    hasError,
    hasErrors
  }
}

// Composable específico para validaciones de autenticación
export const useAuthValidation = () => {
  const loginRules: ValidationRules = {
    email: [
      commonRules.required('El email es requerido'),
      commonRules.email()
    ],
    password: [
      commonRules.required('La contraseña es requerida'),
      commonRules.minLength(8)
    ],
    code: [] // Se añade dinámicamente para 2FA
  }

  const registerRules = (password: Ref<string>): ValidationRules => ({
    username: [
      commonRules.required('El nombre de usuario es requerido'),
      commonRules.username()
    ],
    first_name: [
      commonRules.required('El nombre es requerido'),
      commonRules.minLength(2, 'Mínimo 2 caracteres'),
      commonRules.maxLength(100, 'Máximo 100 caracteres')
    ],
    last_name: [
      commonRules.required('El apellido es requerido'),
      commonRules.minLength(2, 'Mínimo 2 caracteres'),
      commonRules.maxLength(100, 'Máximo 100 caracteres')
    ],
    email: [
      commonRules.required('El email es requerido'),
      commonRules.email()
    ],
    password: [
      commonRules.required('La contraseña es requerida'),
      commonRules.password(),
      commonRules.strongPassword()
    ],
    password_confirmation: [
      commonRules.required('Confirma tu contraseña'),
      commonRules.confirmPassword(password)
    ],
    country_id: [
      commonRules.required('Selecciona tu país')
    ],
    phone_number: [
      commonRules.phone()
    ],
    referral_code: [
      commonRules.referralCode()
    ],
    terms: [
      commonRules.terms()
    ]
  })

  const forgotPasswordRules: ValidationRules = {
    email: [
      commonRules.required('El email es requerido'),
      commonRules.email()
    ]
  }

  const resetPasswordRules = (password: Ref<string>): ValidationRules => ({
    email: [
      commonRules.required('El email es requerido'),
      commonRules.email()
    ],
    password: [
      commonRules.required('La contraseña es requerida'),
      commonRules.password(),
      commonRules.strongPassword()
    ],
    password_confirmation: [
      commonRules.required('Confirma tu contraseña'),
      commonRules.confirmPassword(password)
    ]
  })

  const twoFactorRules: ValidationRules = {
    code: [
      commonRules.required('El código es requerido'),
      commonRules.twoFactorCode()
    ]
  }

  const changePasswordRules = (newPassword: Ref<string>): ValidationRules => ({
    current_password: [
      commonRules.required('La contraseña actual es requerida')
    ],
    password: [
      commonRules.required('La nueva contraseña es requerida'),
      commonRules.password(),
      commonRules.strongPassword()
    ],
    password_confirmation: [
      commonRules.required('Confirma la nueva contraseña'),
      commonRules.confirmPassword(newPassword)
    ]
  })

  const { commonRules } = useValidation({})

  return {
    loginRules,
    registerRules,
    forgotPasswordRules,
    resetPasswordRules,
    twoFactorRules,
    changePasswordRules,
    useValidation
  }
}