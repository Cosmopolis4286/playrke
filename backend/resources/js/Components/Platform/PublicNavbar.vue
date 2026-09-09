<script setup lang="ts">
import { ref, onMounted, onUnmounted } from "vue";
import { Link } from "@inertiajs/vue3";

/**
 * Navbar compartido por las páginas públicas (Landing, GameRoomsShowcase).
 *
 * Bug real corregido en esta versión: al extraer el nav a este componente
 * las clases .btn/.btn--ghost/.btn--primary quedaron SIN estilo — vivían en
 * el <style scoped> de Landing.vue, y `scoped` no comparte reglas entre
 * componentes distintos. Los botones caían al estilo nativo del navegador
 * (blanco, con borde) en vez del diseño de marca. Se declaran aquí también.
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

// El nav se "endurece" (más opaco + sombra) tras un scroll pequeño, en vez
// de mantener siempre el mismo blur translúcido — refuerza que quedó
// pegado arriba de contenido real, no flotando sobre el hero vacío.
const scrolled = ref(false);
function onScroll() {
  scrolled.value = window.scrollY > 8;
}
onMounted(() => {
  onScroll();
  window.addEventListener("scroll", onScroll, { passive: true });
});
onUnmounted(() => window.removeEventListener("scroll", onScroll));
</script>

<template>
  <nav class="public-nav" :class="{ 'public-nav--scrolled': scrolled }">
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
  background: rgba(6, 10, 24, 0.55);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
  transition: background-color 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
}
.public-nav--scrolled {
  background: rgba(6, 10, 24, 0.86);
  border-bottom-color: rgba(255, 255, 255, 0.1);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35);
}

.public-nav__inner {
  max-width: 1180px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 12px 20px;
}

.public-nav__brand {
  display: flex;
  align-items: center;
  flex-shrink: 0;
  transition: filter 0.2s ease, transform 0.2s ease;
}
.public-nav__brand:hover { transform: translateY(-1px); }
.public-nav__logo {
  height: 32px;
  width: auto;
  display: block;
  filter: drop-shadow(0 0 10px rgba(123, 79, 255, 0.3));
}

.public-nav__links {
  display: flex;
  gap: 28px;
  font-size: 13px;
  font-weight: 600;
  color: #7a96c8;
}
.public-nav__links a {
  position: relative;
  text-decoration: none;
  color: inherit;
  white-space: nowrap;
  padding: 4px 0;
  transition: color 0.2s ease;
}
.public-nav__links a::after {
  content: "";
  position: absolute;
  left: 0; right: 100%;
  bottom: 0;
  height: 2px;
  border-radius: 2px;
  background: linear-gradient(90deg, #2af598, #009efd);
  transition: right 0.25s ease;
}
.public-nav__links a:hover { color: #eef2ff; }
.public-nav__links a:hover::after { right: 0; }
.public-nav__links a:focus-visible {
  outline: 2px solid #2af598;
  outline-offset: 2px;
  border-radius: 2px;
}

.public-nav__cta {
  display: flex;
  gap: 10px;
  align-items: center;
  flex-shrink: 0;
}

/* ── Botones — mismas clases usadas en el resto del sitio público
   (Landing.vue las declara para SU propio scope; este componente necesita
   su propia copia porque `scoped` no las comparte entre componentes). ── */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 9px 18px;
  border-radius: 999px;
  font-size: 13px;
  font-weight: 800;
  letter-spacing: 0.3px;
  text-decoration: none;
  cursor: pointer;
  border: none;
  white-space: nowrap;
  transition: filter 0.2s ease, border-color 0.2s ease, transform 0.15s ease;
}
.btn:active { transform: scale(0.97); }
.btn--ghost {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: #c7d2f0;
}
.btn--ghost:hover { border-color: #7a96c8; background: rgba(255, 255, 255, 0.06); }
.btn--primary {
  background: linear-gradient(135deg, #2af598 0%, #009efd 100%);
  color: #06121a;
  box-shadow: 0 4px 16px rgba(42, 245, 152, 0.25);
}
.btn--primary:hover { filter: brightness(1.08); box-shadow: 0 6px 20px rgba(42, 245, 152, 0.35); }
.btn:focus-visible {
  outline: 2px solid #2af598;
  outline-offset: 2px;
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
  .public-nav__cta .btn { padding: 7px 10px; font-size: 11.5px; }
}

@media (prefers-reduced-motion: reduce) {
  .public-nav,
  .public-nav__brand,
  .public-nav__links a,
  .btn { transition: none; }
}
</style>
