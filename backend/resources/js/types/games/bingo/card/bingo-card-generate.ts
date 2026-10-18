/**
 * Respuesta del endpoint GET /bingo-card/{room}/card-price?country_id=X
 *
 * - card_price:      Precio calculado del cartón en la moneda local del país.
 * - currency_symbol: Símbolo de la moneda (ej: "Bs.", "R$", "$").
 * - exchange_rate:   Tasa de cambio aplicada (commerce → moneda del país).
 * - is_custom_price: true si el precio viene de bingo_card_prices, false si
 *                    es el precio base de la sala × tasa.
 */
export interface CardPriceResponse {
    card_price: number
    currency_symbol: string
    exchange_rate: number
    is_custom_price: boolean
    /** Porcentaje de comisión del vendedor autenticado (ej: 5.00 = 5%). */
    seller_commission: number
}

/**
 * Respuesta del endpoint POST /bingo-card/{room}/generate
 */
export interface GenerateCardResponse {
    type: string
    message: string
    /** Link público de acceso al juego para el ExternalPlayer (ver ExternalPlayerGameController). */
    playerLink: string | null
    /** Estado del pago: PENDING = los cartones se activan cuando el comercio apruebe el comprobante. */
    paymentStatus?: 'PENDING' | 'APPROVED'
}

/* ───────── Punto de venta (/bingo-card/pos/*) ───────── */

/** Sala vendible de GET /bingo-card/pos/rooms (BingoCardGenerateService::listSellableRooms). */
export interface PosRoom {
    /** ID hasheado — el mismo que aceptan available-countries / card-price / generate. */
    id: string
    uuid_code: string
    prize_pool: number
    total_players: number
    total_cards: number
    /** true si la sala es del comercio del vendedor (false = sala pública de otro). */
    is_own: boolean
    /** Sala VIP: solo se le vende a compradores cuyo teléfono está en su lista de invitados. */
    is_vip?: boolean
    date: string | null
    /** "HH:MM:SS" */
    time: string | null
    card_price_usd: number
    max_players: number | null
    room_type_id: string | null
    connection_type_id: string | null
    prize_pool_type_id: string | null
    /** Sala de inicio manual (sin fecha/hora): se vende mientras siga ACTIVE. */
    is_manual: boolean
    /** Inicio programado (ISO, UTC) — null si es manual. */
    starts_at: string | null
    /** Cierre de ventas del vendedor (inicio − bingo_seller_sales_cutoff_minutes). */
    sales_close_at: string | null
    /** Estado al momento de listar; el cliente lo re-evalúa con sales_close_at. */
    sales_open: boolean
}

/** Comprador frecuente del vendedor (GET /bingo-card/pos/frequent-buyers). */
export interface FrequentBuyer {
    phone: string
    country_id: string
    first_name: string
    last_name: string
    purchases: number
    last_purchase_at: string | null
}

export type PosSaleStatus = 'PENDING' | 'APPROVED' | 'REJECTED' | 'CANCELLED'

/** Venta del día (GET /bingo-card/pos/today-sales). */
export interface PosSale {
    id: string
    status: PosSaleStatus
    room_code: string | null
    buyer_name: string
    buyer_phone: string | null
    player_link: string | null
    card_count: number
    amount: number
    currency_symbol: string
    receipt: string | null
    created_at: string | null
}

export interface PosSalesSummary {
    sales_count: number
    cards_count: number
    pending: number
    approved: number
    rejected: number
    totals: { currency_iso: string; currency_symbol: string; amount: number }[]
}

/** Resultado de la última venta, para la pantalla de éxito. */
export interface PosSaleResult {
    playerLink: string | null
    paymentStatus: 'PENDING' | 'APPROVED' | null
    buyerName: string
    buyerPhone: string
    countryIso2: string | null
    roomCode: string
    cardCount: number
    total: number
    currencySymbol: string
}
