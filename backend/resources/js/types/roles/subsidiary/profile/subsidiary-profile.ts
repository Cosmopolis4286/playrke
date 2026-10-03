import type { SubsidiaryProfile } from '@/js/types/roles/shared/role-profile-common'

/** Ficha de autogestión de la propia agencia (GET/PUT /subsidiary/profile). */
export type { SubsidiaryProfile }

export interface SubsidiaryProfilePageProps {
    subsidiary: SubsidiaryProfile
    /** true si un admin/superadmin está viendo la ficha de otra agencia (ver AdminProfileAccessService). */
    isAdminView?: boolean
}
