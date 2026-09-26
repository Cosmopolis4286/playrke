/**
 * Campos compartidos entre las 3 fichas de autogestión (Commerce/Subsidiary/
 * Seller) — reflejan RoleProfileEnrichmentService en el backend. Evita
 * repetir la misma forma 3 veces en cada *-profile.ts.
 */

export interface RoleProfileWallet {
    currency: string
    symbol: string
    balance: number
    bonus_balance: number
}

export interface RoleProfileMovement {
    id: string
    type: string
    type_label: string
    status: 'PENDING' | 'COMPLETED' | 'CANCELLED' | 'REJECTED'
    amount: number
    currency: string | null
    created_at: string
}

export interface RoleProfileAccountMeta {
    member_since: string
    /** null si el usuario nunca inició sesión (ej. cuenta recién creada por un admin). */
    last_login_at: string | null
}
