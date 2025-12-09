/**
 * Plugin de autenticación
 * Inicializa el sistema de autenticación y verifica el usuario actual
 */

export default defineNuxtPlugin(async () => {
  const { checkAuth } = useAuth()

  if (import.meta.client) {
    // No esperar a que checkAuth complete - dejar que se ejecute en background
    checkAuth().catch((err: Error) => {
      console.warn('[Auth] Error al verificar sesión (no crítico):', err.message)
    })
  }

  return {
    provide: {
      auth: { checkAuth }
    }
  }
})
