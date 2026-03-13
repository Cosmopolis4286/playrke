import { Country } from "./country"

/**
 * Información de una moneda.
 * Basado en la migración y modelo de Currency.
 */
export interface Currency {
    readonly id: string // Snowflake ID
    readonly iso_code: string // Código único (USD, BTC, CASINO) - max 10 chars
    readonly symbol: string | null // Símbolo ($, €, ₿, 🎰) - max 12 chars, nullable
    readonly name: string // Nombre corto (Dollar, Bitcoin) - max 50 chars
    readonly description: string | null // Descripción extendida - max 255 chars, nullable
    readonly type: 'FIAT' | 'CRYPTO' | 'TOKEN' // Tipo de moneda
    readonly country_id: string | null // Referencia a país - nullable
    readonly decimal_places: number // Cantidad de decimales - default 2
    readonly status: 'ACTIVE' | 'INACTIVE' // Estado - default ACTIVE
    readonly country?: Country // relación opcional incluida
    readonly created_at?: string
    readonly updated_at?: string
}

export interface CurrencyPageProps {
    currencies: Currency[]
    countries: Country[]
    roleUser: string
    tokenId?: string
}
