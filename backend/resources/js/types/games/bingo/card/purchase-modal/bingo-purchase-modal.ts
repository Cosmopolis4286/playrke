/**
 * Types del Modal de Compra de Cartones de Bingo.
 *
 * Cubre el flujo completo:
 * 1. Seleccionar país → GET /{room}/available-countries
 * 2. Obtener precio   → GET /{room}/card-price?country_id=
 * 3. Ingresar jugador + cantidad
 * 4. Confirmar compra → POST /{room}/generate
 */

// ─────────────────────────────────────────────────────────────────────────────
// Países
// ─────────────────────────────────────────────────────────────────────────────

/** País disponible devuelto por /available-countries */
export interface BingoAvailableCountry {
  /** Snowflake ID (string para evitar truncado) */
  readonly id: string
  readonly name: string
  readonly flag: string | null
  /** Código ISO 3166-1 alpha-2 */
  readonly code: string
  /** Símbolo de la moneda del país */
  readonly currency_symbol: string
}

// ─────────────────────────────────────────────────────────────────────────────
// Precio del cartón
// ─────────────────────────────────────────────────────────────────────────────

/** Respuesta de /card-price */
export interface BingoCardPriceResponse {
  readonly card_price: number
  readonly currency_symbol: string
  readonly exchange_rate: number
  readonly is_custom_price: boolean
}

// ─────────────────────────────────────────────────────────────────────────────
// Datos del jugador
// ─────────────────────────────────────────────────────────────────────────────

export interface BingoPlayerForm {
  phone: string
  name: string
  last_name: string
}

// ─────────────────────────────────────────────────────────────────────────────
// Respuesta de compra
// ─────────────────────────────────────────────────────────────────────────────

export interface BingoCardPurchaseResponse {
  readonly message: string
  readonly type: 'success' | 'error' | 'warning' | 'info'
  readonly prize_pool?: number
  readonly ctns?: number
}

// ─────────────────────────────────────────────────────────────────────────────
// Estado del modal
// ─────────────────────────────────────────────────────────────────────────────

/** Pasos del wizard del modal */
export type PurchaseModalStep = 'country' | 'player' | 'confirm'
