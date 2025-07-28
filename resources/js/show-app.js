// resources/js/show-app.js
/**
 * Controla la visibilidad del contenido principal y loader.
 * Se expone globalmente la función vueAppIsReady para que Vue llame
 * cuando esté montado, y también tiene fallback en 'load'.
 */
(function () {
  const showApp = () => {
    document.getElementById('app-loader')?.remove()
    document.getElementById('app-wrapper')?.classList.remove('d-none')
  }

  window.vueAppIsReady = () => {
    window._vueMounted = true
    showApp()
  }

  window.addEventListener('load', () => {
    if (!window._vueMounted) {
      showApp()
    }
  })
})()
