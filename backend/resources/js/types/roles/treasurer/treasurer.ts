export type TreasurerStatus = 'ACTIVE' | 'INACTIVE' | 'BLOCKED' | 'DELETED'

export interface Treasurer {
    id: string
    name: string
    /** Snowflake ID serializado como string (ver Country/Currency/TimeZone). */
    country_id: string | null
    time_zone_id: string | null
    currency_id: string | null
    phone: string
    has_commission?: boolean
    commission: string
    status?: TreasurerStatus
    username: string
    email: string
    other_currencies?: string[]
    allowed_games?: string[]
}
