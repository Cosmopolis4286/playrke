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
}
