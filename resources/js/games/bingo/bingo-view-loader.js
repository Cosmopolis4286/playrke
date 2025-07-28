import mountApp from './bingo-main.js';

const views = {
    Form: () => import('./views/Form.vue'),
    GameInfo: () => import('./views/GameInfo.vue'),
    Summary: () => import('./views/Summary.vue'),
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
