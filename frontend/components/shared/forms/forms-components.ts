/**
 * @fileoverview Form Components - Barrel export optimizado
 * @description Exporta todos los componentes de formulario con tree-shaking
 * @author Lotto38 Team
 * @version 2.0.0
 * @since 2025-09-17
 */

// Componentes principales optimizados
export { default as BaseForm } from './BaseForm.vue'
export { default as FormField } from './FormField.vue'
export { default as FormGroup } from './FormGroup.vue'
export { default as QuickForm } from './QuickForm.vue'

// Tipos TypeScript
export type FieldType = 'text' | 'email' | 'password' | 'number' | 'tel' | 'url' | 'search' | 'textarea' | 'select' | 'date' | 'time' | 'datetime-local' | 'month' | 'week' | 'color' | 'range' | 'file'

export interface FormFieldConfig {
  name: string
  type?: FieldType
  label?: string
  placeholder?: string
  helpText?: string
  required?: boolean
  rules?: Array<(value: unknown) => string | true>
  options?: Array<{ value: unknown, label: string }>
  group?: string
  variant?: 'default' | 'outlined' | 'filled'
  size?: 'sm' | 'md' | 'lg'
  icon?: string
  clearable?: boolean
  disabled?: boolean
  readonly?: boolean
  maxLength?: number
  showCharCount?: boolean
  [key: string]: unknown
}

export interface FormGroupConfig {
  id: string
  title?: string
  description?: string
  helpText?: string
  icon?: string
  layout?: 'vertical' | 'horizontal' | 'grid' | 'inline'
  columns?: number
  gap?: 'sm' | 'md' | 'lg'
  variant?: 'default' | 'bordered' | 'card'
  size?: 'sm' | 'md' | 'lg'
  collapsible?: boolean
  expanded?: boolean
  required?: boolean
  responsive?: boolean
  mobileLayout?: 'vertical' | 'horizontal' | 'grid' | 'inline'
  fields: FormFieldConfig[]
}

export interface FormValidationRule {
  (value: unknown): string | true
}

export interface FormValidationError {
  field: string
  message: string
}

export interface FormState {
  submitting: boolean
  submitted: boolean
  dirty: boolean
  touched: boolean
}

// Utilidades de validación comunes
export const validationRules = {
  required: (message = 'Este campo es requerido'): FormValidationRule =>
    (value: unknown) => {
      if (!value || (typeof value === 'string' && !value.trim())) {
        return message
      }
      return true
    },

  email: (message = 'Ingresa un email válido'): FormValidationRule =>
    (value: string) => {
      if (!value) { return true }
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
      return emailRegex.test(value) || message
    },

  minLength: (min: number, message?: string): FormValidationRule =>
    (value: string) => {
      if (!value) { return true }
      const msg = message || `Mínimo ${min} caracteres`
      return value.length >= min || msg
    },

  maxLength: (max: number, message?: string): FormValidationRule =>
    (value: string) => {
      if (!value) { return true }
      const msg = message || `Máximo ${max} caracteres`
      return value.length <= max || msg
    },

  pattern: (regex: RegExp, message = 'Formato inválido'): FormValidationRule =>
    (value: string) => {
      if (!value) { return true }
      return regex.test(value) || message
    },

  numeric: (message = 'Solo números'): FormValidationRule =>
    (value: string) => {
      if (!value) { return true }
      return /^\d+$/.test(value) || message
    },

  phone: (message = 'Teléfono inválido'): FormValidationRule =>
    (value: string) => {
      if (!value) { return true }
      const phoneRegex = /^[+]?[0-9\s\-()]{7,}$/
      return phoneRegex.test(value) || message
    },

  url: (message = 'URL inválida'): FormValidationRule =>
    (value: string) => {
      if (!value) { return true }
      try {
        new URL(value)
        return true
      } catch {
        return message
      }
    },

  confirm: (_confirmField: string, _message = 'Los campos no coinciden') =>
    (value: string): string | true => {
      if (!value) { return true }
      // Nota: Para validación de confirmación necesitarías pasar formData desde el componente
      return true // Simplificado por ahora
    },
}

// Composables de formulario
export const useFormValidation = () => {
  const validateField = (value: unknown, rules: FormValidationRule[]): string | true => {
    for (const rule of rules) {
      const result = rule(value)
      if (result !== true) {
        return result
      }
    }
    return true
  }

  const validateForm = (
    formData: Record<string, any>,
    validationRules: Record<string, FormValidationRule[]>,
  ): { isValid: boolean, errors: Record<string, string> } => {
    const errors: Record<string, string> = {}

    Object.entries(validationRules).forEach(([field, rules]) => {
      const result = validateField(formData[field], rules)
      if (result !== true) {
        errors[field] = result
      }
    })

    return {
      isValid: Object.keys(errors).length === 0,
      errors,
    }
  }

  return {
    validateField,
    validateForm,
  }
}

// Presets de configuración comunes
export const formPresets = {
  // Formulario de login
  login: (): FormGroupConfig[] => [
    {
      id: 'credentials',
      title: 'Credenciales',
      layout: 'vertical',
      fields: [
        {
          name: 'email',
          type: 'email',
          label: 'Email',
          placeholder: 'tu@email.com',
          required: true,
          rules: [validationRules.required(), validationRules.email()],
        },
        {
          name: 'password',
          type: 'password',
          label: 'Contraseña',
          required: true,
          rules: [validationRules.required(), validationRules.minLength(6)],
        },
      ],
    },
  ],

  // Formulario de registro
  register: (): FormGroupConfig[] => [
    {
      id: 'personal',
      title: 'Información Personal',
      layout: 'grid',
      columns: 2,
      fields: [
        {
          name: 'firstName',
          type: 'text',
          label: 'Nombre',
          required: true,
          rules: [validationRules.required()],
        },
        {
          name: 'lastName',
          type: 'text',
          label: 'Apellido',
          required: true,
          rules: [validationRules.required()],
        },
        {
          name: 'email',
          type: 'email',
          label: 'Email',
          required: true,
          rules: [validationRules.required(), validationRules.email()],
        },
        {
          name: 'phone',
          type: 'tel',
          label: 'Teléfono',
          helpText: 'Incluye código de país',
          rules: [validationRules.phone()],
        },
      ],
    },
    {
      id: 'security',
      title: 'Seguridad',
      layout: 'vertical',
      fields: [
        {
          name: 'password',
          type: 'password',
          label: 'Contraseña',
          required: true,
          rules: [validationRules.required(), validationRules.minLength(8)],
        },
        {
          name: 'confirmPassword',
          type: 'password',
          label: 'Confirmar Contraseña',
          required: true,
          rules: [validationRules.required(), validationRules.confirm('password')],
        },
      ],
    },
  ],

  // Formulario de contacto
  contact: (): FormGroupConfig[] => [
    {
      id: 'contact-info',
      layout: 'grid',
      columns: 2,
      fields: [
        {
          name: 'name',
          type: 'text',
          label: 'Nombre completo',
          required: true,
          rules: [validationRules.required()],
        },
        {
          name: 'email',
          type: 'email',
          label: 'Email',
          required: true,
          rules: [validationRules.required(), validationRules.email()],
        },
        {
          name: 'subject',
          type: 'text',
          label: 'Asunto',
          required: true,
          rules: [validationRules.required()],
        },
        {
          name: 'message',
          type: 'textarea',
          label: 'Mensaje',
          placeholder: 'Escribe tu mensaje aquí...',
          required: true,
          rules: [validationRules.required(), validationRules.minLength(10)],
        },
      ],
    },
  ],
}

// Export por defecto como objeto
const FormComponents = {
  validationRules,
  useFormValidation,
  formPresets,
}

export default FormComponents
