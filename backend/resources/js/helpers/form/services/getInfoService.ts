import axios from '@js/plugins/axios'
import { formCrudState as crud } from '../state/formCrudState'
import { buildRoute, buildParamsForSinglePlaceholder } from '@js/routes/helper'
import { showToast } from '@js/utils/toastNotify'

/**
 * Obtiene información usando la ruta 'show' y el ID cifrado.
 * Detecta automáticamente el placeholder requerido en la ruta y construye la URL.
 *
 * @param encryptId ID cifrado que se usará como parámetro de la ruta.
 * @returns Promise<boolean> indica éxito o fallo.
 */
export async function getInfo(encryptId: string): Promise<boolean> {
    if (!encryptId) return false

    // Extraer placeholders de la ruta 'show'
    const extractPlaceholders = (route: string): string[] =>
        Array.from(route.matchAll(/{([^}]+)}/g)).map(match => match[1])

    const routePattern = crud.routes.show
    const placeholders = extractPlaceholders(routePattern)

    if (placeholders.length > 1) {
        throw new Error(`La ruta 'show' tiene múltiples placeholders, no soportado automáticamente.`)
    }

    // Construir parámetros solo si hay un placeholder
    const params = buildParamsForSinglePlaceholder(crud.routes, 'show', encryptId);

    // Construir URL con buildRoute, sin pasar params si no hay placeholders
    const url = buildRoute(crud.routes, 'show', params ?? {})

    try {
        const { data } = await axios.get(url)

        if (data?.success === false) {
            console.error('Error al cargar info:', data.message)
            showToast(data.message, 'error')
            throw new Error(data.message || 'Error desconocido al cargar info')
        }

        if (data?.data) {
            crud.insertInfo?.('edit', data.data)
            return true
        }

        console.warn('No hay datos disponibles para el ID:', encryptId)
    } catch (error: any) {
        console.error('Error en getInfo:', error.message || error)
        showToast('Error en getInfo!', 'error')
    }

    return false
}
