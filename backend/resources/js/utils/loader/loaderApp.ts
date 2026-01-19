/**
 * Controla la visibilidad del contenido principal y el loader.
 * - Solo se quita el loader cuando el DOM y recursos cargaron.
 */

(() => {
    const loaderId = 'app-loader';
    const wrapperId = 'app-wrapper';

    let vueMounted = false;
    let domLoaded = false;

    /**
     * Muestra el contenido principal y elimina el loader si ambas condiciones se cumplen.
     */
    const tryShowApp = (): void => {
        if (!domLoaded) return
        const loader = document.getElementById(loaderId);
        const wrapper = document.getElementById(wrapperId);

        if (loader) loader.remove();
        if (wrapper) wrapper.classList.remove('d-none');
    };

    /**
     * Detectar cuando el DOM y recursos terminaron de cargar.
     */
    const onDomReady = () => {
        domLoaded = true;
        tryShowApp();
    };

    if (document.readyState === 'complete') {
        // Ya terminó de cargar todo
        onDomReady();
    } else {
        // Esperar a que termine de cargar todos los recursos
        window.addEventListener('load', onDomReady, { once: true });
    }
})();
