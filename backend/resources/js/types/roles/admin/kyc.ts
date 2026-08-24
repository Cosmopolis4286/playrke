import type { Column } from '../../common/table/column-row'

export interface KycDocumentRow {
    readonly id: string
    readonly document_type: string
    readonly kyc_level: number | null
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

export interface KycHistoryEntry {
    readonly id: string
    readonly document_type: string
    readonly status: string
    readonly rejection_reason: string | null
    readonly created_at: string | null
    readonly reviewed_at: string | null
}

export interface KycDocumentDetail {
    readonly id: string
    readonly document_type: string
    readonly document_number: string
    readonly status: string
    readonly rejection_reason: string | null
    readonly reviewed_at: string | null
    readonly reviewed_by: string | null
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
    readonly history: KycHistoryEntry[]
}

export interface KycStats {
    readonly pending: number
    readonly approved_today: number
    readonly rejected_today: number
    readonly rejection_rate: number
}

export interface KycPageProps {
    documents: KycTableData
    stats: KycStats
}
