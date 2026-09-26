export type SellerStatus = 'ACTIVE' | 'INACTIVE' | 'BLOCKED' | 'DELETED'

export interface Seller {
    readonly id: string,
    readonly name: string,
    /** Snowflake ID serializado como string (ver Country/Currency/TimeZone). */
    readonly country_id: string,
    readonly time_zone_id: string,
    readonly currency_id: string,
    readonly phone: string,
    readonly commission: string,
    readonly status: SellerStatus,
    readonly username: string,
    readonly email: string,
    readonly other_currencies?: string[],
    readonly allowed_games?: string[],
    /** user_id del Commerce o Subsidiary al que reporta — ver ManagerSellerResource::toArray(). NO confundir con `user_id` (dueño de ESTE seller). */
    readonly manager_user_id?: string | null
    /** user_id del propio vendedor (dueño de esta fila) — necesario para el botón "Ver perfil" (target_user_id de /seller/profile, ver AdminProfileAccessService). */
    readonly user_id?: string | null
    /** ID hasheado (Hashids) del User dueño de esta fila — necesario para el botón "Impersonar" (admin/impersonation/{player}/start espera el hash, no el Snowflake plano de user_id; ver HasHashedRouteKey). */
    readonly user_hashed_id?: string | null
    /** Solo presente en filas de la vista admin (columna "Manejador"). */
    readonly manager_name?: string
    /** Solo presente en filas de la vista admin (columna "Rol") — badge HTML "Sucursal"/"Commerce". */
    readonly manager_role_badge?: string
}

export interface SellerCurrencyDetail {
    readonly id: string
    readonly symbol: string
    readonly description: string
    readonly level: number
}

export interface SellerGameDetail {
    readonly id: string
    readonly name: string
}

/** A quién reporta este vendedor — jerarquía Comercio → (Agencia opcional) → Vendedor. */
export interface SellerReportsTo {
    readonly commerce: { id: string; name: string; status: SellerStatus } | null
    readonly subsidiary: { id: string; name: string; status: SellerStatus } | null
}

export interface SellerWalletBalance {
    readonly balance: number
    readonly bonus_balance: number
    readonly currency: { symbol: string | null; iso_code: string | null }
}

/** Ventas del mes en curso — vistazo rápido, no reporte financiero. */
export interface SellerActivity {
    readonly lots_count: number
    readonly cards_sold: number
    readonly sale_amount: number
    readonly commission_amount: number
}

/** Ficha de detalle de solo lectura (GET /sellers/{id}/show). */
export interface SellerDetail {
    readonly id: string
    readonly name: string
    readonly phone: string
    readonly commission: string
    readonly status: SellerStatus
    readonly status_badge: string
    readonly country: { id: string; name: string }
    readonly user: { username: string; email: string; time_zone_id: string | null }
    readonly currencies_detail: SellerCurrencyDetail[]
    readonly games_detail: SellerGameDetail[]
    readonly reports_to: SellerReportsTo
    readonly wallet_balances: SellerWalletBalance[]
    readonly activity: SellerActivity
}
