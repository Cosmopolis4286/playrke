export type CommerceStatus = 'ACTIVE' | 'INACTIVE' | 'BLOCKED' | 'DELETED'

export interface Commerce {
    readonly id: number | string,
    readonly name: string,
    readonly country_id: number,
    readonly time_zone_id: number,
    readonly currency_id: number,
    readonly phone: string,
    readonly commission: string,
    readonly status: CommerceStatus,
    readonly username: string,
    readonly email: string,
    readonly other_currencies?: number[],
    readonly allowed_games?: number[]
}
