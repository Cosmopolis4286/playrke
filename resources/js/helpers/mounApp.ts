// resources/js/helpers/mounApp.ts

/**
 * Controla la visibilidad del contenido principal y el loader.
 * - `vueAppIsReady` se expone globalmente para que Vue llame al montarse.
 * - Si Vue no llama, el listener 'load' asegura mostrar el contenido.
 */

(() => {
  const loaderId = 'app-loader';
  const wrapperId = 'app-wrapper';

  let _vueMounted = false;

  const showApp = (): void => {
    const loader = document.getElementById(loaderId);
    const wrapper = document.getElementById(wrapperId);

    if (loader) loader.remove();
    if (wrapper) wrapper.classList.remove('d-none');
  };

  // Función global que Vue puede llamar al montarse
  (window as any).vueAppIsReady = (): void => {
    if (!_vueMounted) {
      _vueMounted = true;
      showApp();
      console.log('✅ Vue App montada y contenido mostrado');
    }
  };

  // Listener de fallback en caso de que Vue no se monte
  window.addEventListener('load', () => {
    if (!_vueMounted) {
      showApp();
      console.log('⚠️ Vue no se montó, mostrando contenido tras load');
    }
  });
})();
