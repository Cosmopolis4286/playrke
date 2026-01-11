/**
 * Utilidades para validación visual de formularios
 *
 * Proporciona funciones para validar campos requeridos y mostrar
 * indicadores visuales (bordes rojos) cuando faltan datos obligatorios.
 *
 * @module utils/form/fieldValidation
 */

import { showToast } from '@/utils/toastNotify'

/**
 * Clase CSS aplicada a campos inválidos
 */
const INVALID_CLASS = 'is-invalid'

/**
 * Clase CSS aplicada a campos válidos
 */
const VALID_CLASS = 'is-valid'

/**
 * Flag interno para evitar listeners duplicados
 */
const REALTIME_FLAG = 'data-realtime-validation'

/**
 * Configuración de colores para los bordes de validación
 */
const BORDER_COLORS = {
  invalid: '#dc3545',
  valid: '#28a745',
  default: '#ced4da'
} as const

/**
 * Resultado de la validación de un campo individual
 */
export interface FieldValidationResult {
  fieldName: string
  isValid: boolean
  errorMessage?: string
  element: HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement | null
}

/**
 * Resultado de la validación completa del formulario
 */
export interface FormValidationResult {
  isValid: boolean
  missingFields: string[]
  fieldResults: FieldValidationResult[]
  firstInvalidField: HTMLElement | null
}

/**
 * Aplica estilo visual de error a un campo
 */
export function highlightInvalidField(
  element: HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement,
  errorMessage?: string
): void {
  element.classList.remove(VALID_CLASS)
  element.classList.add(INVALID_CLASS)

  element.style.borderColor = BORDER_COLORS.invalid
  element.style.borderWidth = '2px'

  if (element.classList.contains('select2') && typeof $ !== 'undefined') {
    const container = $(element).next('.select2-container')
    container.find('.select2-selection').css({
      'border-color': BORDER_COLORS.invalid,
      'border-width': '2px'
    })
  }

  if (errorMessage) {
    removeErrorMessage(element)

    const errorSpan = document.createElement('span')
    errorSpan.className = 'invalid-feedback d-block'
    errorSpan.dataset.validationError = 'true'
    errorSpan.textContent = errorMessage

    element.parentElement?.appendChild(errorSpan)
  }
}

/**
 * Limpia validación visual de un campo
 */
export function clearFieldValidation(
  element: HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement
): void {
  element.classList.remove(INVALID_CLASS, VALID_CLASS)
  element.style.borderColor = ''
  element.style.borderWidth = ''

  if (element.classList.contains('select2') && typeof $ !== 'undefined') {
    const container = $(element).next('.select2-container')
    container.find('.select2-selection').css({
      'border-color': '',
      'border-width': ''
    })
  }

  removeErrorMessage(element)
}

/**
 * Remueve el mensaje de error de un campo
 */
function removeErrorMessage(
  element: HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement
): void {
  element.parentElement?.querySelector('[data-validation-error="true"]')?.remove()
}

/**
 * Determina si un campo es válido
 */
export function isFieldValid(
  element: HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement | null
): boolean {
  if (!element) return false

  const value = element.value?.trim()

  if (element instanceof HTMLSelectElement) {
    return value !== '' && value !== null
  }

  return value !== ''
}

/**
 * Obtiene los IDs o nombres de los campos requeridos dentro de un formulario.
 *
 * @param form - Elemento formulario HTML
 * @returns Array de strings con IDs o nombres de campos requeridos
 */
export function getRequiredFields(formId: string): string[] {
  const form = document.getElementById(formId) as HTMLFormElement | null
  let fields: string[] = []

  return (fields = Array.from(
    form.querySelectorAll<HTMLInputElement | HTMLSelectElement>('input[required], select[required]')
  )
    .map(el => el.id || el.name)
    .filter(Boolean) as string[])
}

/**
 * Valida campos requeridos de un formulario
 */
export function validateRequiredFields(
  formId: string,
  requiredFields: string[],
  showErrorMessages = true
): FormValidationResult {
  const form = document.getElementById(formId) as HTMLFormElement | null

  if (!form) {
    console.error(`❌ Formulario con ID "${formId}" no encontrado`)
    return {
      isValid: false,
      missingFields: [],
      fieldResults: [],
      firstInvalidField: null
    }
  }

  const missingFields: string[] = []
  const fieldResults: FieldValidationResult[] = []
  let firstInvalidField: HTMLElement | null = null

  requiredFields.forEach(fieldName => {
    const element = form.querySelector<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>(
      `[name="${fieldName}"], #${fieldName}`
    )

    const valid = isFieldValid(element)

    fieldResults.push({
      fieldName,
      isValid: valid,
      element,
      errorMessage: valid ? undefined : `El campo ${fieldName} es requerido`
    })

    if (!valid && element) {
      missingFields.push(fieldName)
      highlightInvalidField(element, showErrorMessages ? 'Este campo es requerido' : undefined)

      if (!firstInvalidField) firstInvalidField = element
    }

    if (valid && element) {
      clearFieldValidation(element)
    }
  })

  return {
    isValid: missingFields.length === 0,
    missingFields,
    fieldResults,
    firstInvalidField
  }
}

/**
 * Valida los campos requeridos y enfoca el primero inválido si los hay.
 * @param formId
 * @param fields
 * @returns
 */
export function validateAndFocus(formId: string, fields?: string[]): boolean {
  if (!fields || fields.length === 0) {
    fields = getRequiredFields(formId)
  }

  const result = validateRequiredFields(formId, fields, true)

  if (!result.isValid) {
    attachRealtimeValidation(formId, fields)

    if (result.firstInvalidField) {
      result.firstInvalidField.focus()
      result.firstInvalidField.scrollIntoView({ behavior: 'smooth', block: 'center' })
    }

    showToast(`${result.missingFields.length} Campos requeridos faltantes!`, 'warning')
    return false
  }

  clearFormValidation(formId)
  return true
}

/**
 * Limpia todas las validaciones visuales de un formulario
 */
export function clearFormValidation(formId: string): void {
  const form = document.getElementById(formId)
  if (!form) return

  form
    .querySelectorAll<
      HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement
    >(`.${INVALID_CLASS}, .${VALID_CLASS}`)
    .forEach(clearFieldValidation)

  form.querySelectorAll('[data-validation-error="true"]').forEach(el => el.remove())
}

/**
 * Agrega validación reactiva (una sola vez por formulario)
 */
export function attachRealtimeValidation(formId: string, requiredFields: string[]): void {
  const form = document.getElementById(formId) as HTMLFormElement | null
  if (!form) return

  // Evitar listeners duplicados
  if (form.hasAttribute(REALTIME_FLAG)) return
  form.setAttribute(REALTIME_FLAG, 'true')

  const checkAndClearForm = () => {
    if (form.querySelectorAll(`.${INVALID_CLASS}`).length === 0) {
      clearFormValidation(formId)
    }
  }

  requiredFields.forEach(fieldName => {
    const element = form.querySelector<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>(
      `[name="${fieldName}"], #${fieldName}`
    )

    if (!element) return

    const validate = () => {
      if (isFieldValid(element)) {
        clearFieldValidation(element)
        checkAndClearForm()
      }
    }

    element.addEventListener('input', validate)
    element.addEventListener('change', validate)
    element.addEventListener('blur', () => {
      if (!isFieldValid(element)) {
        highlightInvalidField(element, 'Este campo es requerido')
      }
    })
  })
}
