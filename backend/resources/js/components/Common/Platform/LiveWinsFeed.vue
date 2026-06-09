<script setup lang="ts">
import { TransitionGroup } from 'vue'
import { useLiveWins }    from '@/js/composables/platform/useLiveWins'

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
  <div class="lwf">
    <div class="lwf__header">
      <span class="lwf__dot"></span>
      <span class="lwf__title">Ganadores en vivo</span>
    </div>

    <div class="lwf__list" v-if="wins.length > 0">
      <TransitionGroup name="lwf-item" tag="div">
        <div
          v-for="win in wins"
          :key="win.id"
          class="lwf__item"
        >
          <!-- Thumbnail del juego -->
          <div class="lwf__thumb">
            <img
              v-if="gameImage(win.game_slug)"
              :src="gameImage(win.game_slug)!"
              :alt="win.game"
              class="lwf__thumb-img"
            />
            <div v-else class="lwf__thumb-icon">
              <i :class="gameIcon(win.game_slug)"></i>
            </div>
          </div>

          <!-- Info -->
          <div class="lwf__info">
            <div class="lwf__congrats">¡Felicidades!</div>
            <div class="lwf__winner">
              <span class="lwf__name">{{ maskName(win.winner_name) }}</span>
              <span class="lwf__sep">·</span>
              <span class="lwf__amount">{{ formatWinAmount(win) }}</span>
            </div>
            <div class="lwf__game">{{ win.game }}</div>
          </div>

          <!-- Trofeo -->
          <i class="fas fa-trophy lwf__trophy"></i>
        </div>
      </TransitionGroup>
    </div>

    <!-- Estado vacío: esperando primer ganador -->
    <div v-else class="lwf__empty">
      <i class="fas fa-hourglass-half lwf__empty-icon"></i>
      <span>Esperando ganadores…</span>
    </div>
  </div>
</template>

<style scoped>
.lwf {
  background: linear-gradient(160deg, #0d0829 0%, #0b0720 100%);
  border: 1px solid rgba(124, 92, 252, 0.2);
  border-radius: 12px;
  overflow: hidden;
  width: 100%;
}

/* ── Header ───────────────────────────────────────────── */
.lwf__header {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px 8px;
  border-bottom: 1px solid rgba(255,255,255,0.05);
}

.lwf__dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #22c55e;
  box-shadow: 0 0 6px #22c55e;
  animation: lwf-pulse 1.8s ease-in-out infinite;
  flex-shrink: 0;
}

@keyframes lwf-pulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50%       { opacity: 0.5; transform: scale(0.8); }
}

.lwf__title {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(255,255,255,0.5);
}

/* ── List ─────────────────────────────────────────────── */
.lwf__list { padding: 4px 0; }

/* ── Item ─────────────────────────────────────────────── */
.lwf__item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 14px;
  border-bottom: 1px solid rgba(255,255,255,0.04);
  transition: background 0.18s;
}
.lwf__item:last-child { border-bottom: none; }
.lwf__item:hover { background: rgba(124,92,252,0.07); }

/* ── Thumbnail ────────────────────────────────────────── */
.lwf__thumb {
  width: 44px;
  height: 44px;
  border-radius: 8px;
  overflow: hidden;
  flex-shrink: 0;
  background: rgba(255,255,255,0.05);
  display: flex;
  align-items: center;
  justify-content: center;
}
.lwf__thumb-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.lwf__thumb-icon {
  font-size: 20px;
  color: #7c5cfc;
}

/* ── Info ─────────────────────────────────────────────── */
.lwf__info {
  flex: 1;
  min-width: 0;
}
.lwf__congrats {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.05em;
  color: #7c5cfc;
  text-transform: uppercase;
  line-height: 1;
  margin-bottom: 2px;
}
.lwf__winner {
  display: flex;
  align-items: center;
  gap: 5px;
  flex-wrap: wrap;
}
.lwf__name {
  font-size: 13px;
  font-weight: 700;
  color: rgba(255,255,255,0.9);
}
.lwf__sep {
  color: rgba(255,255,255,0.2);
  font-size: 11px;
}
.lwf__amount {
  font-size: 13px;
  font-weight: 800;
  color: #22c55e;
}
.lwf__game {
  font-size: 11px;
  color: rgba(255,255,255,0.35);
  margin-top: 1px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* ── Trophy ───────────────────────────────────────────── */
.lwf__trophy {
  font-size: 14px;
  color: #fbbf24;
  flex-shrink: 0;
  opacity: 0.8;
}

/* ── Empty ────────────────────────────────────────────── */
.lwf__empty {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 24px 14px;
  font-size: 12px;
  color: rgba(255,255,255,0.25);
}
.lwf__empty-icon { font-size: 14px; }

/* ── Transition ───────────────────────────────────────── */
.lwf-item-enter-active {
  transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.lwf-item-enter-from {
  opacity: 0;
  transform: translateY(-12px) scale(0.97);
}
.lwf-item-leave-active {
  transition: all 0.2s ease;
  position: absolute;
}
.lwf-item-leave-to {
  opacity: 0;
  transform: translateY(8px);
}
.lwf-item-move {
  transition: transform 0.3s ease;
}
</style>
