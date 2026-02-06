import type { BanksVEN } from '../bank/banks-ven'
import type { Currency } from '../currency'
import type { DocumentType } from '../document-type'
import type { Column, Row } from '../common/table/column-row'

/**
 * Directorio de Pago Móvil (Venezuela)
 */
export interface DirectoryVEN {
    readonly id: string
    readonly holder_name: string
    readonly document_type: DocumentType
    readonly document_type_id: string
    readonly document_number: string
    readonly phone: string
    readonly bank: BanksVEN
    readonly bank_id: number | string
    readonly formatted_info?: string
    readonly created_at?: string
    readonly updated_at?: string
}

/**
 * Estructura de tabla para directorios (columns + rows)
 */
export interface DirectoryTableData {
    columns: Column[]
    rows: Row[]
}

/**
 * Props de la página de directorio
 */
export interface DirectoryVENPageProps {
    banks: BanksVEN[]
    documentTypes: DocumentType[]
    directories: DirectoryTableData
    currencies?: Currency[]
    tokenId?: string
}
