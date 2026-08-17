export type AdministratorStatus = 'ACTIVE' | 'INACTIVE' | 'DELETED'

export interface Administrator {
    readonly id: string
    /** Dato crudo (no es columna independiente): se muestra junto a full_name en el slot #cell-full_name. */
    readonly role_badge: string
    readonly full_name: string
    readonly username: string
    readonly email: string
    readonly phone_number: string | null
    readonly country_name: string
    readonly country_id: string | number | null
    readonly status: AdministratorStatus
    readonly status_badge: string
    /** Nombre de rol en minúsculas ('admin' | 'superadmin'), no es columna visible. */
    readonly role_name: string
    readonly first_name: string | null
    readonly last_name: string | null
    /** Fecha de registro en formato ISO 8601. */
    readonly registered_at: string | null
    /** Fecha de último inicio de sesión en formato ISO 8601, null si nunca inició sesión. */
    readonly last_login_at: string | null
    /** ID real del usuario (distinto de `id`, que viene hasheado para la tabla). */
    readonly real_id: string | number | null
}

/**
 * Datos del formulario de alta de un nuevo administrador (AdministratorForm.vue).
 * `id` nunca se usa (siempre es una creación) — solo está para satisfacer el
 * constraint genérico de useModalForm<T extends { id?: ... }>.
 */
export interface AdministratorCreateData {
    id?: string
    first_name: string
    last_name: string
    username: string
    email: string
    password: string
    password_confirmation: string
    country_id: string | number | null
    phone_number: string
}

/**
 * Datos editables del formulario de edición de un administrador existente.
 */
export interface AdministratorEditData {
    id: string | number
    first_name: string
    last_name: string
    username: string
    email: string
    phone_number: string
    country_id: string | number | null
}
