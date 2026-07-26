import type { Currency } from './currency'

export type ExchangeRateStatus = 'ACTIVE' | 'INACTIVE' | 'DIRECTORY' | 'OLD' | 'DELETED'

export interface ExchangeRate {
    readonly id?: number | string
    readonly currency_id?: number | string
    readonly currency_name?: string
    readonly user_name?: string
    readonly amount: number
    readonly percentage?: number
    readonly status: ExchangeRateStatus
    readonly token?: string
    readonly created_at?: string
    readonly updated_at?: string
}

/** Ver AdminExchangeRateResource::toArray() — tasas de cambio propias de un admin/comercio, con `currency` anidado. */
export interface AdminExchangeRate {
    readonly id?: number | string
    readonly currency_id?: number | string
    readonly amount: number
    readonly currency?: {
        readonly id: number | string
        readonly iso_code: string
        readonly name: string
    }
    readonly created_at?: string
    readonly updated_at?: string
}

export interface ExchangeRatePageProps {
    currencies: Currency[]
    exchangeRates: ExchangeRate[]
    roleUser: string
    tokenId?: string
    autoUpdateEnabled: boolean
    bcvAutoUpdateEnabled: boolean
}

export interface ExchangeRateHistoricalPageProps {
    exchangeRates: ExchangeRate[]
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
        id: number | string
        name: string
        iso_code: string
    } | null
}
