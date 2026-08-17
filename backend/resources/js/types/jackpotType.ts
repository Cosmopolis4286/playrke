/**
 * Tipo de jackpot (catálogo dinámico, específico por juego y por moneda).
 * Basado en la migración y modelo de JackpotType.
 */
export interface JackpotType {
    readonly id: string // Snowflake ID
    game_id: string
    currency_id: string
    code: string // Único por juego, formato ^[A-Za-z0-9_]+$
    label: string // Nombre visible es-ES
    periodicity: 'PER_ROOM' | 'WEEKLY' | 'BIWEEKLY' | 'MONTHLY'
    status: 'ACTIVE' | 'INACTIVE'
    /** Saldo actual del pozo — atributo de solo lectura, calculado por JackpotTypeService::listByGame(). */
    current_amount?: number
    readonly created_at?: string
    readonly updated_at?: string
}
