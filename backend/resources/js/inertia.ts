import '@/css/app.css'
import { createApp, h, type DefineComponent } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { createHead } from '@vueuse/head'
import VueImaskPlugin from './plugins/vue-imask-plugin'
import FloatingVue from 'floating-vue'
import Toast, { type PluginOptions, POSITION } from 'vue-toastification'
import { joinOnlinePresence } from '@/js/composables/realtime/useOnlinePresence'
import { hideAppLoader } from '@js/utils/loader/loaderApp'

import '@vueform/multiselect/themes/default.css'
import 'floating-vue/dist/style.css'
import 'vue-toastification/dist/index.css'

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

        // Presencia global: si hay usuario autenticado, se une una sola vez
        // por sesión de SPA al canal `online-users` (dashboards admin la consumen).
        if ((props.initialPage?.props as any)?.auth?.user) {
            joinOnlinePresence()
        }

        const toastOptions: PluginOptions = {
            position: POSITION.BOTTOM_RIGHT,
            timeout: 4000,
            closeOnClick: true,
            pauseOnHover: true,
            draggable: true,
            hideProgressBar: false,
            closeButton: 'button',
            icon: true,
            maxToasts: 5,
            transition: 'Vue-Toastification__bounce',
        }

        app.use(plugin)
        app.use(createHead())
        app.use(VueImaskPlugin)
        app.use(FloatingVue)
        app.use(Toast, toastOptions)

        app.mount(el)

        // El contenido real ya está montado — ocultar el loader ahora, sin
        // esperar a `window.load` (imágenes/recursos externos no relacionados).
        hideAppLoader()
    },

    title: (title) => (title ? `PlayRKE - ${title}` : 'PlayRKE'),
})
