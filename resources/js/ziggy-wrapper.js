import * as Ziggy from 'ziggy-js';

const isLocalhost =
  window.location.hostname === 'localhost' ||
  window.location.hostname === '127.0.0.1';

const APP_URL =
  import.meta.env?.VITE_APP_URL ??
  window.APP_URL ??
  (isLocalhost
    ? 'http://localhost/playrke/public'
    : `${window.location.origin}/playrke/public`);


let cachedZiggy = null;      // Aquí guardamos el resultado ya obtenido (las rutas Ziggy)
let preloadPromise = null;   // Aquí guardamos la promesa activa de la petición en curso

/**
 * Precarga y cachea las rutas Ziggy desde el backend.
 * Debe llamarse al iniciar la app para luego usar route síncrono.
 */
export function preloadZiggyRoutes() {
  // Si ya tenemos las rutas cargadas, retornamos una promesa resuelta con ese valor (cache)
  if (cachedZiggy) return Promise.resolve(cachedZiggy);

  // Si no hay una petición activa, creamos una
  if (!preloadPromise) {
    // Función para esperar un tiempo dado (en ms)
    const delay = ms => new Promise(resolve => setTimeout(resolve, ms));

    // Función que hace la petición fetch para obtener rutas
    const fetchRoutes = () =>
      fetch(`${APP_URL}/ziggy/routes`)
        .then(res => {
          // Validamos que la respuesta sea correcta
          if (!res.ok) throw new Error('No se pudieron cargar las rutas Ziggy');
          return res.json(); // Parseamos la respuesta JSON
        });

    // Intento inicial + reintento en caso de error con delay
    preloadPromise = fetchRoutes()
      .catch(async () => {
        console.warn('Primer intento falló, reintentando en 500ms...');
        await delay(500);
        return fetchRoutes();
      })
      .then(data => {
        cachedZiggy = data; // Guardamos el resultado en cache para futuras llamadas
        return cachedZiggy; // Retornamos el resultado
      })
      .catch(err => {
        preloadPromise = null; // Si hubo error, limpiamos la promesa activa para permitir reintentos
        throw err; // Propagamos el error para manejarlo fuera
      });
  }

  return preloadPromise;
}

/**
 * Devuelve la URL generada para la ruta Ziggy.
 * Debe llamarse después de preloadZiggyRoutes.
 *
 * @param {string} name Nombre de la ruta
 * @param {object} params Parámetros de la ruta
 * @param {boolean} absolute URL absoluta o relativa
 * @returns {string} URL generada
 */
export function route(name, params = {}, absolute = true) {
  if (!cachedZiggy) {
    throw new Error('Rutas Ziggy no cargadas. Llama a preloadZiggyRoutes primero.');
  }
  return Ziggy.route(name, params, absolute, cachedZiggy);
}
