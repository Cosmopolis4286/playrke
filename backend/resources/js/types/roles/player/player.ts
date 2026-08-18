export type PlayerStatus = 'ACTIVE' | 'INACTIVE' | 'DELETED'

export interface Player {
    readonly id: number | string
    readonly username: string
    readonly email: string
    readonly first_name: string
    readonly last_name: string
    readonly phone: string
    readonly country_id: string | number | null
    readonly time_zone_id: string | number | null
    readonly status: PlayerStatus
    /** ID real del usuario (distinto de `id`, que viene hasheado para la tabla) — usado para abrir la ficha de detalle vía /admin/user/{real_id}/show. */
    readonly real_id?: string | number | null
}
