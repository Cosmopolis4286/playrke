// resources/js/games/bingo/bingo.main.js
import { createApp } from 'vue';
import '@/show-app'

export default function mountApp(Component, props = {}) {
  const app = createApp(Component, props);

  app.mount('#app');

  window._vueMounted = true;
  if (typeof window.vueAppIsReady === 'function') {
    window.vueAppIsReady();
  }
}
