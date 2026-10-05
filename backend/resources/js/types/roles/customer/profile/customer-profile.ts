import type { CustomerProfile } from '@/js/types/roles/shared/role-profile-common'

/** Ficha de autogestión del propio cliente API (GET/PUT /customer/profile). */
export type { CustomerProfile }

export interface CustomerProfilePageProps {
    customer: CustomerProfile
    /** true si un admin/superadmin está viendo la ficha de otro cliente (ver AdminProfileAccessService). */
    isAdminView?: boolean
}
