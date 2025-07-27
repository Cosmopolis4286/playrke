import mountApp from './bingo-main.js';

const view = window.currentBingoView || 'GameInfo';
const props = (window.bingoProps && typeof window.bingoProps === 'object') ? window.bingoProps : {};

const views = {
    Form: () => import('./views/Form.vue'),
    GameInfo: () => import('./views/GameInfo.vue'),
    Summary: () => import('./views/Summary.vue'),
};

if (views[view]) {
    views[view]()
        .then(module => {
            mountApp(module.default, props);
        })
        .catch(err => {
            console.error(`Error cargando la vista "${view}":`, err);
        });
} else {
    console.error(`Vista "${view}" no encontrada.`);
}
