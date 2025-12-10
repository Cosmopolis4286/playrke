// utils/parseLaravelError.ts
export function parseLaravelError(error: any): string {
    const response = error?.response?._data || error?.data || error

    if (!response) return 'Error desconocido'

    // 1. Mensaje principal directo
    if (response.message) return response.message

    // 2. Errores de validación (422)
    if (response.errors) {
        const firstKey = Object.keys(response.errors)[0]
        if (firstKey && Array.isArray(response.errors[firstKey])) {
            return response.errors[firstKey][0] // Primer error del primer campo
        }
    }

    // 3. Backend con excepción interna (Laravel debug)
    if (response.exception?.message) {
        return response.exception.message
    }

    // 4. Detectores según status
    const status = error?.response?.status

    switch (status) {
        case 401:
            return 'No autorizado'
        case 403:
            return 'Acceso denegado'
        case 404:
            return 'Recurso no encontrado'
        case 429:
            return 'Demasiados intentos. Inténtalo más tarde'
        case 500:
            return 'Error interno del servidor'
    }

    return 'Ocurrió un error inesperado'
}
