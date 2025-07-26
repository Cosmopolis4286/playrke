import './bootstrap'
import * as Ziggy from 'ziggy-js'

let cachedZiggy = null

// Precarga rutas al inicio y cachea
async function preloadZiggyRoutes() {
  if (!cachedZiggy) {
    const response = await fetch('/ziggy/routes')
    if (!response.ok) throw new Error('No se pudieron cargar las rutas Ziggy')
    cachedZiggy = await response.json()
  }
  return cachedZiggy
}

// Función SÍNCRONA para obtener URL — solo se puede usar después de precarga
function route(name, params = {}, absolute = true) {
  if (!cachedZiggy) {
    throw new Error('Rutas Ziggy no cargadas. Llama a preloadZiggyRoutes() antes.')
  }
  return Ziggy.route(name, params, absolute, cachedZiggy)
}

export { preloadZiggyRoutes, route }
