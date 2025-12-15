import { createApp, h, defineComponent, type DefineComponent } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { InertiaProgress } from '@inertiajs/progress'
import { createHead } from '@vueuse/head' // Importar createHead

// ─────────────────────────────────────────────────────────────
// Inertia Progress
// ─────────────────────────────────────────────────────────────
InertiaProgress.init({
    color: '#8b5cf6',
    showSpinner: false,
})

// ─────────────────────────────────────────────────────────────
// Pages (TIPADO EXACTO)
// ─────────────────────────────────────────────────────────────
const pages = import.meta.glob<{ default: DefineComponent }>(
    './Pages/**/*.vue'
)

// ─────────────────────────────────────────────────────────────
// Page Not Found (TIPO FIJO)
// ─────────────────────────────────────────────────────────────
const PageNotFound: DefineComponent = defineComponent({
    name: 'PageNotFound',
    setup() {
        return () =>
            h(
                'div',
                {
                    style: {
                        padding: '2rem',
                        color: '#ef4444',
                        fontWeight: 'bold',
                    },
                },
                'Página no encontrada'
            )
    },
})

// ─────────────────────────────────────────────────────────────
// Inertia App
// ─────────────────────────────────────────────────────────────
createInertiaApp({
    /**
     * Resolver dinámico de páginas
     * ✔ async
     * ✔ siempre retorna DefineComponent
     */
    resolve: async (name): Promise<DefineComponent> => {
        const path = `./Pages/${name}.vue`
        const loader = pages[path]

        console.log(path)
        if (!loader) {
            console.error(`[Inertia] Página no encontrada: ${name}`)
            return PageNotFound
        }

        const module = await loader()
        return module.default
    },

    /**
     * Bootstrap Vue
     */
    setup({ el, App, props, plugin }) {
        const head = createHead() // Crear la instancia de createHead
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(head) // Usar createHead como plugin de Vue
            .mount(el)
    },

    /**
     * Título dinámico
     */
    title: (title) => (title ? `PlayRKE - ${title}` : 'PlayRKE'),
})
