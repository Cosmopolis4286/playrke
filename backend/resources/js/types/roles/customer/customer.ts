export type CustomerStatus = 'ACTIVE' | 'INACTIVE' | 'BLOCKED' | 'DELETED'

export interface Customer {
    readonly id?: number | string,
    readonly name: string,
    readonly logo?: string,
    readonly api_route: string,
    readonly customer_key?: string,
    readonly country_id: number,
    readonly time_zone_id: number,
    readonly currency_id: number,
    readonly phone: string,
    readonly commission: string,
    readonly status: CustomerStatus,
    readonly username: string,
    readonly email: string,
    readonly other_currencies?: number[],
    readonly allowed_games?: number[]
}
