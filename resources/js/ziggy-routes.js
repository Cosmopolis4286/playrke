import * as Ziggy from 'ziggy-js'

const isLocalhost = window.location.hostname === "localhost" 
|| window.location.hostname === "127.0.0.1";

const APP_URL =
  import.meta.env?.VITE_APP_URL ??
  window.APP_URL ??
  (isLocalhost
    ? "http://localhost/playrke/public"
    : `${window.location.origin}/playrke/public`);


let cachedZiggy = null

/**
 * Precarga y cachea las rutas Ziggy desde el backend.
 * Retorna el JSON con las rutas o lanza error si falla.
 */
export async function preloadZiggyRoutes() {
  if (!cachedZiggy) {
    const response = await fetch(`${APP_URL}/ziggy/routes`);
    if (!response.ok) throw new Error('No se pudieron cargar las rutas Ziggy');
    cachedZiggy = await response.json();
  }
  return cachedZiggy;
}

/**
 * Función síncrona para obtener URL de ruta Ziggy.
 * Debe llamarse después de preloadZiggyRoutes.
 * 
 * @param {string} name - Nombre de la ruta
 * @param {Object} params - Parámetros para la ruta
 * @param {boolean} absolute - Si la URL es absoluta
 * @returns {string} URL generada
 */
export async function route(name, params = {}, absolute = true) {
  if (!cachedZiggy) {
    await preloadZiggyRoutes();
  }
  return Ziggy.route(name, params, absolute, cachedZiggy);
}
