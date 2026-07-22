import type { Country } from '../../country'
import type { Column } from '../../common/table/column-row'
import type { Pagination } from '../../common/table/pagination'

export type UserStatus = 'ACTIVE' | 'INACTIVE' | 'DELETED' | 'SUSPENDED' | 'PENDING_VERIFICATION'

export interface AdminUser {
    readonly id: string | number
    readonly role_badge: string
    readonly full_name: string
    readonly username: string
    readonly email: string
    readonly country_name: string
    readonly status: UserStatus
    readonly balance?: number
    readonly currency?: {
        iso_code: string
        symbol: string
        name: string
    }
    /** Nombre de rol en minúsculas (ej. 'superadmin'), no es una columna visible. */
    readonly role_name: string
    readonly role_id: string | number | null
    readonly country_id: string | number | null
    readonly first_name: string | null
    readonly last_name: string | null
}

export interface AdminRole {
    readonly id: string | number
    readonly name: string
    readonly prefix: string | null
}

export interface AdminUserPageProps {
    users: {
        columns: Column[]
        rows: AdminUser[]
    }
    users_pagination: Pagination | null
    countries: Country[]
    roles: AdminRole[]
}

/**
 * Mapa de rol → ruta Inertia de creación.
 * Se usa en el SweetAlert de selección de rol.
 */
export const ROLE_ROUTE_MAP: Record<string, string> = {
    commerce:   '/admin/commerces',
    customer:   '/admin/customers',
    player:     '/admin/players',
    seller:     '/admin/sellers',
    treasurer:  '/admin/treasurers',
}
