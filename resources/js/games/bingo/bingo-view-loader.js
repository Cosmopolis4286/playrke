// resources/js/games/bingo/bingo-view-loader.js
import mountApp from './bingo-main.js'  // función que crea la app Vue y monta el componente

const view = window.currentBingoView || 'GameInfo'
const props = window.bingoProps || {}

const views = {
    GameInfo: () => import('./views/GameInfo.vue'),
    Summary: () => import('./views/Summary.vue'),
    // agrega más vistas aquí según crezcan tus componentes
}

if (views[view]) {
    views[view]()
        .then(module => {
            mountApp(module.default, props)
        })
        .catch(err => {
            console.error(`Error cargando la vista "${view}":`, err)
        })
} else {
    console.error(`Vista "${view}" no encontrada.`)
}
