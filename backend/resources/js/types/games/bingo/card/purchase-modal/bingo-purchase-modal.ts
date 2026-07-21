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
  /** Modalidad del cartón personalizado de la sala (solo presente en /player-card-price) */
  readonly custom_card_mode?: 'paid' | 'by_range' | 'every_x' | 'free' | null
  /** % de recargo si custom_card_mode = 'paid' (manual) o 'by_range' (calculado automáticamente) */
  readonly custom_card_price_pct?: number | null
  /** Cada cuántos cartones normales se habilita personalizar el siguiente, si custom_card_mode = 'every_x' */
  readonly custom_card_every_x?: number | null
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
  readonly card_count?: number
}

// ─────────────────────────────────────────────────────────────────────────────
// Estado del modal
// ─────────────────────────────────────────────────────────────────────────────

/** Pasos del wizard del modal */
export type PurchaseModalStep = 'loading' | 'country' | 'player' | 'confirm'
