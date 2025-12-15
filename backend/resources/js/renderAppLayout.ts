import { createApp, h, defineComponent, type DefineComponent, type Component } from 'vue'
import { createInertiaApp, usePage } from '@inertiajs/vue3'
import { InertiaProgress } from '@inertiajs/progress'

// Layouts Vue
import AuthLayout from './Layouts/AuthLayout.vue'
import DefaultLayout from './Layouts/DefaultLayout.vue'

/**
 * Inicializa la barra de progreso de Inertia
 */
InertiaProgress.init({
    color: '#8b5cf6',
    showSpinner: false,
})

/**
 * Importación dinámica de todas las páginas Vue
 */
const pages = import.meta.glob<{ default: Component }>(
  './Pages/**/*.vue'
)


/**
 * Página 404 fallback
 */
const PageNotFound: Component = defineComponent({
  name: 'PageNotFound',
  setup() {
    return () =>
      h('div', { style: { padding: '2rem', color: '#ef4444' } }, 'Página no encontrada')
  },
})


/**
 * Bootstrap de la aplicación Inertia + Vue
 */
createInertiaApp({
    /**
     * Resuelve dinámicamente las páginas
     */
 resolve: async (name): Promise<Component> => {
    const path = `./Pages/${name}.vue`
    const loader = pages[path]

    if (!loader) {
      console.error(`[Inertia] Página no encontrada: ${name}`)
      return PageNotFound
    }

    const module = await loader()
    return module.default
  },

    /**
     * Setup principal de Vue
     */
    setup({ el, App, props, plugin }) {
        /**
         * Wrapper que decide el layout en runtime
         */
        const LayoutWrapper = defineComponent({
            name: 'LayoutWrapper',

            setup() {
                const page = usePage<{ layout?: string }>()

                return () => {
                    const layout = page.props?.layout ?? 'default'
                    const LayoutComponent =
                        layout === 'auth' ? AuthLayout : DefaultLayout

                    return h(LayoutComponent, null, {
                        default: () => h(App, props),
                    })
                }
            },
        })


        createApp(LayoutWrapper)
            .use(plugin)
            .mount("app-inertia")
    },

    /**
     * Título dinámico del documento
     */
    title: (title) => (title ? `PlayRKE - ${title}` : 'PlayRKE'),
})
