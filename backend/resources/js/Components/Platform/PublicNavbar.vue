<script setup lang="ts">
import { Link } from "@inertiajs/vue3";

/**
 * Navbar compartido por las páginas públicas (Landing, GameRoomsShowcase) —
 * antes cada página tenía su propio <nav> casi idéntico, con un bug real de
 * responsive: en viewports muy angostos (~320-360px) "PLAYRKE" (texto) + los
 * dos botones de CTA no cabían en una sola fila sin wrap, desbordando el
 * nav horizontalmente. El logo real (imagen) es más compacto que el texto
 * con letter-spacing, y el breakpoint nuevo abajo compacta los CTAs más
 * agresivamente en pantallas pequeñas.
 */
export interface NavLink {
  href: string;
  label: string;
}

withDefaults(
  defineProps<{
    links?: NavLink[];
    showRegister?: boolean;
  }>(),
  {
    links: () => [],
    showRegister: true,
  }
);

defineEmits<{ register: [] }>();

// URL construida en runtime para que Vite no la trate como import estático
// — mismo patrón que GuestLayout.vue.
const logoSrc = `${window.location.origin}/img/logo.png`;
</script>

<template>
  <nav class="public-nav">
    <div class="public-nav__inner">
      <a href="/" class="public-nav__brand" aria-label="PlayRKE — Inicio">
        <img :src="logoSrc" alt="PlayRKE" class="public-nav__logo" />
      </a>

      <div v-if="links.length" class="public-nav__links">
        <a v-for="link in links" :key="link.href" :href="link.href">{{ link.label }}</a>
      </div>

      <div class="public-nav__cta">
        <Link href="/login" class="btn btn--ghost">Iniciar sesión</Link>
        <button v-if="showRegister" type="button" class="btn btn--primary" @click="$emit('register')">
          Crear cuenta
        </button>
      </div>
    </div>
  </nav>
</template>

<style scoped>
.public-nav {
  position: sticky;
  top: 0;
  z-index: 40;
  background: rgba(6, 10, 24, 0.72);
  backdrop-filter: blur(10px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}
.public-nav__inner {
  max-width: 1180px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 10px 20px;
}

.public-nav__brand { display: flex; align-items: center; flex-shrink: 0; }
.public-nav__logo {
  height: 34px;
  width: auto;
  display: block;
  filter: drop-shadow(0 0 10px rgba(123, 79, 255, 0.25));
}

.public-nav__links {
  display: flex;
  gap: 26px;
  font-size: 13px;
  font-weight: 600;
  color: #7a96c8;
}
.public-nav__links a { text-decoration: none; color: inherit; white-space: nowrap; }
.public-nav__links a:hover { color: #eef2ff; }
.public-nav__links a:focus-visible {
  outline: 2px solid #2af598;
  outline-offset: 2px;
}

.public-nav__cta {
  display: flex;
  gap: 10px;
  align-items: center;
  flex-shrink: 0;
}

@media (max-width: 880px) {
  .public-nav__links { display: none; }
}

@media (max-width: 420px) {
  /* Bajo 420px, "logo + 2 botones" ya no cabe con el padding/tamaño de
     desktop — se compacta el nav entero en vez de dejar que rebalse. */
  .public-nav__inner { padding: 8px 12px; gap: 6px; }
  .public-nav__logo { height: 28px; }
  .public-nav__cta { gap: 5px; }
}

@media (max-width: 340px) {
  /* En el extremo más angosto (iPhone SE y similares), "Iniciar sesión"
     como texto completo sigue sin caber junto a "Crear cuenta" — se
     abrevia sin perder claridad de la acción. */
  .public-nav__cta :deep(.btn) { padding: 7px 10px; font-size: 11.5px; }
}
</style>
