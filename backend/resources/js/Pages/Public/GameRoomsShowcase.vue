<script setup lang="ts">
import { computed, onUnmounted, ref } from "vue";
import { Link } from "@inertiajs/vue3";
// Mismo motivo que Landing.vue: esta página usa components/layouts/inertia.blade.php
// (head minimalista, sin el <link> de FontAwesome vendorizado de head.blade.php).
import "@fortawesome/fontawesome-free/css/all.min.css";

import RoomTopBuyersModal from "@js/Components/games/bingo/Room/GameRooms/RoomTopBuyersModal.vue";
import GameRoomsSection from "@js/Components/games/bingo/Room/GameRooms/GameRoomsSection.vue";
import InfoModal from "@js/Components/auth/InfoModal.vue";
import JackpotSection from "@js/Components/Platform/JackpotSection.vue";
import type { JackpotEntry } from "@js/Components/Platform/JackpotDisplay.vue";

import type { BingoGameRoomsPageProps } from "@js/types/games/bingo/room/game-rooms/bingo-game-room";

/**
 * Vitrina pública de salas (sin sesión) — mismo catálogo/métricas que el
 * lobby autenticado (Pages/Games/Bingo/Room/GameRooms.vue), pero de solo
 * lectura: el click en cualquier card lleva a login/registro en vez de abrir
 * el modal de compra. Ver App\Http\Controllers\PublicController::
 * gameRoomsShowcase() y la decisión de producto de no exponer compra sin
 * cuenta (riesgo de KYC/fraude — visitante sin identidad persistente).
 *
 * El header/KPIs/filtros/grid/FAB de reglamento viven en GameRoomsSection.vue
 * (componente compartido con la sección #salas de Landing.vue) — antes cada
 * página repetía ese bloque casi línea por línea.
 */
const props = defineProps<BingoGameRoomsPageProps & { initialJackpots?: JackpotEntry[] }>();

const serverTimestampMs = props.dateTimeRooms?.timestamp
  ? Number(props.dateTimeRooms.timestamp)
  : Date.now();

const jackpots = ref<JackpotEntry[]>(props.initialJackpots ?? []);

// La vitrina es pública y siempre se ve oscura, sin importar el tema del
// dashboard guardado en localStorage — mismo mecanismo que Landing.vue.
const previousTheme = document.documentElement.getAttribute("data-theme");
document.documentElement.setAttribute("data-theme", "dark");
const previousBodyBg = document.body.style.backgroundColor;
const previousBodyMargin = document.body.style.margin;
document.body.style.backgroundColor = "#060a18";
document.body.style.margin = "0";
onUnmounted(() => {
  if (previousTheme) {
    document.documentElement.setAttribute("data-theme", previousTheme);
  } else {
    document.documentElement.removeAttribute("data-theme");
  }
  document.body.style.backgroundColor = previousBodyBg;
  document.body.style.margin = previousBodyMargin;
});

// ── Modal "Ver Top de Compras" — room-uuid-code se resuelve por props.rooms
// (identidad estable), GameRoomsSection mantiene su propia copia con realtime. ──
const topBuyersRoomId = ref<string | null>(null);
function openTopBuyers(roomId: string) {
  topBuyersRoomId.value = roomId;
}
function closeTopBuyers() {
  topBuyersRoomId.value = null;
}
const topBuyersRoomUuidCode = computed(() => {
  const room = (props.rooms ?? []).find((r) => r.id === topBuyersRoomId.value);
  return room?.uuid_code ?? null;
});

// ── Modal Términos/Acerca de/Privacidad — mismo componente que Auth/Landing ──
type InfoModalKey = "terms" | "about" | "privacy";
const activeInfoModal = ref<InfoModalKey | null>(null);
const openInfoModal = (key: InfoModalKey) => { activeInfoModal.value = key; };

function goToRegister() {
  window.location.href = "/register";
}
</script>

<template>
  <div class="showcase-page">
    <!-- Wrapper con el padding lateral — el footer (fuera de este div)
         debe llegar de borde a borde, igual que en Landing.vue (donde
         .landing raíz no tiene padding, solo sus .band internas). -->
    <div class="showcase-page__content">
      <div class="showcase-page__intro">
        <p class="showcase-page__subtitle">Regístrate para comprar tus cartones y participar</p>
        <button type="button" class="showcase-page__cta" @click="goToRegister">
          <i class="fas fa-user-plus" /> Crear cuenta gratis
        </button>
      </div>

      <JackpotSection :jackpots="jackpots" bordered />

      <GameRoomsSection
        :rooms="props.rooms ?? []"
        :server-timestamp-ms="serverTimestampMs"
        :bingo-rules="props.bingoRules"
        :image-bingo-rules="props.imageBingoRules"
        title="Salas de Bingo en vivo"
        subtitle="Todas las salas activas ahora mismo."
        starry-background
        @select="goToRegister"
        @show-top-buyers="openTopBuyers"
      />

      <p class="showcase-page__footer">
        ¿Ya tienes cuenta?
        <Link href="/login" class="showcase-page__footer-link">Inicia sesión</Link>
      </p>
    </div>

    <!-- ══ FOOTER — mismo footer completo que Landing.vue (marca, redes,
         columnas de navegación/legal, barra de copyright) ══ -->
    <footer class="landing__footer">
      <div class="landing__footer-inner">
        <div class="landing__footer-brand">
          <span class="landing__brand">PLAYRKE</span>
          <p class="landing__footer-tagline">Bingo en vivo con premios reales. Ves el pozo, ves quién gana.</p>
          <nav class="landing__footer-socials" aria-label="Redes sociales">
            <a href="https://www.facebook.com/my.play.rke" target="_blank" rel="noopener" aria-label="Facebook" class="fab fa-facebook" />
            <a href="https://www.instagram.com/my.play.rke" target="_blank" rel="noopener" aria-label="Instagram" class="fab fa-instagram" />
            <a href="https://t.me/+rV3KWBuuKOtjYzkx" target="_blank" rel="noopener" aria-label="Telegram" class="fab fa-telegram" />
          </nav>
        </div>

        <nav class="landing__footer-col" aria-label="Navegación">
          <span class="landing__footer-col-title">Plataforma</span>
          <Link href="/#jackpot">Jackpot</Link>
          <Link href="/#salas">Salas en vivo</Link>
          <Link href="/#verificar">Verificar cartón</Link>
          <Link href="/#invita">Invita y gana</Link>
        </nav>

        <nav class="landing__footer-col" aria-label="Legal">
          <span class="landing__footer-col-title">Legal</span>
          <button type="button" class="landing__footer-link" @click="openInfoModal('terms')">Términos y condiciones</button>
          <button type="button" class="landing__footer-link" @click="openInfoModal('about')">Acerca de nosotros</button>
          <button type="button" class="landing__footer-link" @click="openInfoModal('privacy')">Política de privacidad</button>
        </nav>
      </div>

      <div class="landing__footer-bottom">
        <span>PlayRKE © {{ new Date().getFullYear() }}. Todos los derechos reservados.</span>
        <span class="landing__footer-trust">Retiros verificados · Cartones auditables por serial</span>
      </div>
    </footer>
  </div>

  <!-- ══ Modal "Ver Top de Compras" ══ -->
  <RoomTopBuyersModal
    :room-id="topBuyersRoomId"
    :room-uuid-code="topBuyersRoomUuidCode"
    endpoint-base="/game-rooms"
    @close="closeTopBuyers"
  />

  <!-- ══ Modal Términos/Acerca de/Privacidad ══ -->
  <InfoModal v-model="activeInfoModal" />
</template>

<style>
/* Sin scoped — mismo fix que Landing.vue: esta página también usa
   components/layouts/inertia.blade.php (head minimalista, sin reset de
   Bootstrap/Tailwind preflight), así que sin esto el margin de 8px que el
   navegador aplica a <body> por defecto deja franjas visibles a los lados
   aun con .showcase-page ocupando el resto del ancho. */
html:has(.showcase-page), body:has(.showcase-page) {
  margin: 0;
  background-color: #060a18 !important;
  overflow-x: hidden;
}

html:has(.showcase-page) {
  scrollbar-width: thin;
  scrollbar-color: #2af598 rgba(255, 255, 255, 0.06);
}
html:has(.showcase-page)::-webkit-scrollbar { width: 10px; }
html:has(.showcase-page)::-webkit-scrollbar-track { background: rgba(255, 255, 255, 0.03); }
html:has(.showcase-page)::-webkit-scrollbar-thumb {
  background: linear-gradient(180deg, #2af598, #009efd);
  border-radius: 6px;
  border: 2px solid #060a18;
}
html:has(.showcase-page)::-webkit-scrollbar-thumb:hover { background: #2af598; }
</style>

<style scoped>
.showcase-page,
.showcase-page *,
.showcase-page *::before,
.showcase-page *::after {
  box-sizing: border-box;
}

.showcase-page {
  position: relative;
  min-height: 100vh;
  background: linear-gradient(160deg, #0a0f24 0%, #0c1230 45%, #060a18 100%);
}

/* Contenido con padding lateral — el <footer> (hermano, fuera de este div)
   queda full-bleed, igual que en Landing.vue. */
.showcase-page__content {
  padding: 32px 0 40px;
}

.showcase-page__intro {
  text-align: center;
  margin-bottom: 8px;
  padding: 0 12px;
}
.showcase-page__subtitle { font-size: 12px; color: #7a96c8; margin: 0 0 14px; }

.showcase-page__cta {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 22px;
  border: none;
  border-radius: 999px;
  background: linear-gradient(135deg, #2af598 0%, #009efd 100%);
  color: #06121a;
  font-size: 13px;
  font-weight: 800;
  letter-spacing: 0.3px;
  cursor: pointer;
  box-shadow: 0 6px 20px rgba(42, 245, 152, 0.25);
}
.showcase-page__cta:hover { filter: brightness(1.06); }

/* ── Footer ── */
.showcase-page__footer {
  position: relative;
  text-align: center;
  margin: 24px 0 8px;
  font-size: 12px;
  color: #7a96c8;
}
.showcase-page__footer-link {
  color: #2af598;
  font-weight: 700;
  margin-left: 4px;
}

/* ── Footer — copiado literalmente de Landing.vue para que ambas páginas
   públicas compartan exactamente el mismo footer (marca, redes, columnas,
   barra de copyright). No es CSS realmente compartido entre ambos .vue
   (cada uno tiene su <style scoped>), pero las reglas son idénticas
   intencionalmente. ── */
.landing__footer {
  position: relative;
  max-width: none;
  margin-top: 32px;
  border-top: 1px solid rgba(255,255,255,.08);
  background: linear-gradient(180deg, rgba(123,79,255,0.05) 0%, transparent 40%), #050813;
  padding: 56px 20px 28px;
}
.landing__footer::before {
  content: '';
  position: absolute;
  top: -1px; left: 0; right: 0;
  height: 1px;
  background: linear-gradient(90deg, transparent, #2af598 20%, #7b4fff 50%, #009efd 80%, transparent);
  opacity: 0.5;
}
.landing__footer-inner {
  max-width: 1180px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1.4fr 1fr 1fr;
  gap: 40px;
  padding-bottom: 40px;
}
.landing__footer-brand { display: flex; flex-direction: column; gap: 12px; align-items: flex-start; }
.landing__brand {
  font-family: "Orbitron", sans-serif;
  font-weight: 800;
  font-size: 18px;
  letter-spacing: 1px;
  background: linear-gradient(135deg, #e0eaff 0%, #a8c4ff 50%, #7b4fff 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
.landing__footer-tagline { margin: 0; font-size: 13px; line-height: 1.6; color: #7a96c8; max-width: 320px; }
.landing__footer-socials { display: flex; gap: 14px; }
.landing__footer-socials a {
  display: flex; align-items: center; justify-content: center;
  width: 34px; height: 34px;
  border-radius: 10px;
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.08);
  color: #7a96c8;
  font-size: 14px;
  text-decoration: none;
  transition: color 0.2s, border-color 0.2s, transform 0.2s;
}
.landing__footer-socials a:hover {
  color: #2af598;
  border-color: rgba(42,245,152,0.35);
  transform: translateY(-2px);
}
.landing__footer-col { display: flex; flex-direction: column; gap: 12px; }
.landing__footer-col-title {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.8px;
  text-transform: uppercase;
  color: #eef2ff;
  margin-bottom: 2px;
}
.landing__footer-col a,
.landing__footer-link {
  font-family: inherit;
  font-size: 13px;
  text-decoration: none;
  color: #7a96c8;
  transition: color 0.2s;
}
.landing__footer-link {
  background: none;
  border: none;
  padding: 0;
  text-align: inherit;
  cursor: pointer;
}
.landing__footer-col a:hover,
.landing__footer-link:hover { color: #2af598; }
.landing__footer-bottom {
  max-width: 1180px;
  margin: 0 auto;
  padding-top: 24px;
  border-top: 1px solid rgba(255,255,255,.06);
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
  font-size: 12px;
  color: #4a6698;
}
.landing__footer-trust { color: #4a6698; }

@media (max-width: 620px) {
  .landing__footer-inner { grid-template-columns: 1fr; text-align: center; }
  .landing__footer-brand { align-items: center; }
  .landing__footer-bottom { flex-direction: column; text-align: center; }
}
</style>
