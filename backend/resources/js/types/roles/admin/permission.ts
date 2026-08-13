export interface Permission {
  id: string
  name: string
  guard_name: string
  description?: string
  /** true = permiso estructural del sistema (roles.*, superadmin.access, system.*) — no editable ni eliminable. */
  is_system: boolean
  /** true = no está asignado a ningún rol actualmente. */
  is_orphan?: boolean
  status: boolean
  created_at?: string
  updated_at?: string
  selected?: boolean // Para checkboxes en el modal
}

export interface RolePermission {
  roleId: string
  roleName: string
  rolePrefix: string
  permissions: Permission[]
}

export interface RoleWithPermissions {
  id: string
  name: string
  prefix: string
  permissions: string[]
}
