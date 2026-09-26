export type CommerceStatus = 'ACTIVE' | 'INACTIVE' | 'BLOCKED' | 'DELETED'

export interface Commerce {
    readonly id: string,
    readonly name: string,
    /** Snowflake ID serializado como string (ver Country/Currency). */
    readonly country_id: string,
    readonly time_zone_id: string,
    readonly currency_id: string,
    readonly phone: string,
    readonly commission: string,
    readonly status: CommerceStatus,
    readonly username: string,
    readonly email: string,
    readonly other_currencies?: string[],
    readonly allowed_games?: string[]
    /** ID real (Snowflake sin hashear) — necesario para abrir la ficha de detalle. Null en filas sin real_id (ej. fuera del listado paginado). */
    readonly real_id?: string | null
    /** ID del usuario dueño del comercio — necesario para el botón "Ver perfil" (target_user_id de /commerce/profile, ver AdminProfileAccessService). */
    readonly user_id?: string | null
    /** true si este comercio es el principal/vinculado a la app. Solo viene poblado tras cargar el detalle completo (edit()/show()), no en las filas de la tabla. */
    is_default_commerce?: boolean
}

export interface CommerceCurrencyDetail {
    readonly id: string
    readonly symbol: string
    readonly description: string
    readonly level: number
}

export interface CommerceGameDetail {
    readonly id: string
    readonly name: string
}

export interface CommerceSellerSummary {
    readonly id: string
    readonly name: string
    readonly phone: string
    readonly status: CommerceStatus
    readonly status_badge: string
}

export interface CommerceSubsidiarySummary {
    readonly id: string
    readonly name: string
    readonly status: CommerceStatus
    readonly status_badge: string
    readonly sellers: CommerceSellerSummary[]
}

/** Jerarquía real Comercializadora → Agencia → Vendedor de este comercio. */
export interface CommerceHierarchy {
    readonly direct_sellers: CommerceSellerSummary[]
    readonly subsidiaries: CommerceSubsidiarySummary[]
    readonly direct_sellers_count: number
    readonly subsidiaries_count: number
    readonly subsidiary_sellers_count: number
    readonly total_sellers_count: number
}

export interface CommercePagoMovilAccount {
    readonly id: string
    readonly holder_name: string
    readonly phone: string
    readonly bank_name: string | null
    readonly status: string
}

export interface CommercePixKeyAccount {
    readonly id: string
    readonly holder_name: string
    readonly pix_key_type: string
    readonly pix_key_value: string
    readonly bank_name: string | null
    readonly status: string
}

/** Cuentas de cobro (Directorio) del usuario dueño del comercio. */
export interface CommerceDirectoryAccounts {
    readonly pago_movil: CommercePagoMovilAccount[]
    readonly pix_keys: CommercePixKeyAccount[]
}

/** Ficha de detalle de solo lectura (GET /admin/commerce/{id}/show). */
export interface CommerceDetail {
    readonly id: string
    readonly name: string
    readonly phone: string
    readonly commission: string
    readonly status: CommerceStatus
    readonly status_badge: string
    readonly unique_code: string | null
    readonly sellers_count: number
    readonly country: { id: string; name: string }
    readonly user: { username: string; email: string; time_zone_id: string | null }
    readonly currencies_detail: CommerceCurrencyDetail[]
    readonly games_detail: CommerceGameDetail[]
    /** true si este comercio es el principal/vinculado a la app (platform_config.default_commerce_id). */
    readonly is_default_commerce: boolean
    readonly hierarchy: CommerceHierarchy
    readonly directory_accounts: CommerceDirectoryAccounts
}
