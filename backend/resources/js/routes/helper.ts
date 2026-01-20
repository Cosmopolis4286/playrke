type RoutesGroup = Record<string, string | ((...args: any[]) => string)>;

const BASE_URL = import.meta.env.VITE_APP_URL?.replace(/\/+$/, '') || window.location.origin;

/**
 * Reemplaza los placeholders en formato {clave} dentro de una URL por los valores indicados.
 *
 * @param url URL que contiene placeholders.
 * @param params Objeto con claves y valores para reemplazar en la URL.
 * @returns URL con los placeholders reemplazados por valores codificados.
 */
function replacePlaceholders(url: string, params?: Record<string, string | number>): string {
    if (!params) return url;
    return Array.from(url.matchAll(/{([^}]+)}/g)).reduce((acc, match) => {
        const key = match[1];
        if (params[key] !== undefined) {
            return acc.replace(new RegExp(`{${key}}`, 'g'), encodeURIComponent(String(params[key])));
        }
        return acc;
    }, url);
}

/**
 * Construye una URL completa a partir de un conjunto de rutas tipadas,
 * ya sea rutas definidas como strings con placeholders o funciones que generan strings.
 *
 * Para rutas string, se puede pasar un objeto con parámetros para reemplazar los placeholders.
 * Para rutas función, se pasan los argumentos que recibe la función.
 *
 * @template T Tipo del objeto de rutas.
 * @template K Clave de la ruta dentro del objeto.
 *
 * @param routes Objeto que contiene las rutas, pueden ser strings o funciones.
 * @param name Nombre de la ruta a construir.
 * @param args Parámetros para la ruta:
 *   - Si la ruta es función, los argumentos de la función.
 *   - Si la ruta es string, un único objeto opcional con los valores para los placeholders.
 *
 * @returns URL completa con la base configurada y la ruta interpolada.
 *
 * @throws Error si la ruta no existe en el objeto.
 *
 * @example
 * ```ts
 * // Ruta string con placeholder:
 * buildRoute(adminRoutes, 'commerceShow', { commerce: 123 });
 *
 * // Ruta función con argumentos:
 * buildRoute(customRoutes, 'userProfile', 45);
 *
 * // Ruta string sin parámetros:
 * buildRoute(adminRoutes, 'dashboard');
 * ```
 */
export function buildRoute<
    T extends RoutesGroup,
    K extends keyof T
>(
    routes: T,
    name: K,
    ...args: T[K] extends (...args: infer P) => string ? P : [Record<string, string | number>?]
): string {
    const route = routes[name];
    if (!route) throw new Error(`Ruta no definida: ${String(name)}`);

    if (typeof route === 'function') {
        return `${BASE_URL}${route(...(args as any))}`;
    } else {
        const params = args[0] as Record<string, string | number> | undefined;
        return `${BASE_URL}${replacePlaceholders(route, params)}`;
    }
}

/**
 * Dada una ruta con placeholders, construye el objeto params para un valor único.
 * Si hay más de un placeholder lanza error.
 *
 * @param routes Objeto de rutas string con placeholders.
 * @param routeName Nombre de la ruta a usar.
 * @param paramValue Valor para el placeholder único.
 * @returns Objeto params con clave del placeholder y valor dado.
 * @throws Error si la ruta tiene más de un placeholder.
 */
export function buildParamsForSinglePlaceholder(
    routes: Record<string, string>,
    routeName: string,
    paramValue: string | number
): Record<string, string | number> | undefined {
    const extractPlaceholders = (route: string): string[] =>
        Array.from(route.matchAll(/{([^}]+)}/g)).map(match => match[1]);

    const routePattern = routes[routeName];
    if (!routePattern) throw new Error(`Ruta '${routeName}' no encontrada.`);

    const placeholders = extractPlaceholders(routePattern);

    if (placeholders.length > 1) {
        throw new Error(
            `La ruta '${routeName}' tiene múltiples placeholders, no soportado automáticamente.`
        );
    }

    if (placeholders.length === 0) return undefined;

    return { [placeholders[0]]: paramValue };
}
