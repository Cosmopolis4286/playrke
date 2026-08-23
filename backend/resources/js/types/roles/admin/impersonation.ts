import type { Column } from '../../common/table/column-row'

export interface ImpersonationHistoryRow {
    readonly id: string
    readonly admin_username: string
    readonly target_username: string
    readonly started_at: string | null
    readonly ended_at: string | null
    readonly is_active: boolean
    readonly duration_minutes: number | null
}

export interface ImpersonationHistoryTableData {
    columns: Column[]
    rows: ImpersonationHistoryRow[]
    total?: number
    per_page?: number
    current_page?: number
    last_page?: number
}

export interface ImpersonationHistoryPageProps {
    history: ImpersonationHistoryTableData
}
