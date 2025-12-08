/**
 * Plugin de autenticación
 * Inicializa el sistema de autenticación y verifica el usuario actual
 */

export default defineNuxtPlugin(async () => {
  const { checkAuth } = useAuth()

  // Ejecutar solo en cliente (forma recomendada en Nuxt)
  if (import.meta.client) {
    checkAuth().catch((err) => {
      console.error('Error al verificar autenticación:', err)
    })
  }

  return {
    provide: {
      auth: { checkAuth }
    }
  }
})
