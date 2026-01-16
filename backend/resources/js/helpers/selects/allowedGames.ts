import { getAppDataProperty } from '@js/core/appData' // Leer dinámicamente de AppData
import { Game } from '@/js/types/data-models'

// ==========================
//   Constantes
// ==========================
const arrGames = getAppDataProperty<Game[]>('games', [])

let _selectedAllowedGames: (number | string)[] = [] // Almacena los juegos alternativas seleccionadas

// ==========================
//   Elementos del DOM
// ==========================
const allowedGames = document.getElementById('allowed-games') as HTMLSelectElement | null

/**
 * Inserta los juegos disponibles/permitidos en el select correspondiente.
 * - Muestra TODOS los juegos disponibles.
 * - Ordena los juegos alfabéticamente por su nombre.
 * @return void
 */
export function insertAllowedGames(): void {
  if (!allowedGames) return

  // Filtrar juegos: TODOS los juegos permitidos/disponibles
  arrGames
    .sort((a, b) => a.name.localeCompare(b.name, 'es', { sensitivity: 'base' }))
    .forEach(({ id, name }) => {
      const option = document.createElement('option')
      option.value = String(id)
      option.textContent = name
      allowedGames.appendChild(option)
    })

  // Refrescar Select2 si está inicializado
  if (window.jQuery && (window.jQuery as any)(allowedGames).data('select2')) {
    ;(window.jQuery as any)(allowedGames).trigger('change')
  }
}

/**
 * Marca como seleccionadas los juegos que se pasan en `_selectedAllowedGames`.
 * @param selectedAllowedGames - Array de IDs de juegos que deben estar preseleccionados.
 * @return void
 */
export function selectedAllowedGames(selectedAllowedGames: (number | string)[] = []): void {
  if (!allowedGames) return

  const selectedSet = new Set(selectedAllowedGames.map(String))

  Array.from(allowedGames.options).forEach(option => {
    option.selected = selectedSet.has(option.value)
  })

  // Notificar cambios (Select2 / listeners)
  allowedGames.dispatchEvent(new Event('change', { bubbles: true }))
}

// ==========================
//   Inicialización
// ==========================
let _allowedGamesSelectorInitialized = false

/** Inicializa el selector de juegos permitidos */
export function initAllowedGamesSelector(): void {
  if (_allowedGamesSelectorInitialized) {
    if (import.meta.env.DEV) {
      console.log('♻️ initAllowedGamesSelector ya fue inicializado, usando instancia existente')
    }
    return
  }

  _allowedGamesSelectorInitialized = true
}

// Setea los juegos permitidos seleccionadas
export function setAllowedGames(selectedAllowedGames: (number | string)[] = []): void {
  _selectedAllowedGames = selectedAllowedGames
}
