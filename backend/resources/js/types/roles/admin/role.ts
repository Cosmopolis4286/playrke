export interface AdminRole {
  id: string
  name: string
  prefix: string
  description: string | null
  status: boolean
  sort_order: number
  icon: string | null
  color: string | null
}

export interface RoleHierarchyNode {
  id: string
  name: string
  prefix: string
  description: string | null
  function: string | null
  status: boolean
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
}
