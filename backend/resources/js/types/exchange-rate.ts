import type { Currency, Country } from './data-models'

export type ExchangeRateStatus = 'ACTIVE' | 'INACTIVE' | 'DIRECTORY'

export interface ExchangeRate {
  readonly id: number
  readonly currency?: string
  readonly exchange_rate: number
  readonly pct: number
  readonly status: ExchangeRateStatus
  readonly Currency?: Currency & {
    Country?: Country & {
      iso_code_2?: string
    }
  }
}

export interface Creator {
  readonly id: number | string
  readonly type: 'customer' | 'company' | 'seller'
}
