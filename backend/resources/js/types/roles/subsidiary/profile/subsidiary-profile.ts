import type { RoleProfileWallet, RoleProfileMovement, RoleProfileAccountMeta } from '@/js/types/roles/shared/role-profile-common'

/** Ficha de autogestión de la propia agencia (GET/PUT /subsidiary/profile). */
export interface SubsidiaryProfile {
    id: string
    name: string
    phone: string
    status: string
    status_badge: string
    commerce: { name: string; status: string } | null
    country: string | null
    currency: string | null
    user: { username: string; email: string }
    commission: number
    network: {
        sellers_count: number
    }
    wallets: RoleProfileWallet[]
    recent_movements: RoleProfileMovement[]
    account_meta: RoleProfileAccountMeta
}

export interface SubsidiaryProfilePageProps {
    subsidiary: SubsidiaryProfile
    /** true si un admin/superadmin está viendo la ficha de otra agencia (ver AdminProfileAccessService). */
    isAdminView?: boolean
}
