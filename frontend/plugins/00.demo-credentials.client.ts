/**
 * Plugin de credenciales demo
 * Se ejecuta ANTES del plugin de auth (por el prefijo 00)
 * para asegurar que las credenciales estén disponibles primero
 */

import { useDemoCredentials } from '~/composables/auth/useDemoCredentials'

export default defineNuxtPlugin(() => {
  if (import.meta.client) {
    const isDev = import.meta.env.DEV || import.meta.env.MODE === 'development'

    // Solo cargar credenciales en modo desarrollo
    if (isDev) {
      const { refresh } = useDemoCredentials()

      // Cargar credenciales demo en background (no bloquear la inicialización)
      // Esto permite que el store de auth se inicialice correctamente
      refresh().catch((err: Error) => {
        console.warn('[DemoCredentials] Error al cargar credenciales demo (no crítico):', err.message)
      })
    }
  }
})
