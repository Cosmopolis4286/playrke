import { createApp } from 'vue'

export default function mountApp(Component, props = {}) {
    const app = createApp(Component, props)

    // Usar route global definido en window.route, para no importar ziggy-js dos veces
    app.config.globalProperties.$route = window.route

    app.mount('#app')

    window._vueMounted = true
    if (typeof window.vueAppIsReady === 'function') {
        window.vueAppIsReady()
    }
}
