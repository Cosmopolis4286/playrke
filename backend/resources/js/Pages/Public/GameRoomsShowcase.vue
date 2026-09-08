<script setup lang="ts">
import { onMounted, onUnmounted, ref, computed } from "vue";
import { Link } from "@inertiajs/vue3";
// Mismo motivo que Landing.vue: esta página usa components/layouts/inertia.blade.php
// (head minimalista, sin el <link> de FontAwesome vendorizado de head.blade.php).
import "@fortawesome/fontawesome-free/css/all.min.css";

import BingoGameRoomCard from "@js/Components/games/bingo/Room/GameRooms/BingoGameRoomCard.vue";
import RoomTopBuyersModal from "@js/Components/games/bingo/Room/GameRooms/RoomTopBuyersModal.vue";
import GameRoomsKpiHeader from "@js/Components/games/bingo/Room/GameRooms/GameRoomsKpiHeader.vue";
import GameRoomsFilterBar from "@js/Components/games/bingo/Room/GameRooms/GameRoomsFilterBar.vue";
import BingoRulesModal from "@js/Pages/Games/Bingo/Config/Rules/BingoRulesModal.vue";
import InfoModal from "@js/Components/auth/InfoModal.vue";
import JackpotDisplay from "@js/Components/Platform/JackpotDisplay.vue";
import type { JackpotEntry } from "@js/Components/Platform/JackpotDisplay.vue";
import { useGameRoomCountdown } from "@js/composables/games/bingo/room/game-rooms/useGameRoomCountdown";
import { useGameRoomKpis } from "@js/composables/games/bingo/room/game-rooms/useGameRoomKpis";
import { useGameRoomEcho } from "@js/composables/games/bingo/room/game-rooms/useGameRoomEcho";

import type { BingoGameRoomsPageProps, GameRoomFilters } from "@js/types/games/bingo/room/game-rooms/bingo-game-room";

/**
 * Vitrina pública de salas (sin sesión) — mismo catálogo/métricas que el
 * lobby autenticado (Pages/Games/Bingo/Room/GameRooms.vue), pero de solo
 * lectura: el click en cualquier card lleva a login/registro en vez de abrir
 * el modal de compra. Ver App\Http\Controllers\PublicController::
 * gameRoomsShowcase() y la decisión de producto de no exponer compra sin
 * cuenta (riesgo de KYC/fraude — visitante sin identidad persistente).
 *
 * Mismo lenguaje visual/patrón que la sección #salas de Landing.vue (header
 * con dados, KPIs, filtros, fondo estelar, FAB de reglamento) — portado
 * literalmente para que ambas páginas públicas se sientan la misma
 * plataforma. Filtros client-side: getActivePublicRoomsForShowcase() trae
 * todas las salas públicas activas sin paginar (volumen esperado bajo), así
 * que construir un segundo endpoint de filtrado server-side (el repositorio
 * solo lo soporta hoy para el caso autenticado) no se justifica.
 */
const props = defineProps<BingoGameRoomsPageProps & { initialJackpots?: JackpotEntry[] }>();

// Copia reactiva propia (no las props directas): useGameRoomEcho la parchea
// in-place con los mismos eventos WebSocket que la vista autenticada
// (bingo.lobby/bingo.room.{id} son canales públicos) — antes esta página no
// tenía ningún realtime, listado 100% estático desde el primer render.
const rooms = ref(props.rooms ? props.rooms.map((r) => ({ ...r })) : []);

const serverTimestampMs = props.dateTimeRooms?.timestamp
  ? Number(props.dateTimeRooms.timestamp)
  : Date.now();

const { init: initCountdown, getCountdown } = useGameRoomCountdown(serverTimestampMs);

onMounted(() => {
  initCountdown(rooms.value);
});

useGameRoomEcho(rooms, {
  onRoomAdded: (room) => initCountdown([room]),
});

// ── Filtro por variante — mismo GameRoomsFilterBar que la vista autenticada
// y que la Landing, filtrado 100% client-side (ver nota de arriba). ──
const filters = ref<GameRoomFilters>({ dateFrom: null, dateTo: null, variant: null });
const filteredRooms = computed(() => {
  if (!filters.value.variant && !filters.value.dateFrom) return rooms.value;
  return rooms.value.filter((room) => {
    if (filters.value.variant && room.config?.game_variant !== filters.value.variant) return false;
    if (filters.value.dateFrom) {
      const unix = room.config?.unix_date;
      if (!unix) return false;
      const roomDate = new Date(unix * 1000).toISOString().slice(0, 10);
      const to = filters.value.dateTo ?? filters.value.dateFrom;
      if (roomDate < filters.value.dateFrom || roomDate > to) return false;
    }
    return true;
  });
});
function resetFilters() {
  filters.value = { dateFrom: null, dateTo: null, variant: null };
}

const isFiltered = ref(false);
const { nextRoomUnixDate, totalActiveRooms } = useGameRoomKpis(rooms, isFiltered);

// ── Reglamento del bingo — sin dueño único en este contexto público
// (bingoRules/imageBingoRules llegan vacíos desde publicShowcase()),
// BingoRulesModal ya resuelve mostrando el reglamento genérico. ──
const showRulesModal = ref(false);

// ── Jackpots — sembrados por el backend, refrescados por el propio
// JackpotDisplay.vue (polling interno). ──
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

// ── Modal "Ver Top de Compras" ──
const topBuyersRoomId = ref<string | null>(null);
const topBuyersRoomUuidCode = computed(() => {
  const room = rooms.value.find((r) => r.id === topBuyersRoomId.value);
  return room?.uuid_code ?? null;
});
function openTopBuyers(roomId: string) {
  topBuyersRoomId.value = roomId;
}
function closeTopBuyers() {
  topBuyersRoomId.value = null;
}

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
    <!-- Botón flotante: reglamento del bingo -->
    <button
      type="button"
      class="grp-rules-fab"
      title="Ver reglamento del Bingo"
      aria-label="Ver reglamento del Bingo"
      @click="showRulesModal = true"
    >
      <i class="fas fa-book" />
    </button>

    <!-- ── Encabezado ── -->
    <div class="game-rooms-page__header">
      <div class="grp-header-glow" />
      <div class="grp-title-wrap">
        <span class="grp-dice grp-dice--1">&#9858;</span>
        <h1 class="game-rooms-page__title">Salas de Bingo en vivo</h1>
        <span class="grp-dice grp-dice--2">&#9858;</span>
      </div>
      <p class="game-rooms-page__subtitle">Regístrate para comprar tus cartones y participar</p>
      <button type="button" class="showcase-page__cta" @click="goToRegister">
        <i class="fas fa-user-plus" /> Crear cuenta gratis
      </button>
      <div class="grp-header-line" />

      <JackpotDisplay v-if="jackpots.length" :jackpots="jackpots" />

      <div class="grp-toolbar">
        <div class="grp-toolbar__top">
          <GameRoomsKpiHeader :next-room-unix-date="nextRoomUnixDate" :total-active-rooms="totalActiveRooms" />
        </div>
        <GameRoomsFilterBar :filters="filters" :loading="false" @apply="() => {}" @reset="resetFilters" />
      </div>
    </div>

    <!-- ── Sin salas ── -->
    <div v-if="!filteredRooms.length" class="game-rooms-page__empty">
      <i class="fas fa-door-closed grp-empty-icon" />
      <p class="grp-empty-title">No hay salas disponibles</p>
    </div>

    <!-- ── Grid de tarjetas ── -->
    <div v-else class="grp-cards-grid">
      <BingoGameRoomCard
        v-for="(room, index) in filteredRooms"
        :key="room.id"
        :room="room"
        :exchange-rate="1"
        :currency-sym="'USD'"
        :countdown="getCountdown(room.id)"
        :index="index"
        @select="goToRegister"
        @show-top-buyers="openTopBuyers"
      />
    </div>

    <p class="showcase-page__footer">
      ¿Ya tienes cuenta?
      <Link href="/login" class="showcase-page__footer-link">Inicia sesión</Link>
    </p>

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

  <!-- ══ Modal de reglamento del bingo ══ -->
  <BingoRulesModal v-model="showRulesModal" :rules="props.bingoRules" :image="props.imageBingoRules" />

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
  isolation: isolate;
  overflow-x: hidden;
  min-height: 100vh;
  padding: 32px 12px 56px;
  background: linear-gradient(160deg, #0a0f24 0%, #0c1230 45%, #060a18 100%);
}

/* ── Fondo estelar — mismas dos capas que .band--rooms en Landing.vue,
   copiadas literalmente para que ambas páginas públicas compartan el mismo
   "mood" ── */
.showcase-page::before {
  content: "";
  position: absolute; inset: 0; z-index: -2; pointer-events: none;
  background-image:
    radial-gradient(1px 1px at  3%  4%, rgba(255,255,255,0.92) 0%, transparent 100%),
    radial-gradient(1px 1px at  9% 14%, rgba(255,255,255,0.60) 0%, transparent 100%),
    radial-gradient(1px 1px at 16%  2%, rgba(255,255,255,0.82) 0%, transparent 100%),
    radial-gradient(1px 1px at 22% 28%, rgba(255,255,255,0.48) 0%, transparent 100%),
    radial-gradient(1px 1px at 29%  9%, rgba(255,255,255,0.74) 0%, transparent 100%),
    radial-gradient(1px 1px at 36% 18%, rgba(255,255,255,0.55) 0%, transparent 100%),
    radial-gradient(1px 1px at 42%  5%, rgba(255,255,255,0.88) 0%, transparent 100%),
    radial-gradient(1px 1px at 50% 22%, rgba(255,255,255,0.66) 0%, transparent 100%),
    radial-gradient(1px 1px at 57%  8%, rgba(255,255,255,0.80) 0%, transparent 100%),
    radial-gradient(1px 1px at 63% 32%, rgba(255,255,255,0.52) 0%, transparent 100%),
    radial-gradient(1px 1px at 70%  3%, rgba(255,255,255,0.86) 0%, transparent 100%),
    radial-gradient(1px 1px at 77% 18%, rgba(255,255,255,0.68) 0%, transparent 100%),
    radial-gradient(1px 1px at 84%  7%, rgba(255,255,255,0.75) 0%, transparent 100%),
    radial-gradient(1px 1px at 91% 25%, rgba(255,255,255,0.58) 0%, transparent 100%),
    radial-gradient(1px 1px at 97% 12%, rgba(255,255,255,0.84) 0%, transparent 100%),
    radial-gradient(1px 1px at  5% 42%, rgba(255,255,255,0.62) 0%, transparent 100%),
    radial-gradient(1px 1px at 13% 55%, rgba(255,255,255,0.44) 0%, transparent 100%),
    radial-gradient(1px 1px at 21% 67%, rgba(255,255,255,0.78) 0%, transparent 100%),
    radial-gradient(1px 1px at 28% 48%, rgba(255,255,255,0.50) 0%, transparent 100%),
    radial-gradient(1px 1px at 35% 72%, rgba(255,255,255,0.70) 0%, transparent 100%),
    radial-gradient(1px 1px at 44% 58%, rgba(255,255,255,0.42) 0%, transparent 100%),
    radial-gradient(1px 1px at 51% 80%, rgba(255,255,255,0.82) 0%, transparent 100%),
    radial-gradient(1px 1px at 59% 45%, rgba(255,255,255,0.64) 0%, transparent 100%),
    radial-gradient(1px 1px at 67% 62%, rgba(255,255,255,0.56) 0%, transparent 100%),
    radial-gradient(1px 1px at 74% 78%, rgba(255,255,255,0.74) 0%, transparent 100%),
    radial-gradient(1px 1px at 81% 50%, rgba(255,255,255,0.48) 0%, transparent 100%),
    radial-gradient(1px 1px at 88% 68%, rgba(255,255,255,0.80) 0%, transparent 100%),
    radial-gradient(1px 1px at 94% 85%, rgba(255,255,255,0.52) 0%, transparent 100%),
    radial-gradient(1px 1px at  7% 88%, rgba(255,255,255,0.66) 0%, transparent 100%),
    radial-gradient(1px 1px at 18% 93%, rgba(255,255,255,0.44) 0%, transparent 100%),
    radial-gradient(1px 1px at 33% 87%, rgba(255,255,255,0.72) 0%, transparent 100%),
    radial-gradient(1px 1px at 48% 95%, rgba(255,255,255,0.58) 0%, transparent 100%),
    radial-gradient(1px 1px at 62% 90%, rgba(255,255,255,0.80) 0%, transparent 100%),
    radial-gradient(1px 1px at 78% 96%, rgba(255,255,255,0.46) 0%, transparent 100%),
    radial-gradient(1px 1px at 92% 89%, rgba(255,255,255,0.76) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 11% 16%, rgba(190,210,255,0.95) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 26%  6%, rgba(210,185,255,0.88) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 46% 12%, rgba(180,225,255,0.90) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 66% 19%, rgba(200,255,220,0.82) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 85% 11%, rgba(255,240,180,0.85) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 38% 60%, rgba(190,210,255,0.78) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 72% 52%, rgba(255,200,200,0.72) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 55% 76%, rgba(200,225,255,0.86) 0%, transparent 100%);
  animation: twinkle-a 7s ease-in-out infinite alternate;
}
.showcase-page::after {
  content: "";
  position: absolute; inset: 0; z-index: -1; pointer-events: none;
  background-image:
    radial-gradient(2px 2px at  8% 12%, rgba(255,255,255,0.96) 0%, transparent 100%),
    radial-gradient(2px 2px at 24% 35%, rgba(200,215,255,0.88) 0%, transparent 100%),
    radial-gradient(2px 2px at 40%  6%, rgba(255,255,255,0.84) 0%, transparent 100%),
    radial-gradient(2px 2px at 58% 22%, rgba(255,240,180,0.92) 0%, transparent 100%),
    radial-gradient(2px 2px at 74% 15%, rgba(180,200,255,0.86) 0%, transparent 100%),
    radial-gradient(2px 2px at 90% 38%, rgba(255,255,255,0.78) 0%, transparent 100%),
    radial-gradient(2px 2px at 15% 65%, rgba(200,255,240,0.82) 0%, transparent 100%),
    radial-gradient(2px 2px at 50% 55%, rgba(220,200,255,0.88) 0%, transparent 100%),
    radial-gradient(2px 2px at 80% 72%, rgba(255,255,255,0.90) 0%, transparent 100%),
    radial-gradient(2px 2px at 35% 82%, rgba(200,220,255,0.80) 0%, transparent 100%),
    radial-gradient(2px 2px at 68% 88%, rgba(255,250,200,0.84) 0%, transparent 100%),
    radial-gradient(3px 3px at 19% 20%, rgba(255,255,255,1.00) 0%, rgba(210,230,255,0.20) 55%, transparent 100%),
    radial-gradient(3px 3px at 44% 14%, rgba(220,200,255,0.96) 0%, rgba(185,165,255,0.18) 55%, transparent 100%),
    radial-gradient(3px 3px at 69% 42%, rgba(255,255,255,0.94) 0%, rgba(225,245,255,0.15) 55%, transparent 100%),
    radial-gradient(3px 3px at 88% 60%, rgba(200,240,255,0.98) 0%, rgba(160,220,255,0.15) 55%, transparent 100%),
    radial-gradient(3px 3px at 30% 78%, rgba(255,240,160,0.90) 0%, rgba(255,220,100,0.12) 55%, transparent 100%),
    radial-gradient(4px 4px at 55% 30%, rgba(255,255,255,1.00) 0%, rgba(205,225,255,0.28) 50%, transparent 100%),
    radial-gradient(4px 4px at 12% 45%, rgba(255,248,200,0.95) 0%, rgba(255,240,160,0.18) 50%, transparent 100%);
  animation: twinkle-b 9s ease-in-out infinite alternate-reverse;
}
@keyframes twinkle-a {
  0%   { opacity: 0.55; } 40%  { opacity: 0.95; }
  70%  { opacity: 0.70; } 100% { opacity: 0.85; }
}
@keyframes twinkle-b {
  0%   { opacity: 0.70; } 35%  { opacity: 0.50; }
  65%  { opacity: 1.00; } 100% { opacity: 0.65; }
}

/* ── FAB reglamento ── */
.grp-rules-fab {
  position: fixed;
  right: 16px;
  bottom: 18px;
  z-index: 50;
  width: 38px;
  height: 38px;
  border-radius: 50%;
  border: 1px solid rgba(123,79,255,0.35);
  background: linear-gradient(135deg, #2a1f5e 0%, #1a2a52 100%);
  color: #cdd8ff;
  font-size: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: 0 4px 18px rgba(0,0,0,0.45), 0 0 0 0 rgba(123,79,255,0.5);
  transition: transform .2s ease, box-shadow .2s ease, color .2s ease, border-color .2s ease;
  animation: rulesFabPulse 3s ease-in-out infinite;
}
.grp-rules-fab:hover {
  transform: translateY(-2px) scale(1.06);
  color: #fff;
  border-color: rgba(42,245,152,0.6);
  box-shadow: 0 6px 22px rgba(0,0,0,0.5), 0 0 18px rgba(42,245,152,0.35);
}
.grp-rules-fab:active { transform: translateY(0) scale(0.97); }
@keyframes rulesFabPulse {
  0%, 100% { box-shadow: 0 4px 18px rgba(0,0,0,0.45), 0 0 0 0 rgba(123,79,255,0.45); }
  50%      { box-shadow: 0 4px 18px rgba(0,0,0,0.45), 0 0 0 8px rgba(123,79,255,0); }
}

/* ── Header ── */
.game-rooms-page__header { text-align: center; margin-bottom: 32px; position: relative; z-index: 5; }
.grp-header-glow {
  position: absolute;
  top: -60px; left: 50%; transform: translateX(-50%);
  width: 500px; height: 200px;
  background: radial-gradient(ellipse, rgba(123,79,255,0.25) 0%, transparent 70%);
  pointer-events: none;
}
.grp-title-wrap { display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 4px; }
.grp-dice {
  font-size: 20px;
  filter: drop-shadow(0 0 8px rgba(42,245,152,0.6));
  animation: spinDice 8s linear infinite;
  color: #2af598;
  flex-shrink: 0;
}
.grp-dice--2 { animation-direction: reverse; animation-duration: 10s; color: #009efd; }
@keyframes spinDice { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

.game-rooms-page__title {
  font-family: "Orbitron", "Anton", sans-serif;
  font-size: clamp(21px, 4vw, 32px);
  font-weight: 800;
  letter-spacing: 1.5px;
  margin: 0;
  text-shadow: 0 0 30px rgba(42,245,152,0.22), 0 0 60px rgba(123,79,255,0.12), 0 2px 4px rgba(0,0,0,0.5);
  background: linear-gradient(135deg, #e0eaff 0%, #a8c4ff 50%, #7b4fff 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
.game-rooms-page__subtitle { font-size: 12px; color: #7a96c8; margin: 8px 0 16px; }

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

.grp-header-line {
  width: 60px; height: 2px; margin: 20px auto 24px;
  background: linear-gradient(90deg, transparent, #2af598, #009efd, transparent);
  border-radius: 2px;
  position: relative;
}
.grp-header-line::after {
  content: '';
  position: absolute; top: -1px; left: 50%; transform: translateX(-50%);
  width: 6px; height: 4px;
  background: #2af598; border-radius: 50%;
  box-shadow: 0 0 8px #2af598;
}

.grp-toolbar {
  display: flex; flex-direction: column; align-items: center;
  gap: 10px;
  margin: 26px auto 0; max-width: 620px;
  position: relative;
  z-index: 30;
}
.grp-toolbar__top { display: flex; align-items: center; justify-content: center; gap: 8px; }

/* ── Empty state ── */
.game-rooms-page__empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  max-width: 320px;
  margin: 40px auto;
  padding: 28px 20px;
  border-radius: 20px;
  text-align: center;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.08);
}
.grp-empty-icon { font-size: 26px; color: #4a6698; }
.grp-empty-title { font-size: 13.5px; font-weight: 600; color: #9fb3da; margin: 0; }

/* ── Grid ── */
.grp-cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 220px));
  justify-content: center;
  gap: 18px;
  max-width: 1180px;
  margin: 0 auto;
}

/* ── Footer ── */
.showcase-page__footer {
  position: relative;
  text-align: center;
  margin: 40px 0 8px;
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

@media (max-width: 380px) {
  .grp-cards-grid { grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 10px; }
  .grp-rules-fab { right: 12px; bottom: 14px; width: 34px; height: 34px; font-size: 12px; }
}

@media (prefers-reduced-motion: reduce) {
  .showcase-page::before,
  .showcase-page::after { animation: none; }
  .grp-dice { animation: none; }
  .grp-rules-fab { animation: none; }
}
</style>
