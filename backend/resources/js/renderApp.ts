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

// Cache para prevenir resoluciones duplicadas
const resolveCache = new Map<string, Promise<DefineComponent>>()

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
if (import.meta.env.DEV) {
    console.log('🔵 Inicializando Inertia App')
}

createInertiaApp({
    /**
     * Resolver dinámico de páginas
     * ✔ async
     * ✔ siempre retorna DefineComponent
     */
    resolve: async (name): Promise<DefineComponent> => {
        const path = `./Pages/${name}.vue`

        // Verificar si ya estamos resolviendo esta página
        if (resolveCache.has(path)) {
            console.log(`♻️ Usando cache para: ${path}`)
            return resolveCache.get(path)!
        }

        console.log(`📄 Resolviendo página (primera vez): ${path}`)

        const loader = pages[path]

        if (!loader) {
            console.error(`[Inertia] Página no encontrada: ${name}`)
            return PageNotFound
        }

        // Cachear la promesa
        const loadPromise = loader().then(module => module.default)
        resolveCache.set(path, loadPromise)

        return loadPromise
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
