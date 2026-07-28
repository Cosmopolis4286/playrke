/**
 * Tipo de sala (catálogo global, no atado a un juego específico).
 * Basado en la migración y modelo de RoomType.
 */
export interface RoomType {
    readonly id: string // Snowflake ID
    code: string // Único, formato ^[A-Za-z0-9_]+$ (PRIVATE, PUBLIC, VIP, DEMO...)
    label: string // Nombre visible es-ES
    status: 'ACTIVE' | 'INACTIVE'
    readonly created_at?: string
    readonly updated_at?: string
}
