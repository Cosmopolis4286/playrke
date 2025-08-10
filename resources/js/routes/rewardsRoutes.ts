// ======================================================
// Archivo: resources/js/routes/rewardsRoutes.ts
// Descripción: Rutas del módulo rewardsRoutes
// Fecha de actualización (GMT): 2025-08-10T04:48:16Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

export const rewardsRoutes = {
    "index": "/rewards",
    "create": "/rewards/create",
    "getInfo": "/rewards/getInfo/{id}",
    "update": "/rewards/update/{id}",
    "delete": "/rewards/delete/{id}",
    "changeStatus": "/rewards/changeStatus/{id}"
} as const;

/**
 * Obtiene la URL para la ruta dada con parámetros opcionales.
 * @param name Nombre de la ruta (clave del objeto)
 * @param params Parámetros para reemplazar en la URL (por ejemplo {id})
 * @returns URL con parámetros reemplazados
 */
export function route<K extends keyof typeof rewardsRoutes>(
    name: K,
    params?: Record<string, string | number>
): string {
    let url = rewardsRoutes[name] as string;

    if (params) {
        for (const [key, value] of Object.entries(params)) {
            url = url.replace(new RegExp('\{' + key + '\}', 'g'), String(value));
        }
    }

    return url;
}