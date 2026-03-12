export type TreasurerStatus = 'ACTIVE' | 'INACTIVE' | 'BLOCKED' | 'DELETED'

export interface Treasurer {
    id: number | string
    name: string
    country_id: number | null
    time_zone_id: number | null
    currency_id: number | null
    phone: string
    has_commission?: boolean
    commission: string
    status?: TreasurerStatus
    username: string
    email: string
    other_currencies?: (number | string)[]
    allowed_games?: (number | string)[]
}
