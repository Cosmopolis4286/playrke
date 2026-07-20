export interface RoleMeta {
    key: string
    label: string
    icon: string
}

export const ROLE_META: Record<string, RoleMeta> = {
    superadmin: { key: 'superadmin', label: 'Super Admin', icon: 'fas fa-crown' },
    admin: { key: 'admin', label: 'Administrador', icon: 'fas fa-shield-alt' },
    treasurer: { key: 'treasurer', label: 'Tesorero', icon: 'fas fa-dollar-sign' },
    seller: { key: 'seller', label: 'Vendedor', icon: 'fas fa-tags' },
    customer: { key: 'customer', label: 'Asesor', icon: 'fas fa-headset' },
    player: { key: 'player', label: 'Jugador', icon: 'fas fa-gamepad' },
    vip_player: { key: 'vip', label: 'VIP', icon: 'fas fa-star' },
}

export function getRoleMeta(name: string | undefined): RoleMeta {
    const resolved = name ?? 'player'
    return ROLE_META[resolved] ?? { key: 'player', label: resolved, icon: 'fas fa-user' }
}
