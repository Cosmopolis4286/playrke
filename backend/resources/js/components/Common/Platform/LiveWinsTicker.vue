<script setup lang="ts">
import { ref, watchEffect, nextTick, onBeforeUnmount } from 'vue'
import { useLiveWins } from '@/js/composables/platform/useLiveWins'

const GAME_IMAGES: Record<string, string> = {
  bingo: '/img/games/bingo/estelar.png',
}

/**
 * Iconos de respaldo cuando no hay imagen en GAME_IMAGES. bingo y trivia
 * emiten LiveWinEvent realmente (ver BingoDrawService y PrizeDistributionService);
 * blackjack y plinko quedan listados para cuando esos motores de juego existan.
 */
const GAME_ICONS: Record<string, string> = {
  bingo:     'fas fa-dice',
  trivia:    'fas fa-question-circle',
  blackjack: 'fas fa-playing-card',
  plinko:    'fas fa-circle-dot',
}

const COLLAPSE_STORAGE_KEY = 'lwt-collapsed'

function gameImage(slug: string): string | null {
  return GAME_IMAGES[slug] ?? null
}

function gameIcon(slug: string): string {
  return GAME_ICONS[slug] ?? 'fas fa-gamepad'
}

const { wins, formatWinAmount, maskName } = useLiveWins()

const collapsed = ref(localStorage.getItem(COLLAPSE_STORAGE_KEY) === '1')
const panelEl = ref<HTMLElement | null>(null)

/**
 * En vez de depender de una clase estática en <body> (que puede desincronizarse
 * con caché de CSS o builds desfasados), se mide la altura real del panel y se
 * empuja el contenido con esa medida exacta vía CSS custom property. Si el panel
 * no está visible, la variable es '0px' y no se reserva ningún espacio.
 */
function syncReservedSpace(): void {
  const height = panelEl.value?.offsetHeight ?? 0
  document.documentElement.style.setProperty('--lwt-height', `${height}px`)
}

watchEffect(() => {
  // Se re-ejecuta cuando cambian wins/collapsed; nextTick asegura que el DOM
  // ya refleje el v-if antes de medir.
  void wins.value.length
  void collapsed.value
  nextTick(syncReservedSpace)
})

onBeforeUnmount(() => {
  document.documentElement.style.setProperty('--lwt-height', '0px')
})

function collapse(): void {
  collapsed.value = true
  localStorage.setItem(COLLAPSE_STORAGE_KEY, '1')
}

function expand(): void {
  collapsed.value = false
  localStorage.removeItem(COLLAPSE_STORAGE_KEY)
}
</script>

<template>
  <Transition name="lwt-fade">
  <div ref="panelEl" class="lwt" v-if="wins.length > 0 && !collapsed" :key="'panel'">
    <div class="lwt__label">
      <span class="lwt__dot"></span>
      <span class="lwt__label-text">En vivo</span>
    </div>

    <div class="lwt__track-wrap">
      <div class="lwt__track" :class="{ 'lwt__track--scroll': wins.length > 2 }">
        <div
          v-for="win in wins"
          :key="win.id"
          class="lwt__item"
        >
          <div class="lwt__thumb">
            <img
              v-if="gameImage(win.game_slug)"
              :src="gameImage(win.game_slug)!"
              :alt="win.game"
              class="lwt__thumb-img"
            />
            <i v-else :class="gameIcon(win.game_slug)" class="lwt__thumb-icon"></i>
          </div>
          <i class="fas fa-trophy lwt__trophy"></i>
          <span class="lwt__text">
            <strong>{{ maskName(win.winner_name) }}</strong> ganó
            <strong class="lwt__amount">{{ formatWinAmount(win) }}</strong>
            en {{ win.game }}
          </span>
        </div>

        <!-- Duplicado para loop continuo -->
        <template v-if="wins.length > 2">
          <div
            v-for="win in wins"
            :key="`dup-${win.id}`"
            class="lwt__item"
            aria-hidden="true"
          >
            <div class="lwt__thumb">
              <img
                v-if="gameImage(win.game_slug)"
                :src="gameImage(win.game_slug)!"
                :alt="win.game"
                class="lwt__thumb-img"
              />
              <i v-else :class="gameIcon(win.game_slug)" class="lwt__thumb-icon"></i>
            </div>
            <i class="fas fa-trophy lwt__trophy"></i>
            <span class="lwt__text">
              <strong>{{ maskName(win.winner_name) }}</strong> ganó
              <strong class="lwt__amount">{{ formatWinAmount(win) }}</strong>
              en {{ win.game }}
            </span>
          </div>
        </template>
      </div>
    </div>

    <button type="button" class="lwt__close" title="Ocultar ticker" aria-label="Ocultar ticker de ganadores" @click="collapse">
      <i class="fas fa-times"></i>
    </button>
  </div>

  <button
    v-else-if="wins.length > 0 && collapsed"
    type="button"
    class="lwt-reopen"
    title="Mostrar ganadores en vivo"
    aria-label="Mostrar ganadores en vivo"
    :key="'reopen'"
    @click="expand"
  >
    <span class="lwt-reopen__dot"></span>
    <i class="fas fa-trophy"></i>
  </button>
  </Transition>
</template>

<style scoped>
.lwt {
  display: flex;
  align-items: center;
  gap: 12px;
  /* El primer color coincide con el borde del degradado del sidebar para que no se note la unión */
  background: linear-gradient(90deg, #181b50 0%, #0d0829 8%, #120a38 50%, #0d0829 100%);
  border-bottom: 1px solid rgba(124, 92, 252, 0.18);
  padding: 6px 14px;
  overflow: hidden;
  width: 100%;
  box-sizing: border-box;
}

/* ── Label ────────────────────────────────────────────── */
.lwt__label {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-shrink: 0;
  padding-right: 10px;
  border-right: 1px solid rgba(255,255,255,0.08);
}

.lwt__dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #22c55e;
  box-shadow: 0 0 6px #22c55e;
  animation: lwt-pulse 1.8s ease-in-out infinite;
  flex-shrink: 0;
}

@keyframes lwt-pulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50%       { opacity: 0.5; transform: scale(0.8); }
}

.lwt__label-text {
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: rgba(255,255,255,0.45);
  white-space: nowrap;
}

/* ── Track ────────────────────────────────────────────── */
.lwt__track-wrap {
  flex: 1;
  overflow: hidden;
  min-width: 0;
}

.lwt__track {
  display: flex;
  align-items: center;
  gap: 28px;
  width: max-content;
}

.lwt__track--scroll {
  animation: lwt-scroll 30s linear infinite;
}

.lwt__track--scroll:hover {
  animation-play-state: paused;
}

@keyframes lwt-scroll {
  from { transform: translateX(0); }
  to   { transform: translateX(-50%); }
}

/* ── Item ─────────────────────────────────────────────── */
.lwt__item {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
  white-space: nowrap;
}

.lwt__thumb {
  width: 22px;
  height: 22px;
  border-radius: 6px;
  overflow: hidden;
  flex-shrink: 0;
  background: rgba(255,255,255,0.06);
  display: flex;
  align-items: center;
  justify-content: center;
}
.lwt__thumb-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.lwt__thumb-icon {
  font-size: 11px;
  color: #7c5cfc;
}

.lwt__trophy {
  font-size: 11px;
  color: #fbbf24;
  flex-shrink: 0;
}

.lwt__text {
  font-size: 12px;
  color: rgba(255,255,255,0.55);
}
.lwt__text strong {
  color: rgba(255,255,255,0.85);
  font-weight: 700;
}
.lwt__amount {
  color: #22c55e !important;
}

@media (max-width: 576px) {
  .lwt__label-text { display: none; }
  .lwt__label { padding-right: 6px; }
}

/* ── Botón cerrar ─────────────────────────────────────── */
.lwt__close {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  width: 22px;
  height: 22px;
  border: none;
  border-radius: 6px;
  background: rgba(255,255,255,0.06);
  color: rgba(255,255,255,0.45);
  font-size: 11px;
  cursor: pointer;
  transition: background .15s, color .15s;
}
.lwt__close:hover {
  background: rgba(255,255,255,0.12);
  color: rgba(255,255,255,0.85);
}

/* ── Pestaña para volver a mostrar el ticker ─────────────── */
.lwt-reopen {
  position: fixed;
  top: 54px;
  right: 14px;
  z-index: 1027;
  display: flex;
  align-items: center;
  gap: 6px;
  border: 1px solid rgba(124, 92, 252, 0.25);
  border-top: none;
  border-radius: 0 0 8px 8px;
  background: linear-gradient(90deg, #0d0829 0%, #120a38 50%, #0d0829 100%);
  color: #fbbf24;
  font-size: 11px;
  padding: 4px 10px;
  cursor: pointer;
}
.lwt-reopen__dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #22c55e;
  box-shadow: 0 0 6px #22c55e;
  animation: lwt-pulse 1.8s ease-in-out infinite;
}

/* ── Transición de entrada/salida del ticker ─────────────── */
.lwt-fade-enter-active,
.lwt-fade-leave-active {
  transition: opacity .25s ease, transform .25s ease;
}
.lwt-fade-enter-from,
.lwt-fade-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}
</style>
