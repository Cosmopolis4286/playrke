// ======================================================
// Archivo: resources/js/routes/pixRoutes.ts
// Descripción: Rutas del módulo pixRoutes
// Fecha de actualización (GMT): 2025-08-10T04:48:16Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

export const pixRoutes = {
    "index": "/pix",
    "create": "/pix/create",
    "update": "/pix/update/{id}",
    "delete": "/pix/delete/{id}",
    "getInfo": "/pix/getInfo/{id}",
    "getBanks": "/pix/get/banks"
} as const;

/**
 * Obtiene la URL para la ruta dada con parámetros opcionales.
 * @param name Nombre de la ruta (clave del objeto)
 * @param params Parámetros para reemplazar en la URL (por ejemplo {id})
 * @returns URL con parámetros reemplazados
 */
export function route<K extends keyof typeof pixRoutes>(
    name: K,
    params?: Record<string, string | number>
): string {
    let url = pixRoutes[name] as string;

    if (params) {
        for (const [key, value] of Object.entries(params)) {
            url = url.replace(new RegExp('\{' + key + '\}', 'g'), String(value));
        }
    }

    return url;
}