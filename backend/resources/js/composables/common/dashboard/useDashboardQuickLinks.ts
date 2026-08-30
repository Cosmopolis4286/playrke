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
 *
 * `allowedQuickLinks` (labels) viene resuelto por el backend
 * (*DashboardContextService::allowedQuickLinks(), filtrado por permiso
 * Spatie real del usuario + DashboardWidgetVisibility configurable) — no se
 * repite ese chequeo aquí, solo se usa como whitelist final. Sin esa lista
 * (undefined, dashboards que aún no la envían), todos los links pasan —
 * fail-open, mismo criterio que el resto de la app.
 */
export function useDashboardQuickLinks(
  role: 'admin' | 'superadmin',
  entityCounts: ComputedRef<EntityCount[]> | EntityCount[] = [],
  allowedQuickLinks?: ComputedRef<string[] | undefined> | string[],
) {
  const counts = computed(() => Array.isArray(entityCounts) ? entityCounts : entityCounts.value)
  const allowed = computed(() => Array.isArray(allowedQuickLinks) ? allowedQuickLinks : allowedQuickLinks?.value)

  function filterAllowed(links: QuickLink[]): QuickLink[] {
    if (!allowed.value) return links
    const allowedSet = new Set(allowed.value)
    return links.filter((link) => allowedSet.has(link.label))
  }

  return computed<QuickLinkGroup[]>(() => {
    const groups: QuickLinkGroup[] = []

    if (role === 'superadmin') {
      const links = filterAllowed(resolveLinks(SYSTEM_LINKS, counts.value))
      if (links.length > 0) groups.push({ key: 'system', title: 'Sistema', links })
    }

    const entityLinks = filterAllowed(resolveLinks(ENTITY_LINKS, counts.value))
    if (entityLinks.length > 0) groups.push({ key: 'entities', title: 'Entidades', links: entityLinks })

    return groups
  })
}
