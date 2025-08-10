// ======================================================
// Archivo: resources/js/routes/infoRoutes.ts
// Descripción: Rutas del módulo infoRoutes
// Fecha de actualización (GMT): 2025-08-10T04:48:16Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

export const infoRoutes = {
    "terms": "/info/terms",
    "about": "/info/about",
    "privacy": "/info/privacy",
    "help-center": "/info/help-center",
    "power-of-knowledge": "/info/power-of-knowledge"
} as const;

/**
 * Obtiene la URL para la ruta dada con parámetros opcionales.
 * @param name Nombre de la ruta (clave del objeto)
 * @param params Parámetros para reemplazar en la URL (por ejemplo {id})
 * @returns URL con parámetros reemplazados
 */
export function route<K extends keyof typeof infoRoutes>(
    name: K,
    params?: Record<string, string | number>
): string {
    let url = infoRoutes[name] as string;

    if (params) {
        for (const [key, value] of Object.entries(params)) {
            url = url.replace(new RegExp('\{' + key + '\}', 'g'), String(value));
        }
    }

    return url;
}