/**
 * authApp.ts
 *
 * Entrada principal de Vue 3 + Inertia.js para páginas de autenticación.
 * Funciona con Vite y TypeScript, usando lazy-loading de componentes.
 */

import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'

type VueComponent = any

// 🔹 Import dinámico de todas las páginas bajo ./Pages (lazy-loading)
const pages = import.meta.glob('./Pages/**/*.vue') // glob de Vite


createInertiaApp({
    /**
     * Resolve: recibe el nombre de la página solicitado por Inertia
     * Ejemplo: 'Auth/Login' -> './Pages/Auth/Login.vue'
     */
    resolve: (name: string) => {
        const path = `./Pages/${name}.vue`
        const page = pages[path]

        if (!page) {
            throw new Error(`Página no encontrada: ${path}`)
        }

        // Retorna la promesa que carga el componente de manera diferida
        // Tipamos el módulo como { default: VueComponent }
        return page().then((module: { default: VueComponent }) => module.default)
    },

    /**
     * Setup: crea la instancia de Vue
     * @param el - Elemento donde se montará la app
     * @param App - Componente raíz de Inertia
     * @param props - Props que Inertia envía a la página
     * @param plugin - Plugin de Inertia para Vue
     */
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el)
    },

    /**
     * Opcional: título dinámico
     * Cada página puede definir props.title para modificar el document.title
     */
    title: title => (title ? `PlayRKE - ${title}` : 'PlayRKE'),
})
