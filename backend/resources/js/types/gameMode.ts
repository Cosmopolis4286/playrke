/**
 * Modo de juego (catálogo dinámico, específico por juego).
 * Basado en la migración y modelo de GameMode.
 */
export interface GameMode {
    readonly id: string // Snowflake ID
    game_id: string
    code: string // Único por juego, formato ^[A-Za-z0-9_]+$
    label: string // Nombre visible es-ES
    status: 'ACTIVE' | 'INACTIVE'
    readonly created_at?: string
    readonly updated_at?: string
}

export interface GameModeGameOption {
    id: string
    name: string
    status: 'ACTIVE' | 'INACTIVE' | 'MAINTENANCE'
}

export interface GameModePageProps {
    gameModes: GameMode[]
    games: GameModeGameOption[]
}
