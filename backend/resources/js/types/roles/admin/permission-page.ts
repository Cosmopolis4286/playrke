import type { Permission, RoleWithPermissions } from './permission'

export interface PermissionPageProps {
  permissions: {
    rows: any[]
    pagination: {
      current_page: number
      per_page: number
      total: number
      last_page: number
      from: number
      to: number
    }
  }
  roles: RoleWithPermissions[]
}
