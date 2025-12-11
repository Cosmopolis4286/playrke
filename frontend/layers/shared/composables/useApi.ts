/**
 * Composable base para llamadas API
 *
 * Proporciona funciones helper para hacer llamadas HTTP
 * a la API de Laravel con configuración consistente.
 */

export const useApi = () => {
  const config = useRuntimeConfig()
  const baseURL = config.public.apiBase

  let csrfLastFetched = 0
  const csrfCacheDuration = 10 * 60 * 1000 // 10 minutos en ms

  /**
   * Obtiene el token CSRF de la cookie
   */
  const getCsrfToken = (): string | null => {
    if (process.server) return null

    const cookies = document.cookie.split(';')
    const xsrfCookie = cookies.find(c => c.trim().startsWith('XSRF-TOKEN='))

    if (!xsrfCookie) return null

    const token = xsrfCookie.split('=')[1]
    return decodeURIComponent(token)
  }

  /**
   * Solicita una nueva cookie CSRF al backend
   */
  const csrfCookie = async () => {
    const now = Date.now()
    if (now - csrfLastFetched > csrfCacheDuration) {
      await $fetch(`${baseURL.replace('/api', '')}/sanctum/csrf-cookie`, {
        method: 'GET',
        credentials: 'include',
      })
      csrfLastFetched = now
    }
  }

  /**
   * Obtiene los headers comunes incluyendo el token CSRF
   */
  const getHeaders = (): Record<string, string> => {
    const headers: Record<string, string> = {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    }

    // Agregar Referer para Sanctum
    if (!process.server && typeof window !== 'undefined') {
      headers['Referer'] = window.location.origin
    }

    const csrfToken = getCsrfToken()
    if (csrfToken) {
      headers['X-XSRF-TOKEN'] = csrfToken
    }

    return headers
  }

  const get = async <T>(endpoint: string, options: any = {}): Promise<T> => {
    return await $fetch<T>(`${baseURL}${endpoint}`, {
      method: 'GET',
      credentials: 'include',
      headers: getHeaders(),
      ...options
    })
  }

  const post = async <T>(endpoint: string, body: any, options: any = {}): Promise<T> => {
    await csrfCookie()
    return await $fetch<T>(`${baseURL}${endpoint}`, {
      method: 'POST',
      body,
      credentials: 'include',
      headers: getHeaders(),
      ...options
    })
  }

  const put = async <T>(endpoint: string, body: any, options: any = {}): Promise<T> => {
    await csrfCookie()
    return await $fetch<T>(`${baseURL}${endpoint}`, {
      method: 'PUT',
      body,
      credentials: 'include',
      headers: getHeaders(),
      ...options
    })
  }

  const del = async <T>(endpoint: string, options: any = {}): Promise<T> => {
    await csrfCookie()
    return await $fetch<T>(`${baseURL}${endpoint}`, {
      method: 'DELETE',
      credentials: 'include',
      headers: getHeaders(),
      ...options
    })
  }

  const patch = async <T>(endpoint: string, body: any, options: any = {}): Promise<T> => {
    await csrfCookie()
    return await $fetch<T>(`${baseURL}${endpoint}`, {
      method: 'PATCH',
      body,
      credentials: 'include',
      headers: getHeaders(),
      ...options
    })
  }

  return {
    get,
    post,
    put,
    delete: del,
    patch,
    csrfCookie
  }
}
