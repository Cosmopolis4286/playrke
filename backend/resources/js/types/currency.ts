import { Country } from "./country"

/**
 * Información de una moneda.
 * Basado en la migración y modelo de Currency.
 */
export interface Currency {
    id: string // Snowflake ID
    iso_code: string // Código único (USD, BTC, CASINO) - max 10 chars
    symbol: string | null // Símbolo ($, €, ₿, 🎰) - max 12 chars, nullable
    name: string // Nombre corto (Dollar, Bitcoin) - max 50 chars
    description: string | null // Descripción extendida - max 255 chars, nullable
    type: 'FIAT' | 'CRYPTO' | 'TOKEN' // Tipo de moneda
    country_id: string | null // Referencia a país - nullable
    decimal_places: number // Cantidad de decimales - default 2
    status: 'ACTIVE' | 'INACTIVE' // Estado - default ACTIVE
    country?: Country // relación opcional incluida
    created_at?: string
    updated_at?: string
}

export interface CurrencyPageProps {
    currencies: Currency[]
    countries: Country[]
    roleUser: string
    tokenId?: string
}
