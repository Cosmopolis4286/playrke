// resources/js/utils/initSelects.ts
import { createDatasetGetters } from '@js/utils/dataset'

// Generar getters automáticamente desde appData
const datasets = createDatasetGetters()

// Cache para evitar reinicializar selects
const initializedSelects = new WeakSet<HTMLSelectElement>()

// ==========================
// Helpers internos
// ==========================

function isSelect2(select: HTMLSelectElement): boolean {
  return select.classList.contains('select2')
}

function applySelectValue(
  select: HTMLSelectElement,
  value: string | number | Array<string | number>
): void {
  const normalized = Array.isArray(value) ? value.map(String) : String(value)

  if (isSelect2(select) && $(select).data('select2')) {
    $(select).val(normalized).trigger('change')
  } else {
    Array.from(select.options).forEach(option => {
      option.selected = Array.isArray(normalized)
        ? normalized.includes(option.value)
        : option.value === normalized
    })
  }
}

// ==========================
// Builder de opciones
// ==========================

function buildOptions(
  select: HTMLSelectElement,
  dataset: any[],
  valueKey: string,
  labelKey: string,
  disableFn?: (selectId: string, item: any) => boolean
): void {
  select.innerHTML = ''

  // Placeholder
  let placeholderOption: HTMLOptionElement | null = null
  if (select.dataset.placeholder) {
    placeholderOption = new Option(select.dataset.placeholder, '', true, false)
    select.appendChild(placeholderOption)
  }

  dataset
    .sort((a, b) =>
      String(a[labelKey]).localeCompare(String(b[labelKey]), 'es', {
        sensitivity: 'base'
      })
    )
    .forEach(item => {
      const value = item[valueKey]
      const label = item[labelKey]

      if (value == null || label == null) return

      const option = new Option(String(label), String(value), false, false)

      if (disableFn?.(select.id, item)) {
        option.disabled = true
      }

      select.appendChild(option)
    })

  // Placeholder se deshabilita al primer cambio
  if (placeholderOption) {
    select.addEventListener(
      'change',
      () => {
        placeholderOption!.disabled = true
      },
      { once: true }
    )
  }
}

// ==========================
// Select2 adapter
// ==========================

function initSelect2(select: HTMLSelectElement): void {
  if ($(select).data('select2')) return

  const placeholderText = '    ' + (select.dataset.placeholder || '')
  const allowClear = select.dataset.allowClear !== 'false'

  $(select).select2({
    placeholder: placeholderText,
    allowClear,
    theme: 'bootstrap4',
    width: '100%',
    multiple: select.multiple
  })

  if (import.meta.env.DEV) {
    console.log(`✅ Select2 inicializado para #${select.id}`)
  }
}

// ==========================
// API pública
// ==========================

/**
 * Inicializa todos los <select> con data-source.
 * Construye opciones, inicializa Select2 y aplica valores seleccionados.
 *
 * @param selectedValues - Valores a preseleccionar por ID de select
 * @param disableFn - Función opcional para deshabilitar opciones
 * @param forceReinit - Forzar reinicialización completa
 */
export function initAllSelects(
  selectedValues?: Partial<Record<string, any>>,
  disableFn?: (selectId: string, item: any) => boolean,
  forceReinit = false
): void {
  document.querySelectorAll<HTMLSelectElement>('select[data-source]').forEach(select => {
    if (!forceReinit && initializedSelects.has(select)) {
      if (selectedValues?.[select.id] != null) {
        applySelectValue(select, selectedValues[select.id])
      }
      return
    }

    const sourceKey = select.dataset.source as keyof typeof datasets
    const dataset = datasets[sourceKey]?.() ?? []

    if (!dataset.length) {
      console.warn(`⚠️ Dataset '${String(sourceKey)}' vacío para #${select.id}`)
      return
    }

    const valueKey = select.dataset.value
    const labelKey = select.dataset.label

    if (!valueKey || !labelKey) {
      console.warn(`⚠️ Select #${select.id} requiere data-value y data-label`)
      return
    }

    // 1️⃣ Construir opciones
    buildOptions(select, dataset, valueKey, labelKey, disableFn)

    // 2️⃣ Inicializar Select2
    if (isSelect2(select)) {
      initSelect2(select)
    }

    // 3️⃣ Aplicar selección (CLAVE)
    if (selectedValues?.[select.id] != null) {
      applySelectValue(select, selectedValues[select.id])
    }

    initializedSelects.add(select)
  })

  // Inicializar Select2 simples (sin data-source)
  initStandaloneSelect2()
}

/**
 * Inicializa Select2 en selects sin data-source
 */
function initStandaloneSelect2(): void {
  document
    .querySelectorAll<HTMLSelectElement>('select.select2:not([data-source])')
    .forEach(select => {
      initSelect2(select)
    })
}

/**
 * Limpia el cache de inicialización
 */
export function clearSelectsCache(): void {
  if (import.meta.env.DEV) {
    console.log('♻️ WeakSet liberará selects automáticamente')
  }
}
