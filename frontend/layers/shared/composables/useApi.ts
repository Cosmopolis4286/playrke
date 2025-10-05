/**
 * Composable base para llamadas API
 *
 * Proporciona funciones helper para hacer llamadas HTTP
 * a la API de Laravel con configuración consistente.
 */

export const useApi = () => {
  const config = useRuntimeConfig()
  const baseURL = config.public.apiBase

  /**
   * Hace una petición GET a la API
   */
  const get = async <T>(endpoint: string, options: any = {}): Promise<T> => {
    return await $fetch<T>(`${baseURL}${endpoint}`, {
      method: 'GET',
      credentials: 'include',
      ...options
    })
  }

  /**
   * Hace una petición POST a la API
   */
  const post = async <T>(endpoint: string, body: any, options: any = {}): Promise<T> => {
    return await $fetch<T>(`${baseURL}${endpoint}`, {
      method: 'POST',
      body,
      credentials: 'include',
      ...options
    })
  }

  /**
   * Hace una petición PUT a la API
   */
  const put = async <T>(endpoint: string, body: any, options: any = {}): Promise<T> => {
    return await $fetch<T>(`${baseURL}${endpoint}`, {
      method: 'PUT',
      body,
      credentials: 'include',
      ...options
    })
  }

  /**
   * Hace una petición DELETE a la API
   */
  const del = async <T>(endpoint: string, options: any = {}): Promise<T> => {
    return await $fetch<T>(`${baseURL}${endpoint}`, {
      method: 'DELETE',
      credentials: 'include',
      ...options
    })
  }

  /**
   * Hace una petición PATCH a la API
   */
  const patch = async <T>(endpoint: string, body: any, options: any = {}): Promise<T> => {
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
