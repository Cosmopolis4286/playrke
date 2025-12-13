/**
 * Plugin para restaurar la sesión desde localStorage
 * Se ejecuta después de las credenciales demo (00) pero antes del check de auth regular
 *
 * IMPORTANTE: Este plugin garantiza que la sesión se restaure INMEDIATAMENTE
 * desde localStorage al recargar la página, evitando redirects innecesarios
 */

export default defineNuxtPlugin(() => {
  if (import.meta.client) {
    const authStore = useAuthStore()

    // Restaurar sesión desde localStorage
    const stored = authStore.getAuth()

    if (stored.token && stored.user) {
      console.log('[RestoreAuth] Restaurando sesión desde localStorage:', {
        user: stored.user.username,
        roles: stored.user.roles
      })

      // Restaurar en el store INMEDIATAMENTE
      authStore.setAuth(stored.token, stored.user)
    } else {
      console.log('[RestoreAuth] No hay sesión guardada en localStorage')
    }
  }
})
