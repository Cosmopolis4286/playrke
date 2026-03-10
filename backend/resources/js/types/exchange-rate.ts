import type { Currency } from './currency'

export type ExchangeRateStatus = 'ACTIVE' | 'INACTIVE' | 'DIRECTORY' | 'OLD' | 'DELETED'

export interface ExchangeRate {
    readonly id?: number | string
    readonly currency_id?: number | string
    readonly currency_name?: string
    readonly amount: number
    readonly percentage?: number
    readonly status: ExchangeRateStatus
    readonly token?: string
    readonly created_at?: string
    readonly updated_at?: string
}

export interface AdminExchangeRate {
    readonly id?: number | string
    readonly currency_id?: number | string
    readonly currency_name?: string
    readonly amount: number
    readonly currency?: {
        id: number | string
        iso_code: string
        name: string
    }
    readonly created_at?: string
    readonly updated_at?: string
}

export interface ExchangeRatePageProps {
    currencies: Currency[]
    exchangeRates: ExchangeRate[] | AdminExchangeRate[]
    roleUser: string
    tokenId?: string
}

export interface ExchangeRateHistoricalPageProps {
    exchangeRates: ExchangeRate[] | AdminExchangeRate[]
    currencyId: string
    roleUser: string
    title: string
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
