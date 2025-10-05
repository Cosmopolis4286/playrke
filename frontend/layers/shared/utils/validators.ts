/**
 * Utilidades para validación de datos
 *
 * Proporciona funciones reutilizables para validar:
 * - Emails
 * - Contraseñas
 * - URLs
 * - Números
 * - Formularios
 */

/**
 * Valida un email
 *
 * @param email Email a validar
 * @returns True si el email es válido
 */
export function isValidEmail(email: string): boolean {
  if (!email) return false

  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return emailRegex.test(email)
}

/**
 * Valida que una contraseña sea segura
 *
 * @param password Contraseña a validar
 * @param minLength Longitud mínima (default: 8)
 * @returns Objeto con resultado y mensaje
 */
export function isValidPassword(
  password: string,
  minLength: number = 8
): { valid: boolean; message?: string } {
  if (!password) {
    return { valid: false, message: 'La contraseña es requerida' }
  }

  if (password.length < minLength) {
    return { valid: false, message: `La contraseña debe tener al menos ${minLength} caracteres` }
  }

  const hasUpperCase = /[A-Z]/.test(password)
  const hasLowerCase = /[a-z]/.test(password)
  const hasNumbers = /\d/.test(password)
  const hasSpecialChar = /[!@#$%^&*(),.?":{}|<>]/.test(password)

  if (!hasUpperCase) {
    return { valid: false, message: 'La contraseña debe contener al menos una mayúscula' }
  }

  if (!hasLowerCase) {
    return { valid: false, message: 'La contraseña debe contener al menos una minúscula' }
  }

  if (!hasNumbers) {
    return { valid: false, message: 'La contraseña debe contener al menos un número' }
  }

  if (!hasSpecialChar) {
    return { valid: false, message: 'La contraseña debe contener al menos un carácter especial' }
  }

  return { valid: true }
}

/**
 * Valida que dos contraseñas coincidan
 *
 * @param password Contraseña
 * @param confirmation Confirmación de contraseña
 * @returns True si coinciden
 */
export function passwordsMatch(password: string, confirmation: string): boolean {
  return password === confirmation
}

/**
 * Valida una URL
 *
 * @param url URL a validar
 * @returns True si la URL es válida
 */
export function isValidUrl(url: string): boolean {
  if (!url) return false

  try {
    const urlObj = new URL(url)
    return urlObj.protocol === 'http:' || urlObj.protocol === 'https:'
  } catch {
    return false
  }
}

/**
 * Valida un número de teléfono
 *
 * @param phone Teléfono a validar
 * @returns True si el teléfono es válido
 */
export function isValidPhone(phone: string): boolean {
  if (!phone) return false

  // Remover caracteres no numéricos
  const cleaned = phone.replace(/\D/g, '')

  // Validar longitud (10-11 dígitos)
  return cleaned.length >= 10 && cleaned.length <= 11
}

/**
 * Valida un username
 *
 * @param username Username a validar
 * @param minLength Longitud mínima (default: 3)
 * @param maxLength Longitud máxima (default: 20)
 * @returns Objeto con resultado y mensaje
 */
export function isValidUsername(
  username: string,
  minLength: number = 3,
  maxLength: number = 20
): { valid: boolean; message?: string } {
  if (!username) {
    return { valid: false, message: 'El nombre de usuario es requerido' }
  }

  if (username.length < minLength) {
    return { valid: false, message: `El nombre debe tener al menos ${minLength} caracteres` }
  }

  if (username.length > maxLength) {
    return { valid: false, message: `El nombre no puede exceder ${maxLength} caracteres` }
  }

  // Solo alfanuméricos, guiones y guiones bajos
  const usernameRegex = /^[a-zA-Z0-9_-]+$/
  if (!usernameRegex.test(username)) {
    return {
      valid: false,
      message: 'El nombre solo puede contener letras, números, guiones y guiones bajos'
    }
  }

  return { valid: true }
}

/**
 * Valida que un campo no esté vacío
 *
 * @param value Valor a validar
 * @returns True si no está vacío
 */
export function isRequired(value: any): boolean {
  if (value === null || value === undefined) return false
  if (typeof value === 'string') return value.trim().length > 0
  if (Array.isArray(value)) return value.length > 0
  return true
}

/**
 * Valida longitud mínima de un string
 *
 * @param value Valor a validar
 * @param minLength Longitud mínima
 * @returns True si cumple la longitud mínima
 */
export function minLength(value: string, minLength: number): boolean {
  if (!value) return false
  return value.trim().length >= minLength
}

/**
 * Valida longitud máxima de un string
 *
 * @param value Valor a validar
 * @param maxLength Longitud máxima
 * @returns True si cumple la longitud máxima
 */
export function maxLength(value: string, maxLength: number): boolean {
  if (!value) return true // Vacío es válido para max length
  return value.trim().length <= maxLength
}

/**
 * Valida que un número esté en un rango
 *
 * @param value Valor a validar
 * @param min Valor mínimo
 * @param max Valor máximo
 * @returns True si está en el rango
 */
export function inRange(value: number, min: number, max: number): boolean {
  return value >= min && value <= max
}

/**
 * Valida que un valor sea numérico
 *
 * @param value Valor a validar
 * @returns True si es numérico
 */
export function isNumeric(value: any): boolean {
  if (value === null || value === undefined) return false
  return !isNaN(parseFloat(value)) && isFinite(value)
}

/**
 * Valida que un valor sea un entero
 *
 * @param value Valor a validar
 * @returns True si es entero
 */
export function isInteger(value: any): boolean {
  if (!isNumeric(value)) return false
  return Number.isInteger(Number(value))
}

/**
 * Valida que un valor sea positivo
 *
 * @param value Valor a validar
 * @returns True si es positivo
 */
export function isPositive(value: number): boolean {
  return isNumeric(value) && value > 0
}

/**
 * Valida una fecha
 *
 * @param date Fecha a validar
 * @returns True si la fecha es válida
 */
export function isValidDate(date: any): boolean {
  if (!date) return false

  const dateObj = new Date(date)
  return !isNaN(dateObj.getTime())
}

/**
 * Valida que una fecha sea futura
 *
 * @param date Fecha a validar
 * @returns True si la fecha es futura
 */
export function isFutureDate(date: string | Date): boolean {
  if (!isValidDate(date)) return false

  const dateObj = new Date(date)
  const now = new Date()

  return dateObj > now
}

/**
 * Valida que una fecha sea pasada
 *
 * @param date Fecha a validar
 * @returns True si la fecha es pasada
 */
export function isPastDate(date: string | Date): boolean {
  if (!isValidDate(date)) return false

  const dateObj = new Date(date)
  const now = new Date()

  return dateObj < now
}

/**
 * Valida edad mínima basada en fecha de nacimiento
 *
 * @param birthDate Fecha de nacimiento
 * @param minAge Edad mínima requerida
 * @returns True si cumple la edad mínima
 */
export function meetsMinimumAge(birthDate: string | Date, minAge: number): boolean {
  if (!isValidDate(birthDate)) return false

  const birth = new Date(birthDate)
  const today = new Date()
  const age = today.getFullYear() - birth.getFullYear()
  const monthDiff = today.getMonth() - birth.getMonth()

  if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
    return age - 1 >= minAge
  }

  return age >= minAge
}

/**
 * Valida formato de código postal (varios formatos internacionales)
 *
 * @param zipCode Código postal
 * @param country Código de país (default: 'US')
 * @returns True si el código postal es válido
 */
export function isValidZipCode(zipCode: string, country: string = 'US'): boolean {
  if (!zipCode) return false

  const patterns: Record<string, RegExp> = {
    US: /^\d{5}(-\d{4})?$/,
    CA: /^[A-Z]\d[A-Z] \d[A-Z]\d$/,
    UK: /^[A-Z]{1,2}\d[A-Z\d]? \d[A-Z]{2}$/,
    MX: /^\d{5}$/,
    ES: /^\d{5}$/,
    VE: /^\d{4}$/
  }

  const pattern = patterns[country] || patterns.US
  return pattern.test(zipCode)
}

/**
 * Valida que un array no esté vacío
 *
 * @param array Array a validar
 * @returns True si tiene elementos
 */
export function hasItems(array: any[]): boolean {
  return Array.isArray(array) && array.length > 0
}

/**
 * Valida que un objeto no esté vacío
 *
 * @param obj Objeto a validar
 * @returns True si tiene propiedades
 */
export function hasProperties(obj: any): boolean {
  return obj !== null && typeof obj === 'object' && Object.keys(obj).length > 0
}

/**
 * Valida formato de tarjeta de crédito
 *
 * @param cardNumber Número de tarjeta
 * @returns True si el formato es válido
 */
export function isValidCreditCard(cardNumber: string): boolean {
  if (!cardNumber) return false

  // Remover espacios y guiones
  const cleaned = cardNumber.replace(/[\s-]/g, '')

  // Validar longitud (13-19 dígitos)
  if (!/^\d{13,19}$/.test(cleaned)) return false

  // Algoritmo de Luhn
  let sum = 0
  let isEven = false

  for (let i = cleaned.length - 1; i >= 0; i--) {
    let digit = parseInt(cleaned.charAt(i), 10)

    if (isEven) {
      digit *= 2
      if (digit > 9) {
        digit -= 9
      }
    }

    sum += digit
    isEven = !isEven
  }

  return sum % 10 === 0
}

/**
 * Valida formato CVV de tarjeta
 *
 * @param cvv CVV a validar
 * @returns True si el CVV es válido
 */
export function isValidCVV(cvv: string): boolean {
  if (!cvv) return false
  return /^\d{3,4}$/.test(cvv)
}

/**
 * Valida un color hexadecimal
 *
 * @param color Color a validar
 * @returns True si es un color hex válido
 */
export function isValidHexColor(color: string): boolean {
  if (!color) return false
  return /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(color)
}

/**
 * Valida formato IPv4
 *
 * @param ip Dirección IP a validar
 * @returns True si es una IPv4 válida
 */
export function isValidIPv4(ip: string): boolean {
  if (!ip) return false

  const parts = ip.split('.')
  if (parts.length !== 4) return false

  return parts.every(part => {
    const num = parseInt(part, 10)
    return num >= 0 && num <= 255 && part === num.toString()
  })
}

/**
 * Valida un JSON string
 *
 * @param jsonString String JSON a validar
 * @returns True si es JSON válido
 */
export function isValidJSON(jsonString: string): boolean {
  if (!jsonString) return false

  try {
    JSON.parse(jsonString)
    return true
  } catch {
    return false
  }
}

/**
 * Valida múltiples campos de un formulario
 *
 * @param data Datos del formulario
 * @param rules Reglas de validación
 * @returns Objeto con errores (vacío si no hay errores)
 */
export function validateForm(
  data: Record<string, any>,
  rules: Record<string, ValidationRule[]>
): Record<string, string> {
  const errors: Record<string, string> = {}

  Object.keys(rules).forEach(field => {
    const value = data[field]
    const fieldRules = rules[field]

    for (const rule of fieldRules) {
      const result = rule.validator(value, data)

      if (!result) {
        errors[field] = rule.message
        break // Solo mostrar el primer error por campo
      }
    }
  })

  return errors
}

/**
 * Interfaz para reglas de validación
 */
export interface ValidationRule {
  validator: (value: any, allData?: Record<string, any>) => boolean
  message: string
}

/**
 * Reglas de validación predefinidas
 */
export const validationRules = {
  required: (message: string = 'Este campo es requerido'): ValidationRule => ({
    validator: isRequired,
    message
  }),

  email: (message: string = 'Email inválido'): ValidationRule => ({
    validator: isValidEmail,
    message
  }),

  minLength: (length: number, message?: string): ValidationRule => ({
    validator: (value: string) => minLength(value, length),
    message: message || `Mínimo ${length} caracteres`
  }),

  maxLength: (length: number, message?: string): ValidationRule => ({
    validator: (value: string) => maxLength(value, length),
    message: message || `Máximo ${length} caracteres`
  }),

  numeric: (message: string = 'Debe ser un número'): ValidationRule => ({
    validator: isNumeric,
    message
  }),

  positive: (message: string = 'Debe ser un número positivo'): ValidationRule => ({
    validator: isPositive,
    message
  }),

  min: (minValue: number, message?: string): ValidationRule => ({
    validator: (value: number) => value >= minValue,
    message: message || `Valor mínimo: ${minValue}`
  }),

  max: (maxValue: number, message?: string): ValidationRule => ({
    validator: (value: number) => value <= maxValue,
    message: message || `Valor máximo: ${maxValue}`
  }),

  url: (message: string = 'URL inválida'): ValidationRule => ({
    validator: isValidUrl,
    message
  }),

  phone: (message: string = 'Teléfono inválido'): ValidationRule => ({
    validator: isValidPhone,
    message
  }),

  match: (fieldName: string, message?: string): ValidationRule => ({
    validator: (value: any, allData?: Record<string, any>) => {
      if (!allData) return false
      return value === allData[fieldName]
    },
    message: message || 'Los campos no coinciden'
  })
}

/**
 * Ejemplo de uso:
 *
 * const errors = validateForm(formData, {
 *   email: [
 *     validationRules.required(),
 *     validationRules.email()
 *   ],
 *   password: [
 *     validationRules.required(),
 *     validationRules.minLength(8)
 *   ],
 *   password_confirmation: [
 *     validationRules.required(),
 *     validationRules.match('password', 'Las contraseñas no coinciden')
 *   ]
 * })
 */
