/**
 * Plugin de API para realizar peticiones HTTP al backend
 * Configura $fetch con interceptores y manejo de errores
 */

export default defineNuxtPlugin(() => {
  const config = useRuntimeConfig()
  const { token, logout } = useAuth()

  // Crear instancia de fetch con configuración base
  const api = $fetch.create({
    baseURL: config.public.apiBase,

    // Interceptor de request
    onRequest({ options }) {
      // Agregar token de autenticación si existe
      if (token.value) {
        options.headers = {
          ...options.headers,
          Authorization: `Bearer ${token.value}`
        }
      }

      // Agregar headers comunes
      options.headers = {
        ...options.headers,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    },

    // Interceptor de respuesta
    onResponse({ response }) {
      // Manejar respuestas exitosas
      return response._data
    },

    // Interceptor de errores
    onResponseError({ response }) {
      const status = response.status
      const data = response._data

      // Manejar errores comunes
      switch (status) {
        case 401:
          // Token expirado o inválido
          logout()
          // Determinar contexto para login
          const currentPath = window.location.pathname
          const isAdminRoute = currentPath.startsWith('/admin')
          const loginPath = isAdminRoute ? '/admin/auth/login' : '/player/auth/login'
          navigateTo(loginPath)
          break

        case 403:
          // Sin permisos
          console.error('Acceso denegado:', data.message)
          break

        case 404:
          // Recurso no encontrado
          console.error('Recurso no encontrado')
          break

        case 422:
          // Errores de validación
          console.error('Errores de validación:', data.errors)
          break

        case 500:
          // Error del servidor
          console.error('Error del servidor:', data.message)
          break

        default:
          console.error('Error en la petición:', data)
      }

      throw new Error(data.message || 'Error en la petición')
    }
  })

  return {
    provide: {
      api
    }
  }
})
