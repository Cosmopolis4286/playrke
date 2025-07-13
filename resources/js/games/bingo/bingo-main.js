// resources/js/games/bingo/bingo-main.js
import { createApp } from 'vue'

export default function mountApp(Component, props = {}) {
    const app = createApp(Component, props)
    app.mount('#app')

    // Notificar al layout Blade que Vue está montado
    window._vueMounted = true
    if (typeof window.vueAppIsReady === 'function') {
        window.vueAppIsReady()
    }
}
