/**
 * Plugin de autenticación
 * Inicializa el sistema de autenticación y verifica el usuario actual
 */

export default defineNuxtPlugin(async () => {
  const { checkAuth } = useAuth()

  // Verificar autenticación al cargar la app
  if (process.client) {
    try {
      await checkAuth()
    } catch (error) {
      console.error('Error verificando autenticación:', error)
    }
  }

  return {
    provide: {
      auth: {
        checkAuth
      }
    }
  }
})
