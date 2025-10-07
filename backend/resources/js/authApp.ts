/**
 * authApp.ts
 *
 * Entrada principal de Vue 3 + Inertia.js para páginas de autenticación.
 * Funciona con Vite y TypeScript, usando lazy-loading de componentes.
 * Incluye:
 *   - Barra de progreso Inertia
 *   - Resolución segura de rutas (Windows/Linux)
 *   - Tipado correcto para TypeScript
 *   - Manejo de errores con fallback
 */

import { createApp, h, defineComponent, type DefineComponent } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { InertiaProgress } from '@inertiajs/progress'

// Inicializa barra de progreso
InertiaProgress.init()

// Import dinámico de todas las páginas bajo ./Pages
const pages = import.meta.glob<{ default: DefineComponent }>('./Pages/**/*.vue')

// Fallback para páginas no encontradas
const PageNotFound: DefineComponent = defineComponent({
  name: 'PageNotFound',
  setup() {
    return () =>
      h(
        'div',
        { style: { padding: '2rem', color: 'red', fontWeight: 'bold' } },
        'Página no encontrada'
      )
  },
})

createInertiaApp({
  /**
   * Resolver páginas dinámicamente
   */
  resolve: async (name: string): Promise<DefineComponent> => {
    const keys = Object.keys(pages)
    const matchedKey = keys.find(key => key.endsWith(`${name}.vue`))

    if (!matchedKey) {
      console.error(`[Inertia] Página no encontrada: ${name}`)
      return PageNotFound
    }

    const module = await pages[matchedKey]()
    return module.default
  },

  /**
   * Setup de la app Vue
   */
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) }).use(plugin).mount(el)
  },

  /**
   * Título dinámico
   */
  title: title => (title ? `PlayRKE - ${title}` : 'PlayRKE'),
})
