export interface Permission {
  id: string
  name: string
  guard_name: string
  description?: string
  status: boolean
  created_at?: string
  updated_at?: string
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
