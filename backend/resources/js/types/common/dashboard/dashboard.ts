/**
 * Shared Dashboard Types
 * Reutilizables por cualquier rol (Admin, Commerce, Seller, etc.)
 */

// --------------------------------------------------------------------------
// Stat Card
// --------------------------------------------------------------------------

export type StatCardTrend = 'up' | 'down' | 'neutral'
export type StatCardVariant =
    | 'primary' | 'success' | 'danger' | 'warning' | 'info' | 'secondary'
    | 'outline-primary' | 'outline-success' | 'outline-danger' | 'outline-warning' | 'outline-info' | 'outline-secondary'

export interface StatCardData {
    /** Etiqueta visible */
    label: string
    /** Valor principal (string para soportar formatos: "1,234", "$500", etc.) */
    value: string | number
    /** Icono FontAwesome (e.g. "fas fa-dollar-sign") */
    icon?: string
    /** Variante de color */
    variant?: StatCardVariant
    /** Descripción secundaria o subvalor */
    description?: string
    /** Tendencia respecto al período anterior */
    trend?: StatCardTrend
    /** Porcentaje de cambio (e.g. 12.5) */
    trendValue?: number
    /** Texto del período de comparación (e.g. "vs. mes anterior") */
    trendLabel?: string
    /** Enlace opcional al hacer clic */
    href?: string
}

// --------------------------------------------------------------------------
// Entity Count (conteo de entidades por rol)
// --------------------------------------------------------------------------

export interface EntityCount {
    label:   string
    value:   number
    icon:    string
    variant: StatCardVariant
    /** Enlace opcional a la pantalla de gestión de esta entidad */
    href?:       string
    /** Tendencia semana-a-semana (ver BuildsWeeklyTrend) */
    trend?:      StatCardTrend
    trendValue?: number
    trendLabel?: string
}

// --------------------------------------------------------------------------
// Activity Feed
// --------------------------------------------------------------------------

export type ActivityType = 'sale' | 'refund' | 'login' | 'update' | 'alert' | 'info' | 'success' | 'danger' | 'bingo'

export interface ActivityItem {
    id: string | number
    type: ActivityType
    title: string
    description?: string
    /** ISO timestamp */
    timestamp: string
    /** Nombre o avatar del actor */
    actor?: string
    /** Metadatos adicionales libres */
    meta?: Record<string, string | number>
}

// --------------------------------------------------------------------------
// Section / Widget
// --------------------------------------------------------------------------

export type DashboardSkeletonVariant = 'lines' | 'list' | 'chart' | 'stat'

export interface DashboardSectionProps {
    title?: string
    subtitle?: string
    /** Icono FontAwesome para el encabezado */
    icon?: string
    /** Mostrar borde card */
    bordered?: boolean
    /** Mostrar skeleton mientras carga */
    loading?: boolean
    /** Forma del skeleton — 'lines' (genérico, default), 'list' (filas tipo EntityList/ActivityFeed), 'chart' (barras tipo TrendChart), 'stat' (bloque grande tipo CommissionPanel). */
    skeletonVariant?: DashboardSkeletonVariant
    /** Clase CSS adicional para el wrapper */
    class?: string
}

// --------------------------------------------------------------------------
// Welcome Banner
// --------------------------------------------------------------------------

export interface WelcomeBannerProps {
    userName: string
    roleName?: string
    /** Clave de rol para variar el acento de color del banner (superadmin, admin, treasurer, seller, commerce, player) */
    roleKey?: string
    message?: string
    /** Acciones rápidas */
    actions?: WelcomeBannerAction[]
}

export interface WelcomeBannerAction {
    label: string
    icon?: string
    href?: string
    variant?: StatCardVariant
    onClick?: () => void
}

// --------------------------------------------------------------------------
// Red de vendedores (Agencia / Comercio) — NetworkDashboardService
// --------------------------------------------------------------------------

export interface NetworkIdleSeller {
    name:         string
    phone:        string | null
    last_sale_at: string | null
    /** Días desde la última venta; null si nunca vendió */
    days:         number | null
}

export interface NetworkHealth {
    total:      number
    active:     number
    sold_today: number
    /** Vendedores no activos (inactivos/bloqueados) */
    inactive:   number
    /** Activos sin vender en los últimos 7 días */
    idle_count: number
    /** Los 5 con más tiempo sin vender */
    idle:       NetworkIdleSeller[]
}

export interface NetworkPayments {
    pending_count:     number
    /** Monto de la moneda principal ya formateado ("Bs 120,00 +1 moneda(s)") */
    pending_amount:    string | null
    oldest_pending_at: string | null
    /** Rechazados en los últimos 7 días */
    rejected_count:    number
}

export interface PrizesSummary {
    count:  number
    amount: string | null
}
