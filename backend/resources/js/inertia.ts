import '@/css/app.css'
import { createApp, h, type DefineComponent } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { createHead } from '@vueuse/head'
import { InertiaProgress } from '@inertiajs/progress'
import VueImaskPlugin from './plugins/vue-imask-plugin'
import FloatingVue from 'floating-vue'

import '@vueform/multiselect/themes/default.css'
import 'floating-vue/dist/style.css'

/**
 * Inertia Progress (navegación interna)
 */
InertiaProgress.init({
    delay: 150,
    color: '#0d6efd',
    includeCSS: true,
    showSpinner: false,
})

const pages = import.meta.glob<{ default: DefineComponent }>('/resources/js/Pages/**/*.vue')

createInertiaApp({
    resolve: async (name) => {
        const path = `/resources/js/Pages/${name}.vue`
        const pageImport = pages[path]

        if (!pageImport) {
            const errorPage = pages['/resources/js/Pages/Errors/Error.vue']
            if (!errorPage) throw new Error('[Inertia] Falta Pages/Errors/Error.vue')
            return (await errorPage()).default
        }

        const module = await pageImport()
        if (!module?.default) {
            throw new Error(`[Inertia] ${name} no exporta un componente por defecto`)
        }

        return module.default
    },

    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })

        app.use(plugin)
        app.use(createHead())
        app.use(VueImaskPlugin)
        app.use(FloatingVue)

        app.mount(el)
    },

    title: (title) => (title ? `PlayRKE - ${title}` : 'PlayRKE'),
})
