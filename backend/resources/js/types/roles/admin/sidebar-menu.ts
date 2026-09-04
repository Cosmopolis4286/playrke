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

/**
 * item_key => { role_id => sort_order }. Ausencia de entrada, o valor
 * null, significa "orden natural de $menuStructure" (misma semántica de
 * excepción que SidebarMenuVisibilityMatrix).
 */
export type SidebarMenuSortOrderMatrix = Record<string, Record<string, number | null>>

export interface SidebarMenuPageProps {
    items: SidebarMenuItem[]
    roles: SidebarMenuRole[]
    visibility: SidebarMenuVisibilityMatrix
    sortOrder: SidebarMenuSortOrderMatrix
}
