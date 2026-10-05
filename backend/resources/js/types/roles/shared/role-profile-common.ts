/**
 * Fichas de autogestión "Mi …" de las entidades de rol: red comercial
 * (Commerce/Subsidiary/Seller), Customer (integración B2B) y Treasurer.
 * Refleja *ProfileService::showOwn() + RoleProfileEnrichmentService +
 * RoleProfileInsightsService en el backend.
 */

/** Roles de la red comercial (reparto de comisión en cascada). */
export type RoleProfileRole = 'commerce' | 'subsidiary' | 'seller'
/** Todo rol con ficha propia. */
export type AccountRole = RoleProfileRole | 'customer' | 'treasurer'
export type EntityStatus = 'ACTIVE' | 'INACTIVE' | 'BLOCKED' | 'DELETED' | string

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

export interface RoleProfileKpi {
    value: number
    previous: number
    /** null = sin base de comparación (mes anterior en 0). */
    change_pct: number | null
}

export interface RoleProfilePerformance {
    period_label: string
    currency: { iso: string; symbol: string } | null
    kpis: { sales: RoleProfileKpi; commission: RoleProfileKpi; cards: RoleProfileKpi; lots: RoleProfileKpi }
    other_currencies: Array<{ iso: string; symbol: string; sales: number; commission: number }>
    trend: Array<{ date: string; count: number }>
    lifetime: { cards: number; lots: number; commission: number; first_sale_at: string | null }
}

export interface RoleProfileChainNode {
    level: AccountRole
    name: string
    /**
     * null = nivel SUPERIOR al que mira su propio perfil: cada nivel ve solo
     * las comisiones suyas y de quienes tiene debajo (admin ve todo).
     */
    rate: number | null
    status: EntityStatus
    is_self: boolean
}

export interface RoleProfileAlert {
    level: 'danger' | 'warning' | 'info'
    icon: string
    title: string
    message: string
    action: { label: string; href: string } | null
}

export interface RoleProfileSecurity {
    email_verified: boolean
    two_factor_enabled: boolean
    withdrawal_pin_set: boolean
    login_streak_days: number
    score: number
}

export interface RoleProfileReceivable {
    currency: string
    symbol: string
    amount: number
    items: number
}

export interface RoleProfileNetworkRow {
    name: string
    commission: number
    status: EntityStatus
    month_cards: number
    sellers_count?: number
}

interface AccountProfileBase {
    id: string
    name: string
    phone: string
    status: EntityStatus
    country: string | null
    user: { username: string; email: string }
    commission: number
    hierarchy: { chain: RoleProfileChainNode[] }
    alerts: RoleProfileAlert[]
    security: RoleProfileSecurity
    wallets: RoleProfileWallet[]
    recent_movements: RoleProfileMovement[]
    account_meta: RoleProfileAccountMeta
}

/** Base de las fichas que venden (red comercial y Customer). */
interface RoleProfileBase extends AccountProfileBase {
    performance: RoleProfilePerformance
}

export interface CommerceProfile extends RoleProfileBase {
    role: 'commerce'
    currencies: string[]
    games: string[]
    receivables: RoleProfileReceivable[]
    network: {
        subsidiaries_count: number
        sellers_count: number
        direct_sellers_count: number
        subsidiaries: RoleProfileNetworkRow[]
        top_sellers: Array<{ name: string; subsidiary: string | null; cards: number }>
    }
    hierarchy: { chain: RoleProfileChainNode[]; children_rate_range: { min: number; max: number } | null }
}

export interface SubsidiaryProfile extends RoleProfileBase {
    role: 'subsidiary'
    commerce: { name: string; status: EntityStatus } | null
    currency: string | null
    network: { sellers_count: number; sellers: RoleProfileNetworkRow[] }
    hierarchy: { chain: RoleProfileChainNode[]; pass_through: boolean; children_rate_range: { min: number; max: number } | null }
}

export interface SellerProfile extends RoleProfileBase {
    role: 'seller'
    currencies: string[]
    games: string[]
    reports_to: { name: string; type: 'subsidiary' | 'commerce'; commerce_name: string | null }
    hierarchy: { chain: RoleProfileChainNode[]; cap: number | null }
}

/** Ficha de la red comercial (comisión en cascada, cadena jerárquica). */
export type RoleProfile = CommerceProfile | SubsidiaryProfile | SellerProfile

export interface CustomerProfile extends RoleProfileBase {
    role: 'customer'
    business_name: string | null
    currency: string | null
    currencies: string[]
    games: string[]
    /** Integración API — la clave nunca viaja completa, solo key_hint ("••••abcd"). */
    integration: {
        api_route: string | null
        key_hint: string | null
        users_total: number
        users_active: number
        rooms_active: number
        rooms_total: number
    }
}

export interface TreasuryKpi {
    value: number
    previous: number
    change_pct: number | null
}

export interface TreasurerProfile extends AccountProfileBase {
    role: 'treasurer'
    currencies: string[]
    games: string[]
    treasury: {
        period_label: string
        kpis: { processed: TreasuryKpi; approved: TreasuryKpi; rejected: TreasuryKpi }
        approval_rate: number | null
        queue: { pending: number; unassigned: number; assigned_me: number; oldest_at: string | null }
        trend: Array<{ date: string; count: number }>
        top_types: Array<{ type: string; label: string; count: number }>
        lifetime: { processed: number; first_at: string | null; last_at: string | null }
    }
}

/** Cualquier ficha "Mi …". */
export type AccountProfile = RoleProfile | CustomerProfile | TreasurerProfile
