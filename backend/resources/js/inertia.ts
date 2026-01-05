import { createApp, h, type DefineComponent } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { InertiaProgress } from '@inertiajs/progress'
import { createHead } from '@vueuse/head'

// ─────────────────────────────────────────────────────────────
// Inertia Progress
// ─────────────────────────────────────────────────────────────
InertiaProgress.init({
    color: '#8b5cf6',
    showSpinner: false,
})

// ─────────────────────────────────────────────────────────────
// Pages (glob estático, Vite cachea automáticamente)
// ─────────────────────────────────────────────────────────────
const pages = import.meta.glob<{ default: DefineComponent }>(
    '@/Pages/**/*.vue'
)

// ─────────────────────────────────────────────────────────────
// Inertia App
// ─────────────────────────────────────────────────────────────
if (import.meta.env.DEV) {
    console.info('🔵 Inicializando Inertia App')
}

createInertiaApp({
    /**
     * Resolver de páginas Inertia
     * - Determinístico (sin Object.keys / find)
     * - Compatible dev + build
     * - Falla explícitamente si la página no existe
     */
    resolve: async (name): Promise<DefineComponent> => {
        const pageImport = pages[`/resources/js/Pages/${name}.vue`]

        if (!pageImport) {
            console.error(`[Inertia] Página no encontrada: ${name}`)

            const notFound = pages['/resources/js/Pages/errors/404.vue']

            if (!notFound) {
                throw new Error(
                    '[Inertia] Falta Pages/errors/404.vue'
                )
            }

            return (await notFound()).default
        }

        const module = await pageImport()

        if (!module?.default) {
            throw new Error(
                `[Inertia] ${name} no exporta un componente por defecto`
            )
        }

        return module.default
    },

    /**
     * Bootstrap Vue
     */
    setup({ el, App, props, plugin }) {
        const head = createHead()

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(head)
            .mount(el)
    },

    /**
     * Título dinámico
     */
    title: title => (title ? `PlayRKE - ${title}` : 'PlayRKE'),
})
