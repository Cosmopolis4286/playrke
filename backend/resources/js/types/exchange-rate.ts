import type { Currency } from './currency'

export type ExchangeRateStatus = 'ACTIVE' | 'INACTIVE' | 'DIRECTORY'

export interface ExchangeRate {
    readonly id?: number | string
    readonly currency_id?: number | string
    readonly amount: number
    readonly percentage?: number
    readonly status: ExchangeRateStatus
    readonly token?: string
}

export interface ExchangeRatePageProps {
    currencies: Currency[]
    exchangeRates: ExchangeRate[]
    roleUser: string
    tokenId?: string
}

export interface UsedCurrency {
    id: number | string
    name: string
    description: string
    iso_code: string
    type: string
    country?: {
        id: number
        name: string
        iso_code: string
    } | null
}
