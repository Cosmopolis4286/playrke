import { createApp } from 'vue'
import axios from 'axios'
// import BingoBoard from './components/game-info/BingoBoard.vue'
// import NumberCaller from './components/game-info/NumberCaller.vue'

export default function mountApp(Component, props = {}) {
    const app = createApp(Component, props)

    // Registrar componentes globales
    // app.component('BingoBoard', BingoBoard)
    // app.component('NumberCaller', NumberCaller)

    // Agregar axios a globalProperties para poder usarlo con this.$axios
    app.config.globalProperties.$axios = axios

    // También puedes agregar $route u otras cosas
    app.config.globalProperties.$route = window.route

    app.mount('#app')

    window._vueMounted = true
    if (typeof window.vueAppIsReady === 'function') {
        window.vueAppIsReady()
    }
}
