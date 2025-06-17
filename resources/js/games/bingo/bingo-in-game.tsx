// resources/js/games/bingo/bingo-in-game.tsx

import React from 'react';
import { createRoot } from 'react-dom/client';
import BingoInGame from './components/BingoInfo';
import type { BingoProps } from './types/bingo-types';

const container = document.getElementById('bingo-in-game');

if (container) {
    try {
        const rawProps = container.dataset.props || '{}';
        const props: BingoProps = JSON.parse(rawProps);

        const root = createRoot(container);

        root.render(
            <React.StrictMode>
                <BingoInGame {...props} />
            </React.StrictMode>
        );
    } catch (error) {
        console.error('Error parsing props for BingoInGame:', error);
        container.innerHTML = '<p style="color:red;">Error cargando el juego. Intenta recargar la página.</p>';
    }
} else {
    console.warn('No se encontró el contenedor #bingo-in-game en el DOM.');
}
