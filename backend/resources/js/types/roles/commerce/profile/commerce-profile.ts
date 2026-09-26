import type { RoleProfileWallet, RoleProfileMovement, RoleProfileAccountMeta } from '@/js/types/roles/shared/role-profile-common'

/** Ficha de autogestión del propio comercio (GET/PUT /commerce/profile). */
export interface CommerceProfile {
    id: string
    name: string
    phone: string
    status: string
    status_badge: string
    country: string | null
    user: { username: string; email: string }
    commission: number
    network: {
        subsidiaries_count: number
        sellers_count: number
    }
    currencies: string[]
    games: string[]
    wallets: RoleProfileWallet[]
    recent_movements: RoleProfileMovement[]
    account_meta: RoleProfileAccountMeta
}

export interface CommerceProfilePageProps {
    commerce: CommerceProfile
    /** true si un admin/superadmin está viendo la ficha de otro comercio (ver AdminProfileAccessService). */
    isAdminView?: boolean
}
