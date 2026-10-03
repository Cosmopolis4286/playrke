/**
 * Ficha de autogestión de las 3 entidades de negocio (Commerce/Subsidiary/
 * Seller "Mi …"). Refleja *ProfileService::showOwn() + RoleProfileEnrichmentService
 * + RoleProfileInsightsService en el backend.
 */

export type RoleProfileRole = 'commerce' | 'subsidiary' | 'seller'
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
    level: RoleProfileRole
    name: string
    rate: number
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

interface RoleProfileBase {
    id: string
    name: string
    phone: string
    status: EntityStatus
    status_badge: string
    country: string | null
    user: { username: string; email: string }
    commission: number
    performance: RoleProfilePerformance
    alerts: RoleProfileAlert[]
    security: RoleProfileSecurity
    wallets: RoleProfileWallet[]
    recent_movements: RoleProfileMovement[]
    account_meta: RoleProfileAccountMeta
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

export type RoleProfile = CommerceProfile | SubsidiaryProfile | SellerProfile
