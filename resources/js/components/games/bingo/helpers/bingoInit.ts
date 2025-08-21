import { resizeChips, renderChipsView } from './chips';
import { initSound } from './sound';
import { debounce } from './utils';

const preRoute = '/'; // ajustar según tu proyecto

window.addEventListener('load', () => {
    initSound();
    resizeChips();

    window.addEventListener('resize', debounce(() => {
        resizeChips();
        renderChipsView(preRoute);
    }, 200));

    // Eventos de flechas
    document.getElementById('cont-left-arrow')?.addEventListener('click', () => renderChipsView(preRoute, 'L'));
    document.getElementById('cont-right-arrow')?.addEventListener('click', () => renderChipsView(preRoute, 'R'));
});
