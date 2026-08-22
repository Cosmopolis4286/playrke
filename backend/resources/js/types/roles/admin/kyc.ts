import type { Column } from '../../common/table/column-row'

export interface KycDocumentRow {
    readonly id: string
    readonly document_type: string
    readonly status: string
    readonly player_name: string
    readonly player_username: string
    readonly player_email: string
    readonly submitted_at: string | null
}

export interface KycTableData {
    columns: Column[]
    rows: KycDocumentRow[]
    total?: number
    per_page?: number
    current_page?: number
    last_page?: number
}

export interface KycDocumentDetail {
    readonly id: string
    readonly document_type: string
    readonly document_number: string
    readonly status: string
    readonly rejection_reason: string | null
    readonly reviewed_at: string | null
    readonly created_at: string | null
    readonly has_front: boolean
    readonly has_back: boolean
    readonly has_selfie: boolean
    readonly user: {
        id: string
        full_name: string
        username: string
        email: string
        kyc_level: number
        kyc_status: string
    }
}

export interface KycPageProps {
    documents: KycTableData
}
