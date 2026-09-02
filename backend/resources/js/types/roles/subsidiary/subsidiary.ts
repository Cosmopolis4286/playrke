export type SubsidiaryStatus = 'ACTIVE' | 'INACTIVE' | 'BLOCKED' | 'DELETED'

export interface Subsidiary {
    id: string
    name: string
    /** Snowflake ID serializado como string (ver Commerce/Country/Currency). */
    commerce_id: string | null
    country_id: string | null
    currency_id: string | null
    phone: string
    has_commission?: boolean
    commission: string
    brand?: string | null
    product?: string | null
    link_route?: string | null
    status?: SubsidiaryStatus
    username: string
    email: string
}
