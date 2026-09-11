import type { Column, Row } from '../common/table/column-row'
import type { Currency } from '../currency'

/**
 * Estructura de tabla para directorios (columns + rows)
 */
export interface DirectoryTableData {
    columns: Column[]
    rows: Row[]
}

/**
 * Contexto dinámico devuelto por el endpoint fetch-context al cambiar de tab
 */
export interface DirectoryContext {
    banks: any[]
    documentTypes: any[]
    directories: DirectoryTableData
}

/**
 * Usuario dueño del directorio que se está viendo — solo se envía cuando
 * isImpersonation=true (un admin/superadmin gestionando el directorio de
 * otro usuario vía target_user_id).
 */
export interface DirectoryViewingUser {
    id: string
    username: string | null
    email: string
    full_name: string
}

/**
 * Props genéricas de la página de Directorio.
 * Válidas para cualquier país/método de pago.
 * `currencies` incluye `level` del pivot para ordenar los tabs (level=1 primero).
 */
export interface DirectoryPageProps {
    banks: any[]
    documentTypes: any[]
    directories: DirectoryTableData
    currencies?: Currency[]
    tokenId?: string
    roleUser?: string
    /** true si el usuario autenticado (admin/superadmin) está viendo el directorio de otro usuario. */
    isImpersonation?: boolean
    /** Dueño del directorio mostrado — presente solo cuando isImpersonation=true. */
    viewingUser?: DirectoryViewingUser | null
}
