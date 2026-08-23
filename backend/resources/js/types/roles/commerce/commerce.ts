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
}
