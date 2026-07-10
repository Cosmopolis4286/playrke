<script setup lang="ts">
import { ref, watchEffect, nextTick, onBeforeUnmount } from 'vue'
import { usePlatformWinEvents } from '@/js/composables/platform/usePlatformWinEvents'

/**
 * Iconos de respaldo por juego cuando no hay imagen dedicada en GAME_IMAGES.
 * Cualquier game_slug nuevo cae automáticamente al ícono genérico.
 */
const GAME_IMAGES: Record<string, string> = {
  bingo: '/img/games/bingo/estelar.png',
}

const GAME_ICONS: Record<string, string> = {
  bingo:   'fas fa-dice',
  trivia:  'fas fa-question-circle',
}

const PRIZE_KIND_ICONS: Record<string, string> = {
  money:   'fas fa-trophy',
  item:    'fas fa-gift',
  service: 'fas fa-concierge-bell',
}

const COLLAPSE_STORAGE_KEY = 'pwt-collapsed'

function gameImage(slug: string): string | null {
  return GAME_IMAGES[slug] ?? null
}

function gameIcon(slug: string): string {
  return GAME_ICONS[slug] ?? 'fas fa-gamepad'
}

function prizeIcon(kind: string): string {
  return PRIZE_KIND_ICONS[kind] ?? 'fas fa-trophy'
}

const { events, formatPrize, maskName } = usePlatformWinEvents()

const collapsed = ref(localStorage.getItem(COLLAPSE_STORAGE_KEY) === '1')
const panelEl = ref<HTMLElement | null>(null)

/**
 * Mide la altura real del panel y empuja el contenido con esa medida exacta
 * vía CSS custom property. Si el panel no está visible, la variable es '0px'
 * y no se reserva ningún espacio.
 */
function syncReservedSpace(): void {
  const height = panelEl.value?.offsetHeight ?? 0
  document.documentElement.style.setProperty('--pwt-height', `${height}px`)
}

watchEffect(() => {
  void events.value.length
  void collapsed.value
  nextTick(syncReservedSpace)
})

onBeforeUnmount(() => {
  document.documentElement.style.setProperty('--pwt-height', '0px')
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
  <Transition name="pwt-fade">
  <div ref="panelEl" class="pwt" v-if="events.length > 0 && !collapsed" :key="'panel'">
    <div class="pwt__label">
      <span class="pwt__dot"></span>
      <span class="pwt__label-text">En vivo</span>
    </div>

    <div class="pwt__track-wrap">
      <div class="pwt__track" :class="{ 'pwt__track--scroll': events.length > 2 }">
        <div
          v-for="e in events"
          :key="e.id"
          class="pwt__item"
        >
          <div class="pwt__thumb">
            <img
              v-if="gameImage(e.game_slug)"
              :src="gameImage(e.game_slug)!"
              :alt="e.game"
              class="pwt__thumb-img"
            />
            <i v-else :class="gameIcon(e.game_slug)" class="pwt__thumb-icon"></i>
          </div>
          <i :class="prizeIcon(e.prize_kind)" class="pwt__trophy"></i>
          <span class="pwt__text">
            <strong>{{ maskName(e.winner_name) }}</strong> ganó
            <strong class="pwt__amount">{{ formatPrize(e) }}</strong>
            en {{ e.game }}
          </span>
        </div>

        <!-- Duplicado para loop continuo -->
        <template v-if="events.length > 2">
          <div
            v-for="e in events"
            :key="`dup-${e.id}`"
            class="pwt__item"
            aria-hidden="true"
          >
            <div class="pwt__thumb">
              <img
                v-if="gameImage(e.game_slug)"
                :src="gameImage(e.game_slug)!"
                :alt="e.game"
                class="pwt__thumb-img"
              />
              <i v-else :class="gameIcon(e.game_slug)" class="pwt__thumb-icon"></i>
            </div>
            <i :class="prizeIcon(e.prize_kind)" class="pwt__trophy"></i>
            <span class="pwt__text">
              <strong>{{ maskName(e.winner_name) }}</strong> ganó
              <strong class="pwt__amount">{{ formatPrize(e) }}</strong>
              en {{ e.game }}
            </span>
          </div>
        </template>
      </div>
    </div>

    <button type="button" class="pwt__close" title="Ocultar ticker" aria-label="Ocultar ticker de ganadores" @click="collapse">
      <i class="fas fa-times"></i>
    </button>
  </div>

  <button
    v-else-if="events.length > 0 && collapsed"
    type="button"
    class="pwt-reopen"
    title="Mostrar ganadores en vivo"
    aria-label="Mostrar ganadores en vivo"
    :key="'reopen'"
    @click="expand"
  >
    <span class="pwt-reopen__dot"></span>
    <i class="fas fa-trophy"></i>
  </button>
  </Transition>
</template>

<style scoped>
.pwt {
  display: flex;
  align-items: center;
  gap: 12px;
  background: linear-gradient(90deg, #181b50 0%, #0d0829 8%, #120a38 50%, #0d0829 100%);
  border-bottom: 1px solid rgba(124, 92, 252, 0.18);
  padding: 6px 14px;
  overflow: hidden;
  width: 100%;
  box-sizing: border-box;
}

/* ── Label ────────────────────────────────────────────── */
.pwt__label {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-shrink: 0;
  padding-right: 10px;
  border-right: 1px solid rgba(255,255,255,0.08);
}

.pwt__dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #22c55e;
  box-shadow: 0 0 6px #22c55e;
  animation: pwt-pulse 1.8s ease-in-out infinite;
  flex-shrink: 0;
}

@keyframes pwt-pulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50%       { opacity: 0.5; transform: scale(0.8); }
}

.pwt__label-text {
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: rgba(255,255,255,0.45);
  white-space: nowrap;
}

/* ── Track ────────────────────────────────────────────── */
.pwt__track-wrap {
  flex: 1;
  overflow: hidden;
  min-width: 0;
}

.pwt__track {
  display: flex;
  align-items: center;
  gap: 28px;
  width: max-content;
}

.pwt__track--scroll {
  animation: pwt-scroll 30s linear infinite;
}

.pwt__track--scroll:hover {
  animation-play-state: paused;
}

@keyframes pwt-scroll {
  from { transform: translateX(0); }
  to   { transform: translateX(-50%); }
}

/* ── Item ─────────────────────────────────────────────── */
.pwt__item {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
  white-space: nowrap;
}

.pwt__thumb {
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
.pwt__thumb-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.pwt__thumb-icon {
  font-size: 11px;
  color: #7c5cfc;
}

.pwt__trophy {
  font-size: 11px;
  color: #fbbf24;
  flex-shrink: 0;
}

.pwt__text {
  font-size: 12px;
  color: rgba(255,255,255,0.55);
}
.pwt__text strong {
  color: rgba(255,255,255,0.85);
  font-weight: 700;
}
.pwt__amount {
  color: #22c55e !important;
}

@media (max-width: 576px) {
  .pwt__label-text { display: none; }
  .pwt__label { padding-right: 6px; }
}

/* ── Botón cerrar ─────────────────────────────────────── */
.pwt__close {
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
.pwt__close:hover {
  background: rgba(255,255,255,0.12);
  color: rgba(255,255,255,0.85);
}

/* ── Pestaña para volver a mostrar el ticker ─────────────── */
.pwt-reopen {
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
.pwt-reopen__dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #22c55e;
  box-shadow: 0 0 6px #22c55e;
  animation: pwt-pulse 1.8s ease-in-out infinite;
}

/* ── Transición de entrada/salida del ticker ─────────────── */
.pwt-fade-enter-active,
.pwt-fade-leave-active {
  transition: opacity .25s ease, transform .25s ease;
}
.pwt-fade-enter-from,
.pwt-fade-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}
</style>
