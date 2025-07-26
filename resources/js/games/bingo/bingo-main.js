// resources/js/bingo-main.js
import { createApp } from 'vue'
import axios from 'axios'

export default function mountApp(Component, props = {}) {
    // Crear instancia local de Axios
    const localAxios = axios.create()

    // Agregar token CSRF desde el <meta>
    const token = document.querySelector('meta[name="csrf-token"]')
    if (token) {
        localAxios.defaults.headers.common['X-CSRF-TOKEN'] = token.content
    } else {
        console.warn('⚠️ CSRF token no encontrado. Asegúrate de tener <meta name="csrf-token" content="{{ csrf_token() }}"> en el layout.')
    }

    // Crear instancia de Vue
    const app = createApp(Component, props)

    // Agregar Axios como propiedad global SOLO para esta app Vue
    app.config.globalProperties.$axios = localAxios

    // // Agregar route de Ziggy (si window.route ya existe por @routes o ziggy-js importado)
    if (typeof window.route === 'function') {
        app.config.globalProperties.$route = window.route
    }

    app.mount('#app')

    // Flags opcionales
    window._vueMounted = true
    if (typeof window.vueAppIsReady === 'function') {
        window.vueAppIsReady()
    }
}
