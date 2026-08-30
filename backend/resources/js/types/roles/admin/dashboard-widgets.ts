export interface DashboardWidgetItem {
    key:         string
    dashboard:   'admin' | 'superadmin'
    type:        'stat' | 'entity' | 'quicklink'
    label:       string
    permission:  string | null
}

export interface DashboardWidgetRole {
    id:     string
    name:   string
    prefix: string
}

/**
 * item_key => { role_id => is_visible } — solo contiene excepciones
 * explícitas; ausencia de entrada = visible=true (misma semántica que BD).
 */
export type DashboardWidgetVisibilityMatrix = Record<string, Record<string, boolean>>

export interface DashboardWidgetsPageProps {
    items:      DashboardWidgetItem[]
    roles:      DashboardWidgetRole[]
    visibility: DashboardWidgetVisibilityMatrix
}
