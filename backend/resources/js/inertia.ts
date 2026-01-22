import { createApp, h, type DefineComponent, Directive } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { createHead } from '@vueuse/head'
import NProgress from 'nprogress'
import 'nprogress/nprogress.css'
import { Inertia } from '@inertiajs/inertia'
import VueImaskPlugin from './plugins/vue-imask-plugin'

/**
 * Importa de forma dinámica todos los componentes Vue en /resources/js/Pages.
 */
const pages = import.meta.glob<{ default: DefineComponent }>('/resources/js/Pages/**/*.vue')

createInertiaApp({
    resolve: async (name): Promise<DefineComponent> => {
        const path = `/resources/js/Pages/${name}.vue`
        const pageImport = pages[path]

        if (!pageImport) {
            const notFound = pages['/resources/js/Pages/Errors/404.vue']
            if (!notFound) throw new Error('[Inertia] Falta Pages/Errors/404.vue')
            return (await notFound()).default
        }

        const module = await pageImport()
        if (!module?.default) throw new Error(`[Inertia] ${name} no exporta un componente por defecto`)
        return module.default
    },

    /**
     * Setup principal donde se crea la app Vue y se registra la directiva y plugins.
     */
    setup({ el, App, props, plugin }) {
        const head = createHead()
        const app = createApp({ render: () => h(App, props) })

        app.use(VueImaskPlugin) // Registro de la directiva v-mask

        app.use(plugin)
        app.use(head)
        app.mount(el)

        // Configuración de NProgress para indicar carga en navegación Inertia
        Inertia.on('start', () => NProgress.start())
        Inertia.on('finish', () => NProgress.done())
        Inertia.on('error', () => NProgress.done())
    },

    /**
     * Formato del título dinámico de la página.
     */
    title: (title) => (title ? `PlayRKE - ${title}` : 'PlayRKE'),
})
