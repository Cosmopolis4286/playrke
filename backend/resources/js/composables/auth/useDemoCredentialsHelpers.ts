import type { DemoRole } from '@js/types/demo-credentials'

export const ROLE_COLORS: Record<DemoRole, string> = {
  admin:    'from-blue-500 to-blue-700',
  commerce: 'from-orange-400 to-amber-500',
  seller:   'from-teal-500 to-cyan-600',
  customer: 'from-violet-500 to-purple-700',
  player:   'from-gray-400 to-gray-600',
}

export const ROLE_BADGE: Record<DemoRole, string> = {
  admin:    'bg-blue-100 text-blue-700',
  commerce: 'bg-orange-100 text-orange-700',
  seller:   'bg-teal-100 text-teal-700',
  customer: 'bg-violet-100 text-violet-700',
  player:   'bg-gray-100 text-gray-600',
}

export const ROLE_LABELS: Record<DemoRole, string> = {
  admin:    'Admin',
  commerce: 'Comercio',
  seller:   'Vendedor',
  customer: 'Cliente',
  player:   'Jugador',
}

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
