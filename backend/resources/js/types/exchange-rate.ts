import type { Currency } from './currency'

export type ExchangeRateStatus = 'ACTIVE' | 'INACTIVE' | 'DIRECTORY' | 'OLD' | 'DELETED'

/** 'user' (acción manual o botón "Consultar") o 'system' (cronjob
 *  automático, ver HandleExchangeRatesCommand) — ExchangeRate::SOURCE_*. */
export type ExchangeRateSource = 'user' | 'system'

export interface ExchangeRate {
    readonly id?: number | string
    readonly currency_id?: number | string
    readonly currency_name?: string
    readonly user_name?: string
    readonly amount: number
    readonly percentage?: number
    readonly status: ExchangeRateStatus
    /** Ausente en filas antiguas a las que la migración de columna les
     *  aplicó el default 'user' — nunca null/undefined en la práctica tras
     *  la migración, pero opcional por si acaso llega sin poblar. */
    readonly source?: ExchangeRateSource
    readonly token?: string
    readonly created_at?: string
    readonly updated_at?: string
    /** Solo saliente (nunca viene del backend): confirma explícitamente un
     *  cambio >50% respecto al valor vigente — ver
     *  ExchangeRateRequest::validateAmountJump(). Sin esto, ese salto se
     *  rechaza con 422 en vez de guardarse silenciosamente. */
    confirm_large_jump?: boolean
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
    /** Catálogo completo de monedas — reutilizado por ExchangeRateForm en
     *  el botón "Editar" (mismo modal de /exchange-rate), ver
     *  ExchangeRateContextService::resolveHistorical(). */
    currencies: Currency[]
    tokenId?: string
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
