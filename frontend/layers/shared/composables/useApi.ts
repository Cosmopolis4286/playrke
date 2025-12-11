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

  const get = async <T>(endpoint: string, options: any = {}): Promise<T> => {
    return await $fetch<T>(`${baseURL}${endpoint}`, {
      method: 'GET',
      credentials: 'include',
      ...options
    })
  }

  const post = async <T>(endpoint: string, body: any, options: any = {}): Promise<T> => {
    await csrfCookie()
    return await $fetch<T>(`${baseURL}${endpoint}`, {
      method: 'POST',
      body,
      credentials: 'include',
      ...options
    })
  }

  const put = async <T>(endpoint: string, body: any, options: any = {}): Promise<T> => {
    await csrfCookie()
    return await $fetch<T>(`${baseURL}${endpoint}`, {
      method: 'PUT',
      body,
      credentials: 'include',
      ...options
    })
  }

  const del = async <T>(endpoint: string, options: any = {}): Promise<T> => {
    await csrfCookie()
    return await $fetch<T>(`${baseURL}${endpoint}`, {
      method: 'DELETE',
      credentials: 'include',
      ...options
    })
  }

  const patch = async <T>(endpoint: string, body: any, options: any = {}): Promise<T> => {
    await csrfCookie()
    return await $fetch<T>(`${baseURL}${endpoint}`, {
      method: 'PATCH',
      body,
      credentials: 'include',
      ...options
    })
  }

  return {
    get,
    post,
    put,
    delete: del,
    patch
  }
}
