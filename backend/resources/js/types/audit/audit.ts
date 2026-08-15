import type { Column, Row } from '../common/table/column-row'

export interface AuditTableData {
    columns: Column[]
    rows: Row[]
    total?: number
    per_page?: number
    current_page?: number
    last_page?: number
}

export interface AuditFilterOption {
    label: string
    value: string
}

export interface AuditFilterOptions {
    events: AuditFilterOption[]
    actorTypes: AuditFilterOption[]
    entities: AuditFilterOption[]
}

export interface AuditTopEntity {
    entity: string
    count: number
}

export interface AuditStats {
    total: number
    topEntities: AuditTopEntity[]
}

export interface AuditPageProps {
    audits: AuditTableData
    filterOptions: AuditFilterOptions
    initialFilters?: {
        entity?: string
        auditable_id?: string
    }
    stats: AuditStats
}

export interface AuditDetail {
    id: number
    date: string
    actor: string
    actor_type: string
    event: string
    entity: string
    entity_id: unknown
    old_values: Record<string, unknown> | null
    new_values: Record<string, unknown> | null
    url: string | null
    ip_address: string | null
    user_agent: string | null
}
