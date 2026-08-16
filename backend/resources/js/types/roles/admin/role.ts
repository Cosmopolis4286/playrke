export interface AdminRole {
  id: string
  name: string
  prefix: string
  description: string | null
  status: boolean
  sort_order: number
  icon: string | null
  color: string | null
  /** Fecha ISO 8601 del último cambio de `status` (auditoría), null si nunca cambió desde su creación. */
  status_changed_at: string | null
}

export interface RoleHierarchyNode {
  id: string
  name: string
  prefix: string
  /** Única fuente de descripción del rol — la misma columna que edita RoleManagerModal.vue ("Gestionar roles"). */
  description: string | null
  status: boolean
  /** Fecha ISO 8601 del último cambio de `status` (auditoría), null si nunca cambió desde su creación. */
  status_changed_at: string | null
  sort_order: number
  icon: string | null
  color: string | null
  guard_name: string
  users_count: number
  permissions_count: number
  children: RoleHierarchyNode[]
}

export interface RoleMetaUpdate {
  description?: string
  sort_order?: number
  icon?: string | null
  color?: string | null
  status?: boolean
}
