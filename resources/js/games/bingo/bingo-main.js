// resources/js/games/bingo/bingo.main.js
import { createApp } from 'vue';
import '@/show-app'; // Se asegura de ejecutar el control visual

export default function mountApp(Component, props = {}) {
  const app = createApp(Component, props);
  app.mount('#app');

  if (typeof window.vueAppIsReady === 'function') {
    window.vueAppIsReady(); // Solo se llama, no se redefine
  }
}
