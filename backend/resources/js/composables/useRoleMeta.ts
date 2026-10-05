export interface RoleMeta {
    key: string
    label: string
    icon: string
}

export const ROLE_META: Record<string, RoleMeta> = {
    superadmin: { key: 'superadmin', label: 'Super Admin', icon: 'fas fa-crown' },
    admin: { key: 'admin', label: 'Administrador', icon: 'fas fa-shield-alt' },
    treasurer: { key: 'treasurer', label: 'Tesorero', icon: 'fas fa-dollar-sign' },
    // commerce/subsidiary faltaban: caían al fallback (slug crudo "commerce"
    // con estilo de jugador). customer decía "Asesor" — el rol es "Cliente"
    // (RoleSeeder), integración API B2B.
    commerce: { key: 'commerce', label: 'Comercializadora', icon: 'fas fa-store' },
    subsidiary: { key: 'subsidiary', label: 'Agencia', icon: 'fas fa-code-branch' },
    seller: { key: 'seller', label: 'Vendedor', icon: 'fas fa-tags' },
    customer: { key: 'customer', label: 'Cliente', icon: 'fas fa-plug' },
    player: { key: 'player', label: 'Jugador', icon: 'fas fa-gamepad' },
    vip_player: { key: 'vip', label: 'VIP', icon: 'fas fa-star' },
}

export function getRoleMeta(name: string | undefined): RoleMeta {
    const resolved = name ?? 'player'
    return ROLE_META[resolved] ?? { key: 'player', label: resolved, icon: 'fas fa-user' }
}
