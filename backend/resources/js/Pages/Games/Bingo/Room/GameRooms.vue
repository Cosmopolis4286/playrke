<script setup lang="ts">
import "@/css/games/bingo/bingo-game-rooms.css";

import BingoGameRoomCard  from "@js/Components/Games/Bingo/Room/GameRooms/BingoGameRoomCard.vue";
import BingoPurchaseModalWrapper from "@js/Components/Games/Bingo/Card/PurchaseModal/BingoPurchaseModalWrapper.vue";
import BingoRoomInfoModal from "@js/Components/Games/Bingo/Room/GameRooms/BingoRoomInfoModal.vue";
import BingoRulesModal    from "@js/Pages/Games/Bingo/Config/Rules/BingoRulesModal.vue";

import { useGameRooms }             from "@js/composables/games/bingo/room/game-rooms/useGameRooms";
import { BINGO_ROOM_IMG } from "@js/modules/games/bingo/room/game-rooms/gameRoomUtils";

import type { BingoGameRoomsPageProps } from "@js/types/games/bingo/room/game-rooms/bingo-game-room";

const props = defineProps<BingoGameRoomsPageProps>();

const {
  filteredRooms,
  canBuy,
  getCountdown,
  showRulesModal,
  showRoomInfoModal,
  selectedRoomForInfo,
  openRoomInfo,
  closeRoomInfo,
  openPurchaseFromInfo,
  onPurchaseSuccess,
  modal,
  goToLobby,
  redirectingToLobby,
} = useGameRooms(props);
</script>

<template>
  <div class="game-rooms-page">

    <!-- ── Botón flotante: reglamento del bingo ── -->
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
        <h1 class="game-rooms-page__title">{{ props.title ?? "SALAS DE JUEGO" }}</h1>
        <span class="grp-dice grp-dice--2">&#9858;</span>
      </div>
      <p class="game-rooms-page__subtitle">
        Selecciona una sala activa para comprar tus cartones
      </p>
      <div class="grp-header-line" />
    </div>

    <!-- ── Sin salas ── -->
    <div v-if="!filteredRooms.length" class="game-rooms-page__empty">
      <i class="fas fa-door-closed grp-empty-icon" />
      <p class="grp-empty-title">No hay salas disponibles</p>
    </div>

    <!-- ── Grid de tarjetas ── -->
    <div v-else class="game-rooms-page__grid-wrap">
      <div
        class="game-rooms-page__grid"
        :class="{ 'game-rooms-page__grid--centered': filteredRooms.length < 4 }"
      >
        <div
          v-for="(room, index) in filteredRooms"
          :key="room.id"
          class="game-rooms-page__grid-item"
        >
          <BingoGameRoomCard
            :room="room"
            :exchange-rate="props.exchangeRate ?? 1"
            :currency-sym="props.currencySym ?? ''"
            :countdown="getCountdown(room.id)"
            :index="index"
            @select="openRoomInfo"
          />
        </div>
      </div>
    </div>
  </div>

  <!-- ══ Modal de reglamento del bingo ══ -->
  <BingoRulesModal
    v-model="showRulesModal"
    :rules="props.bingoRules"
    :image="props.imageBingoRules"
  />

  <!-- ══ Modal de info de sala ══ -->
  <BingoRoomInfoModal
    v-if="showRoomInfoModal && selectedRoomForInfo"
    :room="selectedRoomForInfo"
    :countdown="getCountdown(selectedRoomForInfo.id)"
    :exchange-rate="props.exchangeRate ?? 1"
    :currency-sym="props.currencySym ?? 'USD'"
    :can-buy="canBuy"
    :bingo-room-img="BINGO_ROOM_IMG"
    :redirecting="redirectingToLobby"
    @close="closeRoomInfo"
    @go-lobby="goToLobby(selectedRoomForInfo.id)"
    @buy="openPurchaseFromInfo"
  />

  <!-- ══ Modal de compra ══ -->
  <BingoPurchaseModalWrapper
    :modal="modal"
    :exchange-rate="props.exchangeRate ?? 1"
    :guard="canBuy"
    @success="onPurchaseSuccess"
  />
</template>

<style>
/* .mt-2 y #app son ancestros fuera de este componente (vienen de
   auth-layout.blade.php), así que scoped no los alcanza. Son `display:
   block` con `height: auto`, por lo que no propagan la altura real de
   #content-scrollable (flex-grow-1) hacia .game-rooms-page — sin esto,
   `min-height: 100%` de abajo no tiene una base % válida y la página
   queda más corta que el espacio disponible (ver comentario en
   .game-rooms-page). Selector acotado con :has() para no afectar otras
   páginas que reusan el mismo layout. */
#app:has(> .game-rooms-page),
.mt-2:has(> #app > .game-rooms-page) {
  height: 100%;
}
</style>

<style scoped>
.game-rooms-page,
.game-rooms-page *,
.game-rooms-page *::before,
.game-rooms-page *::after {
  box-sizing: border-box;
}

/* ═══════════════════════════════════════════════════════
   PÁGINA — fondo estelar directo (igual que Game/Index)
═══════════════════════════════════════════════════════ */
.game-rooms-page {
  flex: 1;
  /* El footer tiene altura variable en móvil (min-height: 0 !important en
     footer.blade.php bajo 576px, ver comentario ahí — el texto puede envolver
     en 2 líneas y superar los 44px "de referencia"). Calcular con
     calc(100vh - navbar - footer) asumía un footer fijo y se quedaba corto,
     generando ~9px de overflow-y en el contenedor scrollable padre.
     #content-scrollable ya es flex-grow-1 dentro de .wrapper (vh-100), así
     que mide el espacio real disponible: heredamos ese 100% en vez de
     recalcularlo con el viewport. */
  min-height: 100%;
  margin: -0.5rem -0.5rem 0;
  padding: 28px 12px 48px;
  position: relative;
  isolation: isolate;
  overflow-x: hidden;
  box-sizing: border-box;
  background: linear-gradient(160deg, #0a0f24 0%, #0c1230 45%, #060a18 100%);
}

@media (max-width: 380px) {
  .game-rooms-page { padding: 20px 8px 40px; }
}

/* ── Capa 1: estrellas finas — mayor densidad ── */
.game-rooms-page::before {
  content: "";
  position: absolute; inset: 0; z-index: -2; pointer-events: none;
  background-image:
    /* fila 1 */
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
    /* fila 2 */
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
    /* fila 3 */
    radial-gradient(1px 1px at  7% 88%, rgba(255,255,255,0.66) 0%, transparent 100%),
    radial-gradient(1px 1px at 18% 93%, rgba(255,255,255,0.44) 0%, transparent 100%),
    radial-gradient(1px 1px at 33% 87%, rgba(255,255,255,0.72) 0%, transparent 100%),
    radial-gradient(1px 1px at 48% 95%, rgba(255,255,255,0.58) 0%, transparent 100%),
    radial-gradient(1px 1px at 62% 90%, rgba(255,255,255,0.80) 0%, transparent 100%),
    radial-gradient(1px 1px at 78% 96%, rgba(255,255,255,0.46) 0%, transparent 100%),
    radial-gradient(1px 1px at 92% 89%, rgba(255,255,255,0.76) 0%, transparent 100%),
    /* estrellas coloreadas */
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

/* ── Capa 2: estrellas grandes con halo ── */
.game-rooms-page::after {
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
    /* grandes con halo */
    radial-gradient(3px 3px at 19% 20%, rgba(255,255,255,1.00) 0%, rgba(210,230,255,0.20) 55%, transparent 100%),
    radial-gradient(3px 3px at 44% 14%, rgba(220,200,255,0.96) 0%, rgba(185,165,255,0.18) 55%, transparent 100%),
    radial-gradient(3px 3px at 69% 42%, rgba(255,255,255,0.94) 0%, rgba(225,245,255,0.15) 55%, transparent 100%),
    radial-gradient(3px 3px at 88% 60%, rgba(200,240,255,0.98) 0%, rgba(160,220,255,0.15) 55%, transparent 100%),
    radial-gradient(3px 3px at 30% 78%, rgba(255,240,160,0.90) 0%, rgba(255,220,100,0.12) 55%, transparent 100%),
    /* especiales con halo amplio */
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

/* ── Botón flotante: reglamento del bingo ──
   position: absolute (no fixed) anclado a .game-rooms-page: el área de
   contenido tiene su propio scroll (#content-scrollable), independiente
   del viewport. Con fixed, el botón quedaba posicionado respecto a la
   ventana y terminaba solapado con el footer en vez de flotar dentro
   del contenido de la página. */
.grp-rules-fab {
  position: absolute;
  right: 20px;
  bottom: 24px;
  z-index: 50;
  width: 50px;
  height: 50px;
  border-radius: 50%;
  border: 1px solid rgba(123,79,255,0.35);
  background: linear-gradient(135deg, #2a1f5e 0%, #1a2a52 100%);
  color: #cdd8ff;
  font-size: 18px;
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

.grp-rules-fab:active {
  transform: translateY(0) scale(0.97);
}

@keyframes rulesFabPulse {
  0%, 100% { box-shadow: 0 4px 18px rgba(0,0,0,0.45), 0 0 0 0 rgba(123,79,255,0.45); }
  50%      { box-shadow: 0 4px 18px rgba(0,0,0,0.45), 0 0 0 8px rgba(123,79,255,0); }
}

@media (max-width: 576px) {
  .grp-rules-fab {
    right: 14px;
    bottom: 18px;
    width: 44px;
    height: 44px;
    font-size: 16px;
  }
}

/* ═══════════════════════════════
   HEADER
═══════════════════════════════ */
.game-rooms-page__header {
  text-align: center;
  margin-bottom: 40px;
  position: relative;
  animation: fadeSlideDown 0.7s ease both;
}

@media (max-width: 380px) {
  .game-rooms-page__header { margin-bottom: 26px; }
}

@keyframes fadeSlideDown {
  from { opacity: 0; transform: translateY(-20px); }
  to   { opacity: 1; transform: translateY(0); }
}

.grp-header-glow {
  position: absolute;
  top: -60px; left: 50%; transform: translateX(-50%);
  width: 500px; height: 200px;
  background: radial-gradient(ellipse, rgba(123,79,255,0.25) 0%, transparent 70%);
  pointer-events: none;
}

.grp-title-wrap {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16px;
  margin-bottom: 10px;
}

.grp-dice {
  font-size: 28px;
  filter: drop-shadow(0 0 10px rgba(42,245,152,0.6));
  animation: spinDice 8s linear infinite;
  color: #2af598;
  flex-shrink: 0;
}
.grp-dice--2 { animation-direction: reverse; animation-duration: 10s; color: #009efd; }

@media (max-width: 380px) {
  .grp-title-wrap { gap: 10px; }
  .grp-dice { font-size: 20px; }
}

@keyframes spinDice {
  0%   { transform: rotate(0deg);   }
  100% { transform: rotate(360deg); }
}

.game-rooms-page__title {
  font-family: "Anton", sans-serif;
  font-size: clamp(24px, 7vw, 44px);
  letter-spacing: 4px;
  color: #e0eaff;
  margin: 0;
  text-shadow:
    0 0 40px rgba(42,245,152,0.25),
    0 0 80px rgba(123,79,255,0.15),
    0 2px 4px rgba(0,0,0,0.5);
  background: linear-gradient(135deg, #e0eaff 0%, #a8c4ff 50%, #7b4fff 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.game-rooms-page__subtitle {
  font-size: 13px;
  color: #7a96c8;
  letter-spacing: 0.8px;
  margin: 0 0 16px;
  padding: 0 12px;
}

@media (max-width: 380px) {
  .game-rooms-page__title { letter-spacing: 2px; }
  .game-rooms-page__subtitle { font-size: 12px; }
}

.grp-header-line {
  width: 80px;
  height: 2px;
  margin: 0 auto;
  background: linear-gradient(90deg, transparent, #2af598, #009efd, transparent);
  border-radius: 2px;
  position: relative;
}
.grp-header-line::after {
  content: '';
  position: absolute;
  top: -1px; left: 50%; transform: translateX(-50%);
  width: 6px; height: 4px;
  background: #2af598;
  border-radius: 50%;
  box-shadow: 0 0 8px #2af598;
}

/* ═══════════════════════════════
   EMPTY STATE — compacto y minimalista
═══════════════════════════════ */
.game-rooms-page__empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  max-width: 320px;
  margin: 40px auto;
  padding: 28px 20px;
  border-radius: 20px;
  text-align: center;
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.08);
  animation: fadeSlideDown 0.5s ease both;
}

.grp-empty-icon {
  font-size: 26px;
  color: #4a6698;
  flex-shrink: 0;
}

.grp-empty-title {
  font-size: 13.5px;
  font-weight: 600;
  letter-spacing: 0.2px;
  color: #9fb3da;
  margin: 0;
}

/* ═══════════════════════════════
   GRID — cards horizontales responsive
═══════════════════════════════ */
.game-rooms-page__grid-wrap {
  width: 100%;
  max-width: 1180px;
  margin: 0 auto;
  padding: 0 4px;
}

.game-rooms-page__grid {
  display: grid;
  gap: 16px;
  /* minmax más ancho: la card horizontal aprovecha mejor 320-460px que 260px,
     y con pocas salas cada columna crece hasta el máximo del item en vez de
     quedar angosta con espacio vacío alrededor. */
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
}

.game-rooms-page__grid-item {
  display: flex;
  width: 100%;
  max-width: 460px;
}

/* Con pocas cards: centrar la fila */
.game-rooms-page__grid--centered {
  justify-content: center;
}
.game-rooms-page__grid--centered .game-rooms-page__grid-item {
  max-width: 460px;
}

@media (max-width: 380px) {
  .game-rooms-page__grid-wrap { padding: 0; }
  .game-rooms-page__grid { grid-template-columns: 1fr; gap: 10px; }
  .game-rooms-page__grid-item,
  .game-rooms-page__grid--centered .game-rooms-page__grid-item { max-width: none; }
}

/* estilos del modal de info movidos a BingoRoomInfoModal.vue */
</style>
