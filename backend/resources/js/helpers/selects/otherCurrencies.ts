// backend/resources/js/helpers/selects/otherCurrencies.ts
import { getAppDataProperty } from '@js/core/appData' // Leer dinámicamente de AppData
import { Currency, TimeZone } from '@/js/types/data-models'

// ==========================
//   Constantes
// ==========================
const arrCurrencies = getAppDataProperty<Currency[]>('currencies', [])
const arrTimeZones = getAppDataProperty<TimeZone[]>('timeZones', [])

let _selectedCurrencies: (number | string)[] = [] // Almacena las monedas alternativas seleccionadas

// ==========================
//   Elementos del DOM
// ==========================
const currency = document.getElementById('currency') as HTMLSelectElement | null
const containerCurrency = document.getElementById('container-currency') as HTMLElement | null
const containerOtherCurrencies = document.getElementById(
  'container-other-currencies'
) as HTMLElement | null
const containerTimeZone = document.getElementById('container-time-zone') as HTMLElement | null
const country = document.getElementById('country') as HTMLSelectElement | null
const otherCurrencies = document.getElementById('other-currencies') as HTMLSelectElement | null
const timeZoneSelect = document.getElementById('time-zone') as HTMLSelectElement | null

// ==========================
//   Funciones auxiliares
// ==========================
/** Limpia el select de monedas alternativas y resetea el array de monedas seleccionadas */
function clearOtherCurrencies(): void {
  if (!otherCurrencies) return

  otherCurrencies.replaceChildren()

  // Refrescar Select2 si está inicializado
  if (window.jQuery && (window.jQuery as any)(otherCurrencies).data('select2')) {
    ;(window.jQuery as any)(otherCurrencies).val(null).trigger('change')
  }
}

/**
 * Inserta las monedas alternativas en el select correspondiente.
 * - Muestra TODAS las monedas disponibles (sin importar el país).
 * - Excluye la moneda principal seleccionada.
 * - Ordena las monedas alfabéticamente por su símbolo.
 * - Marca como seleccionadas las monedas que se pasan en `_selectedCurrencies`.
 * @return void
 */
export function insertOtherCurrencies(): void {
  if (!otherCurrencies || !currency) return

  clearOtherCurrencies()

  const mainCurrencyId = currency.value
  if (!mainCurrencyId) return

  // Filtrar monedas: TODAS las monedas EXCEPTO la principal
  arrCurrencies
    .filter(c => String(c.id) !== String(mainCurrencyId))
    .sort((a, b) => a.symbol.localeCompare(b.symbol, 'es', { sensitivity: 'base' }))
    .forEach(({ id, symbol, description }) => {
      const option = document.createElement('option')
      option.value = String(id)
      option.textContent = `(${symbol}) ${description}`
      if (_selectedCurrencies.some(sc => String(sc) === String(id))) option.selected = true
      otherCurrencies.appendChild(option)
    })

  containerOtherCurrencies?.classList.remove('d-none')

  // Refrescar Select2 si está inicializado
  if (window.jQuery && (window.jQuery as any)(otherCurrencies).data('select2')) {
    ;(window.jQuery as any)(otherCurrencies).trigger('change')
  }
}

/**
 * Selecciona las monedas alternativas preexistentes en el select.
 * No modifica las opciones, solo las selecciona.
 * @return void
 */
export function selectOtherCurrencies(): void {
  if (!otherCurrencies) return

  // Deseleccionar todo
  Array.from(otherCurrencies.options).forEach(option => {
    option.selected = false
  })

  // Seleccionar las que correspondan
  _selectedCurrencies.forEach(id => {
    const option = Array.from(otherCurrencies.options).find(opt => opt.value === String(id))
    if (option) option.selected = true
  })

  containerOtherCurrencies?.classList.remove('d-none')

  // Refrescar Select2 si está activo
  if (window.jQuery && (window.jQuery as any)(otherCurrencies).data('select2')) {
    ;(window.jQuery as any)(otherCurrencies).trigger('change')
  }
}

/** Actualiza la moneda principal según el país seleccionado */
function updateMainCurrencyByCountry(selectedCountry: string): void {
  if (!currency) return

  console.log('🌍 Actualizando moneda principal para el país ID:', selectedCountry)
  // Limpiar el select
  currency.innerHTML = ''

  // Filtrar monedas por país seleccionado
  const currenciesInCountry = arrCurrencies
    .filter(c => String(c.country_id) === String(selectedCountry))
    .sort((a, b) => a.symbol.localeCompare(b.symbol, 'es', { sensitivity: 'base' }))

  if (currenciesInCountry.length === 0) {
    console.warn(`⚠️ No hay monedas para el país con ID ${selectedCountry}`)
    return
  }

  // Solo agregar placeholder si hay múltiples monedas
  const hasMultipleCurrencies = currenciesInCountry.length > 1
  const placeholder = currency.dataset.placeholder

  if (hasMultipleCurrencies && placeholder) {
    const placeholderOption = new Option(placeholder, '', false, false)
    placeholderOption.disabled = true
    currency.appendChild(placeholderOption)
  }

  // Agregar todas las monedas del país al select
  currenciesInCountry.forEach(({ id, symbol, description }, index) => {
    // Si solo hay una moneda, seleccionarla automáticamente
    const shouldBeSelected = !hasMultipleCurrencies && index === 0

    const option = new Option(
      `(${symbol}) ${description}`,
      String(id),
      shouldBeSelected,
      shouldBeSelected
    )
    currency.appendChild(option)
  })

  // Si solo hay una moneda, manejar Select2 y establecer el valor
  if (!hasMultipleCurrencies && currenciesInCountry.length === 1) {
    // Si Select2 está inicializado, destruir y reiniciar sin placeholder PRIMERO
    if (window.jQuery && (window.jQuery as any)(currency).data('select2')) {
      ;(window.jQuery as any)(currency).select2('destroy')
      ;(window.jQuery as any)(currency).select2({
        placeholder: false,
        allowClear: false
      })
    }

    // Establecer el valor DESPUÉS de reinicializar Select2
    currency.value = String(currenciesInCountry[0].id)
    currency.dispatchEvent(new Event('change'))
  }

  containerCurrency?.classList.remove('d-none')
  containerTimeZone?.classList.remove('d-none')
}

/**
 * Actualiza las zonas horarias filtradas por el país seleccionado
 * @param selectedCountry - ID del país seleccionado
 * @param selectedTimeZone - Zona horaria a preseleccionar (opcional)
 */
function updateTimeZonesByCountry(selectedCountry: string, selectedTimeZone?: string): void {
  if (!timeZoneSelect) return

  // Limpiar opciones actuales
  timeZoneSelect.innerHTML = ''

  // Filtrar zonas horarias por país
  const filteredTimeZones = arrTimeZones.filter(
    tz => String(tz.country_id) === String(selectedCountry)
  )

  if (filteredTimeZones.length === 0) {
    console.warn(`⚠️ No hay zonas horarias para el país con ID ${selectedCountry}`)
    return
  }

  // Ordenar alfabéticamente por nombre
  const sortedTimeZones = filteredTimeZones.sort((a, b) =>
    a.name.localeCompare(b.name, 'es', { sensitivity: 'base' })
  )

  // Si hay más de una zona horaria, agregar placeholder
  // Si hay solo una, NO agregar placeholder (se seleccionará automáticamente)
  const placeholder = timeZoneSelect.dataset.placeholder
  const hasMultipleTimeZones = sortedTimeZones.length > 1

  if (hasMultipleTimeZones && placeholder) {
    const placeholderOption = new Option(placeholder, '', true, false)
    placeholderOption.disabled = false
    timeZoneSelect.appendChild(placeholderOption)
  }

  // Agregar opciones de zonas horarias
  sortedTimeZones.forEach((tz, index) => {
    // Si hay solo una zona horaria, seleccionarla por defecto
    // Si hay un valor preseleccionado, usarlo
    // Si hay múltiples zonas y no hay preselección, ninguna estará seleccionada (placeholder activo)
    const shouldBeSelected = selectedTimeZone
      ? String(selectedTimeZone) === String(tz.id)
      : !hasMultipleTimeZones && index === 0

    const option = new Option(
      `${tz.name} (${tz.utc})`,
      String(tz.id),
      shouldBeSelected,
      shouldBeSelected
    )
    timeZoneSelect.appendChild(option)
  })

  // Si Select2 está inicializado, manejar según el número de opciones
  if (window.jQuery && (window.jQuery as any)(timeZoneSelect).data('select2')) {
    // Si solo hay una opción, destruir y reiniciar Select2 sin placeholder
    if (!hasMultipleTimeZones && sortedTimeZones.length === 1) {
      ;(window.jQuery as any)(timeZoneSelect).select2('destroy')
      ;(window.jQuery as any)(timeZoneSelect).select2({
        placeholder: false,
        allowClear: false
      })
    }
  }

  // Establecer el valor seleccionado en el select (DESPUÉS de reinicializar Select2)
  if (selectedTimeZone) {
    timeZoneSelect.value = String(selectedTimeZone)
  } else if (!hasMultipleTimeZones && sortedTimeZones.length === 1) {
    timeZoneSelect.value = String(sortedTimeZones[0].id)
  }

  // Trigger change event to update Select2 display
  if (window.jQuery && (window.jQuery as any)(timeZoneSelect).data('select2')) {
    ;(window.jQuery as any)(timeZoneSelect).trigger('change')
  }
}

/** Resetea selección de moneda y zonas horarias, oculta containers */
function resetCurrencySelection(): void {
  // Resetear select de moneda
  if (currency) {
    currency.innerHTML = ''
    const placeholder = currency.dataset.placeholder
    if (placeholder) {
      const placeholderOption = new Option(placeholder, '', true, false)
      placeholderOption.disabled = true
      currency.appendChild(placeholderOption)
    }
  }

  // Resetear select de zonas horarias
  if (timeZoneSelect) {
    timeZoneSelect.innerHTML = ''
    const placeholder = timeZoneSelect.dataset.placeholder
    if (placeholder) {
      const placeholderOption = new Option(placeholder, '', true, false)
      placeholderOption.disabled = true
      timeZoneSelect.appendChild(placeholderOption)
    }
  }

  containerOtherCurrencies?.classList.add('d-none')
  containerCurrency?.classList.add('d-none')
  containerTimeZone?.classList.add('d-none')
}

// ==========================
//   Inicialización
// ==========================
let _currencySelectorInitialized = false

/** Inicializa el selector de moneda y zona horaria en función del país */
export function initCurrencySelector(): void {
  if (_currencySelectorInitialized) {
    if (import.meta.env.DEV) {
      console.log('♻️ initCurrencySelector ya fue inicializado, usando instancia existente')
    }
    return
  }

  if (!country) {
    if (import.meta.env.DEV) {
      console.warn('⚠️ No se encontró select de país (#country)')
    }
    return
  }

  if (import.meta.env.DEV) {
    console.log('Inicializando selector de moneda y zona horaria por país', country)
  }

  country.addEventListener('change', () => {
    const selectedCountry = country.value

    if (!selectedCountry) {
      resetCurrencySelection()
      return
    }

    // Actualizar moneda principal
    updateMainCurrencyByCountry(selectedCountry)

    // Actualizar zonas horarias filtradas por país
    updateTimeZonesByCountry(selectedCountry)
  })

  // Listener para cuando se selecciona una moneda principal manualmente
  currency?.addEventListener('change', () => {
    if (!currency.value) {
      containerOtherCurrencies?.classList.add('d-none')
      _selectedCurrencies = []
      clearOtherCurrencies()
      return
    }
    insertOtherCurrencies()
  })

  _currencySelectorInitialized = true
}

/**
 * Exportar función para actualizar zonas horarias manualmente
 * Útil cuando se necesita preseleccionar una zona horaria al editar
 */
export function updateTimeZones(countryId: string | number, selectedTimeZone?: string): void {
  updateTimeZonesByCountry(String(countryId), selectedTimeZone)
}

/**
 * Exportar función para actualizar moneda principal manualmente
 * Útil cuando se necesita cargar opciones programáticamente
 */
export function updateCurrency(countryId: string | number): void {
  updateMainCurrencyByCountry(String(countryId))
}

/**
 * Resetea el estado de inicialización (útil para testing)
 */
export function resetCurrencySelectorState(): void {
  _currencySelectorInitialized = false
}

// Setea las monedas alternativas seleccionadas
export function setOtherCurrencies(selectedCurrencies: (number | string)[] = []): void {
  _selectedCurrencies = selectedCurrencies
}
