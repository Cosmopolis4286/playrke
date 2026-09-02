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
    /** user_id del Commerce o Subsidiary al que reporta — ver ManagerSellerResource::toArray(). */
    readonly manager_user_id?: string | null
    /** Solo presente en filas de la vista admin (columna "Manejador"). */
    readonly manager_name?: string
    /** Solo presente en filas de la vista admin (columna "Rol") — badge HTML "Sucursal"/"Commerce". */
    readonly manager_role_badge?: string
}
