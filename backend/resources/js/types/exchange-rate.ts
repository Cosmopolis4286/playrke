import type { Currency } from './currency'

export type ExchangeRateStatus = 'ACTIVE' | 'INACTIVE' | 'DIRECTORY'

export interface ExchangeRate {
    readonly id: number
    readonly currency_id?: number | string
    readonly exchange_rate: number
    readonly percentage: number
    readonly status: ExchangeRateStatus
}

export interface ExchangeRatePageProps {
    currencies: Currency[]
    exchangeRates: ExchangeRate[]
    roleUser: string
    tokenId?: string
}

export interface UsedCurrency {
    id: number
    name: string
    iso_code: string,
    country?: {
        id: number
        name: string
        iso_code: string
    } | null
}
