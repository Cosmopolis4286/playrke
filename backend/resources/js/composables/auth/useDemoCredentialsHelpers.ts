import type { DemoRole } from '@js/types/demo-credentials'

/**
 * ROLE_COLORS/ROLE_BADGE/ROLE_LABELS hardcodeados eliminados — no cubrían
 * 'superadmin' ni 'subsidiary' (caían al fallback de 'player'), y
 * duplicaban en 3 lugares distintos lo que ya vive en Role.icon/color/
 * description (BD). DemoCredentials.vue ahora usa formatRoleName()/el color
 * real del rol directo del payload (UserRepository::getDemoUsersForFrontend()),
 * mismo criterio que UserCreateRoleForm.vue.
 */

/** Ruta del dashboard según rol */
export const getDashboardRouteByRole = (role?: string): string => {
  switch (role?.toLowerCase() as DemoRole) {
    case 'admin':    return '/admin/dashboard'
    case 'commerce': return '/commerce/dashboard'
    case 'seller':   return '/seller/dashboard'
    case 'customer': return '/customer/dashboard'
    case 'player':
    default:         return '/dashboard'
  }
}
