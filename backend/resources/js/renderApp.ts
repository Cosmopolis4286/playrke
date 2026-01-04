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
// Pages (TIPADO EXACTO) - Usando alias @ para compatibilidad prod
// ─────────────────────────────────────────────────────────────
const pages = import.meta.glob<{ default: DefineComponent }>(
    '@/Pages/**/*.vue'
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
     * ✔ Intenta múltiples formatos de ruta para dev y prod
     */
    resolve: async (name): Promise<DefineComponent> => {
        // Intentar ambos formatos de ruta (dev usa @/, prod usa /resources/js/)
        const possiblePaths = [
            `/resources/js/Pages/${name}.vue`, // Producción
            `@/Pages/${name}.vue`,              // Desarrollo
            `./Pages/${name}.vue`,              // Fallback
        ]

        for (const path of possiblePaths) {
            // Verificar cache primero
            if (resolveCache.has(path)) {
                console.log(`♻️ Usando cache para: ${path}`)
                return resolveCache.get(path)!
            }

            const loader = pages[path]

            if (loader) {
                console.log(`📄 Resolviendo página: ${path}`)
                const loadPromise = loader().then(module => module.default)
                resolveCache.set(path, loadPromise)
                return loadPromise
            }
        }

        // Si no se encuentra en ningún formato
        console.error(`[Inertia] Página no encontrada: ${name}`)
        console.error('Páginas disponibles:', Object.keys(pages))
        return PageNotFound
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
