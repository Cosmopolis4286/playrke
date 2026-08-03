import type { QuickLink } from '@/js/Components/Common/Dashboard/DashboardQuickLinks.vue'

const ADMIN_LINKS: QuickLink[] = [
  { label: 'Usuarios',   description: 'Gestionar todos los usuarios',  icon: 'fas fa-users',      href: '/admin/users',      variant: 'primary' },
  { label: 'Comercios',  description: 'Gestionar comercializadoras',   icon: 'fas fa-building',   href: '/admin/commerces',  variant: 'info' },
  { label: 'Vendedores', description: 'Gestionar vendedores',          icon: 'fas fa-user-tie',   href: '/admin/sellers',    variant: 'success' },
  { label: 'Clientes',   description: 'Gestionar clientes',            icon: 'fas fa-handshake',  href: '/admin/customers',  variant: 'warning' },
  { label: 'Jugadores',  description: 'Gestionar jugadores',           icon: 'fas fa-gamepad',    href: '/admin/players',    variant: 'secondary' },
  { label: 'Tesoreros',  description: 'Gestionar tesoreros',           icon: 'fas fa-piggy-bank', href: '/admin/treasurers', variant: 'secondary' },
]

const SUPERADMIN_EXTRA_LINKS: QuickLink[] = [
  { label: 'Administradores', description: 'Gestionar administradores del sistema', icon: 'fas fa-user-shield', href: '/admins',      variant: 'danger' },
  { label: 'Permisos',        description: 'Configurar permisos de roles',          icon: 'fas fa-key',         href: '/permissions', variant: 'warning' },
]

/**
 * Accesos rápidos del dashboard por rol. SuperAdmin extiende el set de Admin
 * con administración de cuentas/permisos, evitando mantener dos arrays
 * casi idénticos en cada Index.vue.
 */
export function useDashboardQuickLinks(role: 'admin' | 'superadmin'): QuickLink[] {
  return role === 'superadmin'
    ? [...SUPERADMIN_EXTRA_LINKS, ...ADMIN_LINKS]
    : ADMIN_LINKS
}
