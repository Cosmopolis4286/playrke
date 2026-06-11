<script setup lang="ts">
import { useLiveWins } from '@/js/composables/platform/useLiveWins'

const GAME_IMAGES: Record<string, string> = {
  bingo: '/img/games/bingo/estelar.png',
}

const GAME_ICONS: Record<string, string> = {
  bingo:     'fas fa-dice',
  blackjack: 'fas fa-playing-card',
  plinko:    'fas fa-circle-dot',
  trivia:    'fas fa-question-circle',
}

function gameImage(slug: string): string | null {
  return GAME_IMAGES[slug] ?? null
}

function gameIcon(slug: string): string {
  return GAME_ICONS[slug] ?? 'fas fa-gamepad'
}

const { wins, formatWinAmount, maskName } = useLiveWins()
</script>

<template>
  <div class="lwt" v-if="wins.length > 0">
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
  </div>
</template>

<style scoped>
.lwt {
  display: flex;
  align-items: center;
  gap: 12px;
  background: linear-gradient(90deg, #0d0829 0%, #120a38 50%, #0d0829 100%);
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
</style>
