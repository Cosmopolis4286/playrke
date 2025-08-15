// resources/js/mounApp.ts

/**
 * Controla la visibilidad del contenido principal y el loader.
 * - `vueAppIsReady` se expone globalmente para que Vue llame al montarse.
 * - Si Vue no llama, el listener 'load' asegura mostrar el contenido.
 */

(() => {
  const loaderId = 'app-loader';
  const wrapperId = 'app-wrapper';

  const showApp = (): void => {
    document.getElementById(loaderId)?.remove();
    document.getElementById(wrapperId)?.classList.remove('d-none');
  };

  window.vueAppIsReady = (): void => {
    window._vueMounted = true;
    showApp();
  };

  window.addEventListener('load', () => {
    if (!window._vueMounted) {
      showApp();
    }
  });
})();
