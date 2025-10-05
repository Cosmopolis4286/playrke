/**
 * Middleware global para manejo de multi-tenancy.
 *
 * Detecta automáticamente si la ruta corresponde a admin o player
 * basándose en el dominio/subdominio y redirige a la layer apropiada.
 */

export default defineNuxtRouteMiddleware((to) => {
  // Solo ejecutar en el cliente
  if (process.server) return

  const host = window.location.hostname
  const subdomain = host.split('.')[0]

  // Configuración de dominios
  const config = {
    admin: {
      domains: ['admin.playrke.local', 'admin.playrke.com'],
      subdomains: ['admin'],
      basePath: '/admin'
    },
    player: {
      domains: ['play.playrke.local', 'play.playrke.com', 'playrke.local', 'playrke.com'],
      subdomains: ['play', 'www'],
      basePath: '/player'
    }
  }

  // Detectar contexto actual
  let currentContext = 'player' // Por defecto player

  // Verificar dominios admin
  if (config.admin.domains.includes(host) || config.admin.subdomains.includes(subdomain)) {
    currentContext = 'admin'
  }

  // Verificar dominios player explícitos
  if (config.player.domains.includes(host) || config.player.subdomains.includes(subdomain)) {
    currentContext = 'player'
  }

  // Verificar si estamos en el contexto correcto
  const isAdminRoute = to.path.startsWith('/admin')
  const isPlayerRoute = to.path.startsWith('/player') || to.path === '/'

  // Redirigir si no coincide el contexto
  if (currentContext === 'admin' && !isAdminRoute) {
    return navigateTo('/admin' + (to.path === '/' ? '' : to.path))
  }

  if (currentContext === 'player' && !isPlayerRoute && !isAdminRoute) {
    return navigateTo('/player' + to.path)
  }

  // Establecer contexto en el estado global
  useState('app.context', () => currentContext)
})