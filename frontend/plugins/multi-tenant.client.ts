/**
 * Plugin de cliente para configuración multi-tenant.
 *
 * Detecta el contexto actual (admin/player) y configura
 * la aplicación según corresponda.
 */

export default defineNuxtPlugin(() => {
  // Solo en el cliente
  if (process.server) return

  const host = window.location.hostname
  const subdomain = host.split('.')[0]

  // Detectar contexto
  let context = 'player' // Por defecto

  // Verificar si es admin
  if (
    ['admin.playrke.local', 'admin.playrke.com'].includes(host) ||
    subdomain === 'admin'
  ) {
    context = 'admin'
  }

  // Establecer contexto global
  useState('app.context', () => context)

  // Configurar tema según el contexto
  const htmlElement = document.documentElement

  if (context === 'admin') {
    htmlElement.classList.add('admin-context')
    htmlElement.classList.remove('player-context')
  } else {
    htmlElement.classList.add('player-context')
    htmlElement.classList.remove('admin-context')
  }

  // Log para debugging
  console.log(`[Multi-Tenant] Contexto detectado: ${context}`)
  console.log(`[Multi-Tenant] Host: ${host}`)
  console.log(`[Multi-Tenant] Subdomain: ${subdomain}`)
})