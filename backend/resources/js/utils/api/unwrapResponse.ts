import type { AxiosResponse } from 'axios'

/**
 * Extrae el objeto `data` dentro del payload `{ success, data }`.
 * Lanza error si `success` es false.
 *
 * @template T Tipo del dato esperado dentro de `data`
 * @param {AxiosResponse<{ success: boolean; data: T }>} response
 * @returns {T} Datos contenidos en `data`
 * @throws {Error} Si la respuesta indica `success: false`
 */
export function unwrapResponse<T>(response: AxiosResponse<{ success: boolean; data: T }>): T {
    if (!response.data.success) {
        throw new Error('La respuesta indica fallo en la operación')
    }
    return response.data.data
}
