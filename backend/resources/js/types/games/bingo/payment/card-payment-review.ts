/**
 * Cola de revisión de pagos de cartones (/bingo/card-payments) —
 * props de BingoCardPaymentReviewController::index().
 */

export type PendingPaymentPolicy = 'reject_refund' | 'keep_review'

/** Urgencia según el inicio de la sala (se calcula en el cliente con el reloj). */
export type PaymentUrgency = 'started' | 'soon' | 'later' | 'manual'

export interface PendingCardPayment {
    id: string
    room_uuid_code: string | null
    room_short_code: string | null
    room_status: string | null
    room_status_label: string | null
    room_starts_at: string | null
    room_started: boolean
    seller_name: string | null
    seller_network: string | null
    commerce_name: string | null
    pending_policy: PendingPaymentPolicy
    external_player: { name: string; phone: string | null } | null
    card_count: number | null
    payment_amount: number
    currency_code: string | null
    amount_label: string
    transaction_reference: string | null
    payment_method: string | null
    payment_method_label: string | null
    payment_proof_url: string | null
    created_at: string | null
    pending_at_start_at: string | null
}

export interface CardPaymentRefundFailure {
    id: string
    room_uuid_code: string | null
    amount: number
    amount_label: string
    status: 'PENDING' | 'ABANDONED'
    attempts: number
    error_message: string | null
    last_attempted_at: string | null
}

export interface CardPaymentReviewPageProps {
    title: string
    payments: PendingCardPayment[]
    refundFailures: CardPaymentRefundFailure[]
    /** Premios suspendidos o con reintentos agotados (solo admin). */
    prizeReviews?: BingoPrizeReview[]
    /** Regla propia (solo comercializadora); null para admin. */
    policy: PendingPaymentPolicy | null
    summary: {
        total: number
        amount: string | null
        truncated: boolean
    }
}

/** Premio de Bingo que el pago automático no resolvió solo (BingoPrizeReviewService). */
export interface BingoPrizeReview {
    id: string
    room_uuid_code: string | null
    winner: string
    amount_label: string
    prize_status: 'suspended' | 'waiting' | 'processing'
    /** external: comprador externo; no_wallet: ganador sin billetera; exhausted: reintentos agotados. */
    reason: 'external' | 'no_wallet' | 'exhausted'
    attempts: number
    last_error: string | null
    created_at: string | null
}
