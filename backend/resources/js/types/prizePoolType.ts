/**
 * Tipo de pool de premios / bote (catálogo dinámico, específico por juego).
 * Basado en la migración y modelo de PrizePoolType.
 */
export interface PrizePoolType {
    readonly id: string // Snowflake ID
    game_id: string
    code: string // Único por juego, formato ^[A-Za-z0-9_]+$
    label: string // Nombre visible es-ES
    status: 'ACTIVE' | 'INACTIVE'
    readonly created_at?: string
    readonly updated_at?: string
}
