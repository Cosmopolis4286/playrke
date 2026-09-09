<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import BingoGameRoomCard from "@js/Components/games/bingo/Room/GameRooms/BingoGameRoomCard.vue";
import GameRoomsKpiHeader from "@js/Components/games/bingo/Room/GameRooms/GameRoomsKpiHeader.vue";
import GameRoomsFilterBar from "@js/Components/games/bingo/Room/GameRooms/GameRoomsFilterBar.vue";
import BingoRulesModal from "@js/Pages/Games/Bingo/Config/Rules/BingoRulesModal.vue";
import { useGameRoomCountdown } from "@js/composables/games/bingo/room/game-rooms/useGameRoomCountdown";
import { useGameRoomKpis } from "@js/composables/games/bingo/room/game-rooms/useGameRoomKpis";
import { useGameRoomEcho } from "@js/composables/games/bingo/room/game-rooms/useGameRoomEcho";

import type { BingoGameRoom } from "@js/types/games/bingo/room/game-rooms/bingo-game-room";
import type { GameRoomFilters } from "@js/types/games/bingo/room/game-rooms/bingo-game-room";

/**
 * Sección pública completa de "Salas activas" — header con dados/título con
 * gradiente/KPIs/filtros, grid de cards, empty state, FAB de reglamento y
 * fondo estelar opcional. Autocontenida: monta su propio realtime (Echo),
 * countdown y filtrado client-side a partir de las `rooms` iniciales.
 * Extraída de Landing.vue y GameRoomsShowcase.vue, que antes repetían este
 * bloque casi línea por línea con solo el copy y el fondo estelar distintos.
 */
const props = withDefaults(
  defineProps<{
    rooms: BingoGameRoom[];
    /** Timestamp del servidor (ms) para el countdown — evita drift de reloj cliente. */
    serverTimestampMs?: number;
    id?: string;
    title?: string;
    subtitle?: string;
    /** Reglamento del creador de sala — vacío en contextos públicos sin dueño único (BingoRulesModal ya muestra el genérico). */
    bingoRules?: string | null;
    imageBingoRules?: string | null;
    /** Fondo estelar propio (dos capas + isolation) — la Landing ya vive dentro de su propio fondo de marca; el Showcase, con fondo continuo, sí lo necesita. */
    starryBackground?: boolean;
  }>(),
  {
    serverTimestampMs: () => Date.now(),
    id: "salas",
    title: "Salas de Bingo activas",
    subtitle: "Cartones a la venta ahora mismo. El pozo crece con cada compra.",
    bingoRules: "",
    imageBingoRules: "",
    starryBackground: false,
  }
);

const emit = defineEmits<{
  select: [roomId: string];
  "show-top-buyers": [roomId: string];
}>();

// Copia reactiva propia: useGameRoomEcho la parchea in-place con los eventos
// públicos bingo.lobby/bingo.room.{id}, dándole realtime a cualquier página
// que use este componente sin que cada una tenga que orquestarlo.
const rooms = ref(props.rooms.map((r) => ({ ...r })));

const { init: initCountdown, getCountdown } = useGameRoomCountdown(props.serverTimestampMs);
onMounted(() => initCountdown(rooms.value));
useGameRoomEcho(rooms, { onRoomAdded: (room) => initCountdown([room]) });

// Filtro por variante — 100% client-side, ver justificación en el plan:
// ambos contextos públicos (Landing trunca a 3, Showcase trae el dataset
// completo pero de bajo volumen) parten del mismo repositorio sin
// paginación, así que un segundo endpoint de filtrado server-side no se
// justifica hoy.
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

const showRulesModal = ref(false);
</script>

<template>
  <section :id="id" class="game-rooms-section" :class="{ 'game-rooms-section--starry': starryBackground }">
    <button
      type="button"
      class="grp-rules-fab"
      title="Ver reglamento del Bingo"
      aria-label="Ver reglamento del Bingo"
      @click="showRulesModal = true"
    >
      <i class="fas fa-book" />
    </button>

    <div class="game-rooms-page__header">
      <div class="grp-header-glow" />
      <div class="grp-title-wrap">
        <span class="grp-dice grp-dice--1">&#9858;</span>
        <h2 class="game-rooms-page__title">{{ title }}</h2>
        <span class="grp-dice grp-dice--2">&#9858;</span>
      </div>
      <p class="game-rooms-page__subtitle">{{ subtitle }}</p>
      <div class="grp-header-line" />

      <div class="grp-toolbar">
        <div class="grp-toolbar__top">
          <GameRoomsKpiHeader :next-room-unix-date="nextRoomUnixDate" :total-active-rooms="totalActiveRooms" />
        </div>
        <GameRoomsFilterBar :filters="filters" :loading="false" @apply="() => {}" @reset="resetFilters" />
      </div>
    </div>

    <div v-if="!filteredRooms.length" class="game-rooms-page__empty">
      <i class="fas fa-door-closed grp-empty-icon" />
      <p class="grp-empty-title">No hay salas disponibles</p>
    </div>
    <div v-else class="grp-cards-grid">
      <BingoGameRoomCard
        v-for="(room, index) in filteredRooms"
        :key="room.id"
        :room="room"
        :exchange-rate="1"
        :currency-sym="'USD'"
        :countdown="getCountdown(room.id)"
        :index="index"
        @select="emit('select', room.id)"
        @show-top-buyers="emit('show-top-buyers', $event)"
      />
    </div>

    <slot name="after-grid" />
  </section>

  <BingoRulesModal v-model="showRulesModal" :rules="bingoRules" :image="imageBingoRules" />
</template>

<style scoped>
.game-rooms-section {
  position: relative;
}
.game-rooms-section--starry {
  isolation: isolate;
  overflow: hidden;
  padding: 32px 0 8px;
}

/* ── Fondo estelar — dos capas, mismas usadas en Pages/Games/Bingo/Room/GameRooms.vue ── */
.game-rooms-section--starry::before {
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
  animation: game-rooms-section-twinkle-a 7s ease-in-out infinite alternate;
}
.game-rooms-section--starry::after {
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
  animation: game-rooms-section-twinkle-b 9s ease-in-out infinite alternate-reverse;
}
@keyframes game-rooms-section-twinkle-a {
  0%   { opacity: 0.55; } 40%  { opacity: 0.95; }
  70%  { opacity: 0.70; } 100% { opacity: 0.85; }
}
@keyframes game-rooms-section-twinkle-b {
  0%   { opacity: 0.70; } 35%  { opacity: 0.50; }
  65%  { opacity: 1.00; } 100% { opacity: 0.65; }
}

/* ── FAB reglamento — absolute (no fixed) anclado a .game-rooms-section:
   antes flotaba sobre TODA la página (fixed = relativo al viewport), así
   que seguía visible aunque el usuario hubiera scrolleado a otras secciones
   (Invita y gana, footer, etc.). Con absolute queda acotado a los límites
   de esta sección, visible solo mientras ella ocupa la pantalla. ── */
.grp-rules-fab {
  position: absolute;
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
  font-size: 22px;
  filter: drop-shadow(0 0 8px rgba(42,245,152,0.6));
  animation: spinDice 8s linear infinite;
  color: #2af598;
  flex-shrink: 0;
}
.grp-dice--2 { animation-direction: reverse; animation-duration: 10s; color: #009efd; }
@keyframes spinDice { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

.game-rooms-page__title {
  font-family: "Orbitron", "Anton", sans-serif;
  font-size: clamp(22px, 3.4vw, 32px);
  font-weight: 800;
  letter-spacing: 1px;
  margin: 0;
  text-shadow: 0 0 30px rgba(42,245,152,0.22), 0 0 60px rgba(123,79,255,0.12), 0 2px 4px rgba(0,0,0,0.5);
  background: linear-gradient(135deg, #e0eaff 0%, #a8c4ff 50%, #7b4fff 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
.game-rooms-page__subtitle { font-size: 13.5px; color: #7a96c8; margin: 6px 0 14px; }

.grp-header-line {
  width: 60px; height: 2px; margin: 0 auto;
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

.grp-toolbar { display: flex; flex-direction: column; align-items: center; gap: 10px; margin: 18px auto 0; max-width: 620px; position: relative; z-index: 30; }
.grp-toolbar__top { display: flex; align-items: center; justify-content: center; gap: 8px; }

/* ── Empty state ── */
.game-rooms-page__empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  max-width: 320px;
  margin: 24px auto;
  padding: 28px 20px;
  border-radius: 20px;
  text-align: center;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.08);
}
.grp-empty-icon { font-size: 24px; color: #4a6698; }
.grp-empty-title { font-size: 13.5px; font-weight: 600; color: #9fb3da; margin: 0; }

/* ── Grid ── */
.grp-cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 220px));
  justify-content: center;
  gap: 18px;
  max-width: 1180px;
  margin: 0 auto;
  padding: 0 12px;
}

@media (max-width: 640px) {
  .grp-cards-grid { grid-template-columns: repeat(2, minmax(150px, 1fr)); }
}

@media (max-width: 380px) {
  .grp-cards-grid { grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 10px; }
  .grp-rules-fab { right: 12px; bottom: 14px; width: 34px; height: 34px; font-size: 12px; }
}

@media (prefers-reduced-motion: reduce) {
  .game-rooms-section--starry::before,
  .game-rooms-section--starry::after { animation: none; }
  .grp-dice { animation: none; }
  .grp-rules-fab { animation: none; }
}
</style>
