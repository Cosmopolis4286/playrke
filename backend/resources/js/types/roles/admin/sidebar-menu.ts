export interface SidebarMenuItem {
    key: string
    parent_key: string | null
    title: string
    permission: string | null
}

export interface SidebarMenuRole {
    id: string
    name: string
    prefix: string
}

/**
 * item_key => { role_id => is_visible } — solo contiene excepciones
 * explícitas; ausencia de entrada = visible=true (misma semántica que BD).
 */
export type SidebarMenuVisibilityMatrix = Record<string, Record<string, boolean>>

export interface SidebarMenuPageProps {
    items: SidebarMenuItem[]
    roles: SidebarMenuRole[]
    visibility: SidebarMenuVisibilityMatrix
}
