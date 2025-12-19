// resources/js/helpers/mounApp.ts

/**
 * Controla la visibilidad del contenido principal y el loader.
 * - Si Vue llama a `vueAppIsReady`, se marca como montado.
 * - Si no hay Vue, se usará el evento `load` del navegador.
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
    if ((vueMounted || !window.hasOwnProperty("vueAppIsReady")) && domLoaded) {
      const loader = document.getElementById(loaderId);
      const wrapper = document.getElementById(wrapperId);

      if (loader) loader.remove();
      if (wrapper) wrapper.classList.remove('d-none');

      if (import.meta.env.DEV) {
        console.log('✅ App mostrada (DOM cargado' + (vueMounted ? ' + Vue montado' : ' sin Vue') + ')');
      }
    }
  };

  /**
   * Marca que Vue ya montó.
   */
  const markAppReady = (): void => {
    vueMounted = true;
    tryShowApp();
  };

  // Exponer globalmente para Vue (opcional)
  (window as any).vueAppIsReady = markAppReady;

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
