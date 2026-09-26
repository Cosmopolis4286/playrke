export type SubsidiaryStatus = 'ACTIVE' | 'INACTIVE' | 'BLOCKED' | 'DELETED'

export interface Subsidiary {
    id: string
    name: string
    /** Snowflake ID serializado como string (ver Commerce/Country/Currency). */
    commerce_id: string | null
    country_id: string | null
    currency_id: string | null
    phone: string
    has_commission?: boolean
    commission: string
    status?: SubsidiaryStatus
    username: string
    email: string
    /** ID del usuario dueño de la agencia — necesario para el botón "Ver perfil" (target_user_id de /subsidiary/profile, ver AdminProfileAccessService). */
    user_id?: string | null
    /** ID hasheado (Hashids) del User dueño de la agencia — necesario para el botón "Impersonar" (admin/impersonation/{player}/start espera el hash, no el Snowflake plano de user_id; ver HasHashedRouteKey). */
    user_hashed_id?: string | null
}

export interface SubsidiarySellerSummary {
    readonly id: string
    readonly name: string
    readonly phone: string
    readonly status: string
    readonly status_badge: string
}

/** Jerarquía hacia abajo (Sellers propios de esta Subsidiary) — la jerarquía hacia arriba (Commerce padre) ya viene en el campo `commerce` de la ficha. */
export interface SubsidiaryHierarchy {
    readonly sellers: SubsidiarySellerSummary[]
    readonly sellers_count: number
}

/** Ficha de detalle de solo lectura (GET /admin/subsidiary/{id}/show). */
export interface SubsidiaryDetail {
    readonly id: string
    readonly name: string
    readonly phone: string
    readonly commission: string
    readonly status: SubsidiaryStatus
    readonly status_badge: string
    readonly sellers_count: number
    readonly commerce: { id: string; name: string; status: string; status_badge: string } | null
    readonly country: { id: string; name: string }
    readonly user: { username: string; email: string }
    readonly currency: string | null
    readonly hierarchy: SubsidiaryHierarchy
}
