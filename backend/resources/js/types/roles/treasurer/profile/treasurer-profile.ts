import type { TreasurerProfile } from '@/js/types/roles/shared/role-profile-common'

/** Ficha de autogestión del propio tesorero (GET/PUT /treasurer/profile). */
export type { TreasurerProfile }

export interface TreasurerProfilePageProps {
    treasurer: TreasurerProfile
    /** true si un admin/superadmin está viendo la ficha de otro tesorero (ver AdminProfileAccessService). */
    isAdminView?: boolean
}
