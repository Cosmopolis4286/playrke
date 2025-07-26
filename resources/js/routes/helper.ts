// js/routes/helper.ts

type RoutesGroup = Record<string, string | ((...args: any[]) => string)>;

/**
 * Construye una URL a partir de un grupo de rutas.
 *
 * @param routes Grupo de rutas estáticas o dinámicas.
 * @param name Nombre clave de la ruta.
 * @param params Parámetros para rutas dinámicas.
 * @returns URL generada como string.
 * @throws Error si la ruta no existe en el grupo.
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

    return typeof route === 'function' ? route(...params) : route;
}
