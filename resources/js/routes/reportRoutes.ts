// ======================================================
// Archivo: resources/js/routes/reportRoutes.ts
// Descripción: Rutas del módulo reportRoutes
// Fecha de actualización (GMT): 2025-08-10T04:48:16Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

export const reportRoutes = {
    "index": "/report",
    "store": "/report/store"
} as const;

/**
 * Obtiene la URL para la ruta dada con parámetros opcionales.
 * @param name Nombre de la ruta (clave del objeto)
 * @param params Parámetros para reemplazar en la URL (por ejemplo {id})
 * @returns URL con parámetros reemplazados
 */
export function route<K extends keyof typeof reportRoutes>(
    name: K,
    params?: Record<string, string | number>
): string {
    let url = reportRoutes[name] as string;

    if (params) {
        for (const [key, value] of Object.entries(params)) {
            url = url.replace(new RegExp('\{' + key + '\}', 'g'), String(value));
        }
    }

    return url;
}