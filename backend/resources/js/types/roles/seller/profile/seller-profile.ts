import type { SellerProfile } from '@/js/types/roles/shared/role-profile-common'

/** Ficha de autogestión del propio vendedor (GET/PUT /seller/profile). */
export type { SellerProfile }

export interface SellerProfilePageProps {
    seller: SellerProfile
    /** true si un admin/superadmin está viendo la ficha de otro vendedor (ver AdminProfileAccessService). */
    isAdminView?: boolean
}
