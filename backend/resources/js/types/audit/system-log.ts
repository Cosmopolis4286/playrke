import type { Column, Row } from '../common/table/column-row'

export interface SystemLogTableData {
    columns: Column[]
    rows: Row[]
    total?: number
    per_page?: number
    current_page?: number
    last_page?: number
}

export interface SystemLogLevelCount {
    level: string
    count: number
}

export interface SystemLogStats {
    total: number
    byLevel: SystemLogLevelCount[]
}

export interface SystemLogContextOption {
    label: string
    value: string
}

export interface SystemLogPageProps {
    logs: SystemLogTableData
    stats: SystemLogStats
    contexts: SystemLogContextOption[]
}

export interface SystemLogDetail {
    id: string
    date: string
    last_seen_at: string | null
    level: string
    message: string
    context: string | null
    stack: string | null
    meta: Record<string, unknown> | null
    occurrences: number
    reviewed_at: string | null
    reviewed_by: string | null
    server_timezone: string | null
}
