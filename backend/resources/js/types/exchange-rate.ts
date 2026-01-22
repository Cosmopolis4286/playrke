import type { Currency } from './currency'
import type { Country } from './country'

export type ExchangeRateStatus = 'ACTIVE' | 'INACTIVE' | 'DIRECTORY'

export interface ExchangeRate {
  readonly id: number
  readonly currency?: string
  readonly exchange_rate: number
  readonly percentage: number
  readonly status: ExchangeRateStatus
  readonly Currency?: Currency & {
    Country?: Country & {
      iso_code_2?: string
    }
  }
}

export interface ExchangeRatePageProps {
  currencies: Currency[]
  exchangeRates: ExchangeRate[]
  roleUser: string
  tokenId?: string
}
