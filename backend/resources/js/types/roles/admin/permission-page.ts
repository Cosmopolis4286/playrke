import type { Permission, RoleWithPermissions } from './permission'
import type { SystemModule } from './module'

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
  modules: SystemModule[]
}
