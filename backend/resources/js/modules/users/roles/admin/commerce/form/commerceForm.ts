// commerceForm.ts
import {
  initCurrencySelector,
  updateCurrency,
  updateTimeZones,
  setOtherCurrencies
} from '@/helpers/selects/otherCurrencies'
import { insertAllowedGames, selectedAllowedGames } from '@/helpers/selects/allowedGames'
import { validateInput } from '@/utils/validateInput'
import { initAllSelects } from '@/helpers/selects/initAllSelects'
import {
  fillFormFromObject,
  clearValidationErrors,
  resetFormAndContainers
} from '@/helpers/form/formHelpers'
import { commerceAdminRoutes } from '@/routes/commerceRoutes'

// ==========================
// Constantes globales
// ==========================
export const routes: typeof commerceAdminRoutes = commerceAdminRoutes
export const formTitleSuffix = 'Comercio'
export const moduleId = 'commerce-form'

// Campos requeridos (IDs del DOM)
export const requiredFields: string[] = [
  'name',
  'country',
  'time-zone',
  'currency',
  'phone',
  'username',
  'email'
]

// ==========================
// Mapeo de acciones personalizadas
// ==========================
export const otherCasesMap: Record<string, unknown> = {}

// ==========================
// Helpers internos
// ==========================

/**
 * Retorna un input por ID con tipado seguro
 */
function getInput(id: string): HTMLInputElement | null {
  return document.getElementById(id) as HTMLInputElement | null
}

/**
 * Ejecuta una función cuando el callstack esté libre
 * (mejor alternativa a setTimeout encadenados)
 */
function nextTick(fn: () => void): void {
  requestAnimationFrame(() => requestAnimationFrame(fn))
}

// ==========================
// Función: insertInfo
// ==========================
/**
 * Inserta información en el formulario del modal según la acción.
 *
 * @param action - 'create' | 'edit'
 * @param data - Datos para precargar en modo edición
 */
export function insertInfo(action: 'create' | 'edit', data: any = null): void {
  const modal = document.getElementById(`modal-${moduleId}`)
  console.log(modal)
  if (!modal) {
    console.error('Modal no encontrado')
    return
  }

  const usernameInput = getInput('username')
  const emailInput = getInput('email')

  if (!usernameInput || !emailInput) {
    console.error('Inputs username/email no encontrados')
    return
  }

  // Limpiar errores de validación previos
  clearValidationErrors(moduleId)

  // Valores preseleccionados para selects dinámicos
  const selectedValues: Record<string, string | number> = {}

  // ==========================
  // Modo edición
  // ==========================
  if (action === 'edit' && data) {
    // Datos del commerce
    const commerce = data

    if (import.meta.env.DEV) {
      console.log('✏️ Modo edición - Datos recibidos:', data)
    }

    // Rellenar campos base del commerce
    fillFormFromObject(commerce)

    // Usuario
    usernameInput.value = commerce.user?.username ?? ''
    usernameInput.disabled = Boolean(usernameInput.value)

    emailInput.value = commerce?.user?.email ?? ''
    emailInput.disabled = Boolean(emailInput.value)

    // Preselección de foreign keys
    selectedValues.country = commerce?.country?.id ?? ''
    selectedValues.currency = commerce?.currency?.id ?? ''
    selectedValues['time-zone'] = commerce?.user?.time_zone_id ?? ''
  } else {
    // ==========================
    // Modo creación
    // ==========================
    resetFormAndContainers(moduleId, [
      'container-currency',
      'container-time-zone',
      'container-other-currencies',
      'container-allowed-games'
    ])

    usernameInput.disabled = false
    emailInput.disabled = false
    setOtherCurrencies([])
  }

  // ==========================
  // Inicialización de selects
  // ==========================
  initAllSelects(selectedValues)

  // ==========================
  // Carga dependiente en edición
  // ==========================
  if (action === 'edit' && data) {
    // Datos del commerce
    const commerce = data
    const otherCurrencies = commerce?.other_currencies
    const allowedGames = commerce?.allowed_games

    // Otras monedas
    if (Array.isArray(otherCurrencies) && otherCurrencies.length > 0) {
      if (import.meta.env.DEV) {
        console.log('💵 Insertando other currencies:', otherCurrencies)
      }
      setOtherCurrencies(otherCurrencies)
    }

    // Juegos permitidos
    if (Array.isArray(allowedGames) && allowedGames.length > 0) {
      if (import.meta.env.DEV) {
        console.log('🎮 Insertando allowed games:', allowedGames)
      }
      selectedAllowedGames(allowedGames)
    }

    nextTick(() => {
      if (import.meta.env.DEV) {
        console.log('🔄 Cargando selects dependientes por país:', commerce?.country?.name)
      }

      updateCurrency(commerce?.country?.id)
      updateTimeZones(commerce?.country?.id, String(commerce?.user?.time_zone_id))
    })
  }

  // Mostrar modal
  $(modal).modal('show')
}

// ==========================
// Inicialización de listeners
// ==========================
/**
 * Inicializa listeners globales del formulario
 */
export function initListeners(): void {
  // Selector de moneda según país
  initCurrencySelector()
  // Insertar juegos disponibles
  insertAllowedGames()

  // Máscara de teléfono
  if (typeof $ === 'function') {
    const $phone = $('#phone')
    if ($phone.length) {
      $phone.mask('00000000000')
    }
  }

  // Validación de comisión
  document.addEventListener('input', event => {
    const target = event.target
    if (target instanceof HTMLInputElement && target.id === 'commission') {
      validateInput(target, true, 3, 35)
    }
  })
}
