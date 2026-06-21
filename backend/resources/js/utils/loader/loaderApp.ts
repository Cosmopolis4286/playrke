/**
 * Controla la visibilidad del loader global.
 * - El loader se muestra por defecto
 * - Se oculta cuando todos los recursos terminaron de cargar
 */

(() => {
    const loaderId = 'app-loader';

    document.documentElement.classList.add('is-loading');

    const hideLoader = (): void => {
        const loader = document.getElementById(loaderId);
        if (!loader) return;

        loader.classList.add('is-hidden');
        loader.setAttribute('aria-busy', 'false');
        document.documentElement.classList.remove('is-loading');

        // Eliminar del DOM luego de la transición (debe coincidir con CSS transition: 0.5s)
        setTimeout(() => {
            loader.remove();
        }, 520);
    };

    if (document.readyState === 'complete') {
        hideLoader();
    } else {
        window.addEventListener('load', hideLoader, { once: true });
    }
})();
