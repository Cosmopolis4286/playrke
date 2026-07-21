import '@/css/app.css'
import { createApp, defineComponent, h, type DefineComponent } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { createHead } from '@vueuse/head'
import VueImaskPlugin from './plugins/vue-imask-plugin'
import FloatingVue from 'floating-vue'
import Toast, { type PluginOptions, POSITION } from 'vue-toastification'
import { joinOnlinePresence } from '@/js/composables/realtime/useOnlinePresence'
import { joinLevelUpNotifications } from '@/js/composables/useLevelUpNotifications'
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
            // El componente pedido no está en el bundle (rename, casing distinto
            // al de disco, o un data-page obsoleto cacheado por el navegador tras
            // un deploy). Inertia monta el fallback con las props que el server
            // preparó para el componente ORIGINAL (name), que no incluyen
            // status/message/url — Error.vue las exige, así que se envuelve en
            // un componente propio que le inyecta esos defaults, o Vue lanza
            // "Missing required prop: status" y la página queda en blanco.
            console.error(`[Inertia] Componente "${name}" no encontrado en el bundle. Mostrando fallback de error.`)
            const errorPage = pages['/resources/js/Pages/Errors/Error.vue']
            if (!errorPage) throw new Error('[Inertia] Falta Pages/Errors/Error.vue')
            const ErrorComponent = (await errorPage()).default
            return defineComponent({
                name: 'InertiaMissingComponentFallback',
                render: () =>
                    h(ErrorComponent, {
                        status: 404,
                        message: `No se pudo cargar la página solicitada (${name}).`,
                        url: null,
                    }),
            })
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
        const authUser = (props.initialPage?.props as any)?.auth?.user
        if (authUser) {
            joinOnlinePresence()
            joinLevelUpNotifications(authUser.id)
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
