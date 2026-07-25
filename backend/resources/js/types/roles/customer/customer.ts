export type CustomerStatus = 'ACTIVE' | 'INACTIVE' | 'BLOCKED' | 'DELETED'

export interface Customer {
    readonly id: string,
    readonly name: string,
    readonly logo?: string,
    readonly api_route: string,
    readonly customer_key?: string,
    /** Snowflake ID serializado como string (ver Country/Currency/TimeZone). */
    readonly country_id: string,
    readonly time_zone_id: string,
    readonly currency_id: string,
    readonly phone: string,
    readonly has_commission?: boolean,
    readonly commission: string,
    readonly status: CustomerStatus,
    readonly username: string,
    readonly email: string,
    readonly other_currencies?: string[],
    readonly allowed_games?: string[]
}
