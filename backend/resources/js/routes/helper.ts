// resources/js/routes/helper.ts

/**
 * Tipo que representa un grupo de rutas.
 * Puede contener rutas estáticas (string) o rutas dinámicas (función que devuelve string).
 */
type RoutesGroup = Record<string, string | ((...args: any[]) => string)>;

/**
 * URL base tomada de las variables de entorno (.env).
 * Ejemplo en .env local:
 *   VITE_APP_URL=http://localhost/playrke/public
 * Ejemplo en producción:
 *   VITE_APP_URL=https://tusitio.com
 *
 * Si no se define, se usará `window.location.origin` como fallback.
 */
const BASE_URL = import.meta.env.VITE_APP_URL?.replace(/\/+$/, '') || window.location.origin;

/**
 * Reemplaza los placeholders {clave} en una URL por los valores proporcionados.
 *
 * @param url URL con placeholders.
 * @param params Objeto de parámetros para reemplazar.
 * @returns URL con placeholders reemplazados.
 */
function replacePlaceholders(url: string, params?: Record<string, string | number>): string {
    if (!params) return url;
    for (const [key, value] of Object.entries(params)) {
        url = url.replace(
            new RegExp(`{${key}}`, 'g'),
            encodeURIComponent(String(value))
        );
    }
    return url;
}

/**
 * Construye una URL a partir de un grupo de rutas tipadas.
 * Soporta rutas definidas como strings con placeholders o funciones que generen strings.
 *
 * @param routes Objeto con rutas (clave → string o función).
 * @param name Clave de la ruta a obtener.
 * @param params Parámetros para la ruta (objeto si es string, argumentos si es función).
 * @returns URL completa (con dominio y path base).
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

    let path: string;
    if (typeof route === 'function') {
        // Si la ruta es función, llamamos con los parámetros
        path = route(...params);
    } else {
        // Si la ruta es string, reemplazamos placeholders usando el primer parámetro como objeto
        const paramsObj = params[0] as Record<string, string | number> | undefined;
        path = replacePlaceholders(route, paramsObj);
    }

    return `${BASE_URL}${path}`;
}

/**
 * Obtiene la URL para una ruta simple (solo rutas tipo string).
 * Ideal para módulos generados automáticamente.
 *
 * @param routes Objeto de rutas (clave → URL con o sin placeholders).
 * @param name Clave de la ruta.
 * @param params Objeto con parámetros para reemplazar en la URL.
 * @returns URL completa (con dominio y path base).
 */
export function route<T extends Record<string, string>>(
    routes: T,
    name: keyof T,
    params?: Record<string, string | number>
): string {
    const path = replacePlaceholders(routes[name] as string, params);
    return `${BASE_URL}${path}`;
}
