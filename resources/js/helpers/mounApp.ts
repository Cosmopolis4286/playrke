// resources/js/helpers/mounApp.ts

/**
 * Controla la visibilidad del contenido principal y el loader.
 * - `vueAppIsReady` se expone globalmente para que Vue llame al montarse.
 * - Si Vue no llama, se muestra el contenido tras 'DOMContentLoaded' o 'load'.
 */

(() => {
  const loaderId = 'app-loader';
  const wrapperId = 'app-wrapper';

  let vueMounted = false;

  /**
   * Muestra el contenido principal y elimina el loader.
   */
  const showApp = (): void => {
    const loader = document.getElementById(loaderId);
    const wrapper = document.getElementById(wrapperId);

    if (loader) loader.remove();
    if (wrapper) wrapper.classList.remove('d-none');
  };

  /**
   * Marca la app como lista. Evita llamadas repetidas.
   */
  const markAppReady = (): void => {
    if (!vueMounted) {
      vueMounted = true;
      showApp();
      console.log('✅ Vue App montada y contenido mostrado');
    }
  };

  /**
   * Exponer globalmente para que Vue llame al montarse.
   */
  (window as any).vueAppIsReady = markAppReady;

  /**
   * Fallback: mostrar la app si Vue no se monta.
   */
  const fallbackShow = () => {
    if (!vueMounted) {
      showApp();
      console.log('⚠️ Vue no se montó, mostrando contenido tras fallback');
    }
  };

  // Usar DOMContentLoaded para fallback temprano
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', fallbackShow, { once: true });
  } else {
    fallbackShow();
  }

  // Extra: fallback definitivo por si hay recursos tardíos
  window.addEventListener('load', fallbackShow);
})();
