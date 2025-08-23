// js/bingo/utils/bingoViewLoader.ts
import mountApp from '@/helpers/vueMain.js';

const views = {
    Form: () => import('@/bingo/views/form/Form.vue'),
    GameInfo: () => import('@/bingo/views/game-info/GameInfo.vue'),
    Summary: () => import('@/bingo/views/summary/Summary.vue'),
};

// Obtener vista desde window (fallback: 'GameInfo')
const viewName = typeof window.currentBingoView === 'string' ? window.currentBingoView : 'GameInfo';

// Validar props
const props = (window.bingoProps && typeof window.bingoProps === 'object')
    ? window.bingoProps
    : {};

const loadView = views[viewName];

if (loadView) {
    loadView()
        .then(({ default: Component }) => {
            mountApp(Component, props);
        })
        .catch((error) => {
            console.error(`[Bingo] Error al cargar la vista "${viewName}":`, error);
        });
} else {
    console.error(`[Bingo] Vista "${viewName}" no está definida en el mapa de vistas.`);
}
