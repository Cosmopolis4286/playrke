import type { Currency } from './currency'

export type DirectoryStatus = 'ACTIVE' | 'INACTIVE' | 'DIRECTORY'

export interface Directory {
    readonly id?: number | string
    readonly currency_id?: number | string
    readonly amount: number
    readonly percentage?: number
    readonly status: DirectoryStatus
    readonly token?: string
}

export interface DirectoryPageProps {
    currencies: Currency[]
    exchangeRates: Directory[]
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
