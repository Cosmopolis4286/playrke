import type { CommerceProfile } from '@/js/types/roles/shared/role-profile-common'

/** Ficha de autogestión del propio comercio (GET/PUT /commerce/profile). */
export type { CommerceProfile }

export interface CommerceProfilePageProps {
    commerce: CommerceProfile
    /** true si un admin/superadmin está viendo la ficha de otro comercio (ver AdminProfileAccessService). */
    isAdminView?: boolean
}
