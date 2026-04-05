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

export interface DashboardSectionProps {
    title?: string
    subtitle?: string
    /** Icono FontAwesome para el encabezado */
    icon?: string
    /** Mostrar borde card */
    bordered?: boolean
    /** Mostrar skeleton mientras carga */
    loading?: boolean
    /** Clase CSS adicional para el wrapper */
    class?: string
}

// --------------------------------------------------------------------------
// Welcome Banner
// --------------------------------------------------------------------------

export interface WelcomeBannerProps {
    userName: string
    roleName?: string
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
