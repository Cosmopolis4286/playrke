import mountApp from '@js/utils/vueMain'
import type { Component } from 'vue'

declare global {
    interface Window {
        currentBingoView?: string
        bingoProps?: Record<string, unknown>
    }
}

type BingoViewName = 'Form' | 'GameInfo' | 'Summary'

const views: Record<BingoViewName, () => Promise<{ default: Component }>> = {
    Form:     () => import('@js/Components/games/bingo/Room/Form/BingoRoomForm.vue'),
    GameInfo: () => import('@js/Components/games/bingo/views/gameInfo/GameInfo.vue'),
    Summary:  () => import('@js/Components/games/bingo/views/summary/Summary.vue'),
}

const viewName = (typeof window.currentBingoView === 'string' ? window.currentBingoView : 'GameInfo') as BingoViewName

const props: Record<string, unknown> = (window.bingoProps && typeof window.bingoProps === 'object')
    ? window.bingoProps
    : {}

const loadView = views[viewName]

if (loadView) {
    loadView()
        .then(({ default: Component }) => {
            mountApp(Component, props)
        })
        .catch(() => {
            // Vista no disponible — fallo silencioso en producción
        })
}
