import type { Currency } from '../../currency'
import type { Country } from '../../country'

export type AdminCommerceStatus = 'ACTIVE' | 'INACTIVE' | 'DIRECTORY'

export interface AdminCommerce {
    readonly id: number
    readonly currency?: string
    readonly exchange_rate: number
    readonly pct: number
    readonly status: AdminCommerceStatus
    readonly Currency?: Currency & {
        Country?: Country & {
            iso_code_2?: string
        }
    }
}

export interface AdminCommercePageProps {
    commerces: [Array<string>, String],
    countries: Country[],
    currencies: Currency[]
    exchangeRates: AdminCommerce[]
    tokenId?: string
}
