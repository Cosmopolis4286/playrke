/**
 * Configuración del layer shared - Funcionalidad compartida.
 *
 * Este layer contiene estilos y configuración compartida entre
 * admin y player. Los stores, types y utils están en el proyecto principal.
 */

export default defineNuxtConfig({
  // Configuración específica del layer shared
  alias: {
    '@shared': '~/layers/shared'
  },

  // CSS compartido
  css: [
    '~/layers/shared/assets/css/shared.css'
  ]
})
