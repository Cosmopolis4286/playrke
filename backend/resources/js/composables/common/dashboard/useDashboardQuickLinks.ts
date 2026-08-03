import { computed, type ComputedRef } from 'vue'
import type { QuickLink } from '@/js/Components/Common/Dashboard/DashboardQuickLinks.vue'
import type { EntityCount } from '@/js/types/roles/admin/dashboard/dashboard-admin'

export interface QuickLinkGroup {
  key: string
  title: string
  links: QuickLink[]
}

interface QuickLinkDef {
  label: string
  description: string
  icon: string
  href: string
  variant: QuickLink['variant']
  /** label de EntityCount cuyo valor se muestra como badge de conteo. */
  countsFrom?: string
}

const SYSTEM_LINKS: QuickLinkDef[] = [
  { label: 'Administradores', description: 'Gestionar administradores del sistema', icon: 'fas fa-user-shield', href: '/admins',      variant: 'danger',  countsFrom: 'Administradores' },
  { label: 'Permisos',        description: 'Configurar permisos de roles',          icon: 'fas fa-key',         href: '/permissions', variant: 'warning' },
]

const ENTITY_LINKS: QuickLinkDef[] = [
  { label: 'Usuarios',   description: 'Gestionar todos los usuarios',  icon: 'fas fa-users',      href: '/admin/users',      variant: 'primary' },
  { label: 'Comercios',  description: 'Gestionar comercializadoras',   icon: 'fas fa-building',   href: '/admin/commerces',  variant: 'info',      countsFrom: 'Comercios' },
  { label: 'Vendedores', description: 'Gestionar vendedores',          icon: 'fas fa-user-tie',   href: '/admin/sellers',    variant: 'success',   countsFrom: 'Vendedores' },
  { label: 'Clientes',   description: 'Gestionar clientes',            icon: 'fas fa-handshake',  href: '/admin/customers',  variant: 'warning',   countsFrom: 'Clientes' },
  { label: 'Jugadores',  description: 'Gestionar jugadores',           icon: 'fas fa-gamepad',    href: '/admin/players',    variant: 'secondary', countsFrom: 'Jugadores' },
  { label: 'Tesoreros',  description: 'Gestionar tesoreros',           icon: 'fas fa-piggy-bank', href: '/admin/treasurers', variant: 'secondary', countsFrom: 'Tesoreros' },
]

function resolveLinks(defs: QuickLinkDef[], entityCounts: EntityCount[]): QuickLink[] {
  return defs.map((def) => {
    const count = def.countsFrom ? entityCounts.find((e) => e.label === def.countsFrom)?.value : undefined
    return {
      label: def.label,
      description: count !== undefined ? `${count} registrados` : def.description,
      icon: def.icon,
      href: def.href,
      variant: def.variant,
    }
  })
}

/**
 * Accesos rápidos del dashboard, separados por responsabilidad: "Sistema"
 * (cuentas/permisos, solo SuperAdmin) y "Entidades" (gestión de negocio,
 * ambos roles). El conteo real de cada entidad (si `entityCounts` lo trae)
 * reemplaza la descripción genérica, convirtiendo el acceso en un mini-KPI
 * navegable.
 */
export function useDashboardQuickLinks(
  role: 'admin' | 'superadmin',
  entityCounts: ComputedRef<EntityCount[]> | EntityCount[] = [],
) {
  const counts = computed(() => Array.isArray(entityCounts) ? entityCounts : entityCounts.value)

  return computed<QuickLinkGroup[]>(() => {
    const groups: QuickLinkGroup[] = []

    if (role === 'superadmin') {
      groups.push({ key: 'system', title: 'Sistema', links: resolveLinks(SYSTEM_LINKS, counts.value) })
    }

    groups.push({ key: 'entities', title: 'Entidades', links: resolveLinks(ENTITY_LINKS, counts.value) })

    return groups
  })
}
