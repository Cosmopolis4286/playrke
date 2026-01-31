import type { BanksVEN } from './bank/banks-ven'

/**
 * Tipo de documento venezolano
 */
export interface DocumentType {
    readonly id: number | string
    readonly code: string
    readonly description: string
}

/**
 * Banco venezolano
 */
export interface BankVenezuela {
    readonly id: number | string
    readonly name: string
    readonly code?: string
}

/**
 * Directorio de Pago Móvil (Venezuela)
 */
export interface Directory {
    readonly id?: number | string
    readonly document_type?: DocumentType
    readonly document_type_id?: number | string
    readonly document: string
    readonly phone: string
    readonly bank?: BankVenezuela
    readonly bank_id?: number | string
    readonly formatted_info?: string
    readonly created_at?: string
    readonly updated_at?: string
}

/**
 * Props de la página de directorio
 */
export interface DirectoryPageProps {
    banks: BanksVEN[]
    documentTypes: DocumentType[]
    directories?: Directory[]
    roleUser: string
    tokenId?: string
}
