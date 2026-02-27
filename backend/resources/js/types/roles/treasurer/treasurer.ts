export type TreasurerStatus = 'ACTIVE' | 'INACTIVE' | 'BLOCKED' | 'DELETED'

export interface Treasurer {
    readonly id?: number | string,
    readonly name: string,
    readonly logo?: string,
    readonly api_route: string,
    readonly treasurer_key?: string,
    readonly country_id: number,
    readonly time_zone_id: number,
    readonly currency_id: number,
    readonly phone: string,
    readonly has_commission?: boolean,
    readonly commission: string,
    readonly status: TreasurerStatus,
    readonly username: string,
    readonly email: string,
    readonly other_currencies?: number[],
    readonly allowed_games?: number[]
}
