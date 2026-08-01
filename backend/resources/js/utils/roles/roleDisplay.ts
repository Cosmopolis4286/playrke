/**
 * Orden jerárquico real del sistema (ver RoleSeeder.php): administración de
 * plataforma → negocio → operativo → consumidor final → externo. Roles que
 * no aparezcan aquí (futuros, o creados fuera del seeder) no se ocultan —
 * se ordenan alfabéticamente al final.
 */
export const ROLE_HIERARCHY = [
  'superadmin', 'admin',
  'commerce', 'subsidiary', 'seller', 'treasurer',
  'customer', 'player',
  'provider',
]

export function sortByRoleHierarchy<T extends { name: string }>(roles: T[]): T[] {
  return [...roles].sort((a, b) => {
    const ia = ROLE_HIERARCHY.indexOf(a.name.toLowerCase())
    const ib = ROLE_HIERARCHY.indexOf(b.name.toLowerCase())
    if (ia === -1 && ib === -1) return a.name.localeCompare(b.name)
    if (ia === -1) return 1
    if (ib === -1) return -1
    return ia - ib
  })
}

export function formatRoleName(name: string): string {
  const map: Record<string, string> = {
    superadmin: 'SuperAdmin', admin: 'Admin', commerce: 'Comercio',
    subsidiary: 'Sucursal', seller: 'Vendedor', provider: 'Proveedor',
    customer: 'Cliente', treasurer: 'Tesorero', player: 'Jugador',
  }
  return map[name.toLowerCase()] ?? name
}

export function getRoleBadgeClass(name: string): string {
  const map: Record<string, string> = {
    superadmin: 'badge-purple', admin: 'badge-blue', commerce: 'badge-green',
    subsidiary: 'badge-teal', seller: 'badge-yellow', provider: 'badge-orange',
    customer: 'badge-pink', treasurer: 'badge-info', player: 'badge-secondary',
  }
  return map[name.toLowerCase()] ?? 'badge-secondary'
}

export function isRoleSuperadmin(name: string): boolean {
  return name.toLowerCase() === 'superadmin'
}
