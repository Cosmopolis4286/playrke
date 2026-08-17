import type { Country } from '../../country'
import type { Column } from '../../common/table/column-row'
import type { Pagination } from '../../common/table/pagination'

export type UserStatus = 'ACTIVE' | 'INACTIVE' | 'DELETED' | 'SUSPENDED' | 'PENDING_VERIFICATION'

export interface AdminUser {
    readonly id: string | number
    /** Dato crudo (no es columna independiente): se muestra junto a full_name en el slot #cell-full_name. */
    readonly role_badge: string
    readonly full_name: string
    /** Dato crudo (no es columna independiente): se muestra junto a full_name en el slot #cell-full_name. */
    readonly username: string
    /** Dato crudo (no es columna independiente): se muestra junto a full_name en el slot #cell-full_name. */
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
    readonly phone_number: string | null
    /** true si el usuario tiene permisos específicos asignados además de los de su rol. */
    readonly has_specific_permissions: boolean
    /** Cantidad de permisos individuales asignados directamente (además de los de su rol). */
    readonly specific_permissions_count: number
    /** ID real del usuario (distinto de `id`, que viene hasheado para la tabla). */
    readonly real_id: string | number | null
    /** Fecha de registro en formato ISO 8601, para formatear tiempo relativo en el frontend. */
    readonly registered_at: string | null
    /** ID del registro de dominio (Commerce/Seller/Customer/Treasurer, o el propio User para player) para el botón "Ver más". Null si el rol no tiene página dedicada. */
    readonly domain_id: string | number | null
}

export interface AdminRole {
    readonly id: string | number
    readonly name: string
    readonly prefix: string | null
}

export interface AdminUserDetailAudit {
    readonly id: string
    readonly date: string | null
    readonly actor: string
    readonly event: string
}

export interface AdminUserDetail {
    readonly id: string
    readonly full_name: string
    readonly username: string
    readonly email: string
    readonly status: UserStatus
    readonly country: string | null
    readonly role_name: string
    readonly role_label: string | null
    readonly created_at: string | null
    readonly has_specific_permissions: boolean
    readonly wallet: { balance: number; currency: { iso_code: string; symbol: string; name: string } | null } | null
    readonly level: Record<string, unknown> | null
    readonly recent_audits: AdminUserDetailAudit[]
}

/**
 * Datos editables del formulario de edición de usuario (UserEditForm.vue).
 */
export interface AdminUserEditData {
    id: string | number
    first_name: string
    last_name: string
    username: string
    email: string
    phone_number: string
    country_id: string | number | null
    role_id: string | number | null
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
 */
export const ROLE_ROUTE_MAP: Record<string, string> = {
    commerce:   '/admin/commerces',
    customer:   '/admin/customers',
    player:     '/admin/players',
    seller:     '/admin/sellers',
    treasurer:  '/admin/treasurers',
}

/**
 * Mapa de rol → ruta de la página dedicada, para el botón "Ver más" del
 * modal de edición (UserEditForm.vue). Superset de ROLE_ROUTE_MAP: incluye
 * 'admin' -> /admins, que no admite creación por el flujo genérico de
 * ROLE_ROUTE_MAP (los admins se gestionan aparte, ver AdministratorController)
 * pero sí tiene una página de listado a la que navegar.
 */
export const ROLE_DETAIL_ROUTE_MAP: Record<string, string> = {
    ...ROLE_ROUTE_MAP,
    admin: '/admins',
}

/**
 * Roles cuya página dedicada (ROLE_DETAIL_ROUTE_MAP) sabe auto-abrir la
 * ficha de un registro específico vía ?open={domainId} (ver onMounted en
 * cada Roles/Admin/{Rol}/Index.vue). 'admin' queda fuera: /admins
 * (Administrator/Index.vue) no tiene modal de edición de datos, solo
 * gestión de estado/contraseña — "Ver más" ahí es pura navegación, no
 * requiere resolver domain_id.
 */
export const ROLES_WITH_AUTO_OPEN = new Set(Object.keys(ROLE_ROUTE_MAP))
