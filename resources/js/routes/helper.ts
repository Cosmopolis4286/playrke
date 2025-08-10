// resources/js/routes/helper.ts

type RoutesGroup = Record<string, string | ((...args: any[]) => string)>;

/**
 * Construye una URL a partir de un grupo de rutas.
 *
 * @param routes Grupo de rutas estáticas o dinámicas (funciones).
 * @param name Clave de la ruta a obtener.
 * @param params Parámetros para rutas dinámicas (funciones).
 * @returns URL construida.
 * @throws Error si la ruta no existe.
 */
export function buildRoute<
    T extends RoutesGroup,
    K extends keyof T
>(
    routes: T,
    name: K,
    ...params: T[K] extends (...args: infer P) => string ? P : []
): string {
    const route = routes[name];
    if (!route) throw new Error(`Ruta no definida: ${String(name)}`);

    if (typeof route === 'function') {
        // Ruta es función: se llama con parámetros
        return route(...params);
    }

    // Ruta es string: reemplazar placeholders {param}
    let url = route as string;
    if (params.length > 0) {
        // Suponemos que los parámetros se pasan como objeto si hay más de 1, o uno solo
        const paramsObj = params[0] as Record<string, string | number> | undefined;

        if (paramsObj && typeof paramsObj === 'object') {
            for (const [key, value] of Object.entries(paramsObj)) {
                url = url.replace(new RegExp(`{${key}}`, 'g'), encodeURIComponent(String(value)));
            }
        }
    }

    return url;
}
