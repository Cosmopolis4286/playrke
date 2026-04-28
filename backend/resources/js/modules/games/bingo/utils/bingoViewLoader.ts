import mountApp from '@js/utils/vueMain'
import type { Component } from 'vue'

// Definir tipo de vistas
type BingoViewName = 'Form' | 'GameInfo' | 'Summary'

const views: Record<BingoViewName, () => Promise<{ default: Component }>> = {
    Form: () => import('@js/Components/Games/Bingo/Room/Form/BingoRoomForm.vue'),
    GameInfo: () => import('@js/components/games/bingo/views/gameInfo/GameInfo.vue'),
    Summary: () => import('@js/components/games/bingo/views/summary/Summary.vue'),
}

// Obtener vista desde window (fallback: 'GameInfo')
const viewName = (typeof window.currentBingoView === 'string' ? window.currentBingoView : 'GameInfo') as BingoViewName

// Validar props
const props: Record<string, any> = (window.bingoProps && typeof window.bingoProps === 'object')
    ? window.bingoProps
    : {}

const loadView = views[viewName]

if (loadView) {
    loadView()
        .then(({ default: Component }) => {
            mountApp(Component, props)
        })
        .catch((error) => {
            console.error(`[Bingo] Error al cargar la vista "${viewName}":`, error)
        })
} else {
    console.error(`[Bingo] Vista "${viewName}" no está definida en el mapa de vistas.`)
}
