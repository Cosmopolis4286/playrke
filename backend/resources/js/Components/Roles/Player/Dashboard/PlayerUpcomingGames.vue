<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue'
import type { PlayerUpcomingGame } from '@/js/types/roles/player/dashboard/dashboard-player'
import DashboardEmptyState from '@/js/Components/Common/Dashboard/DashboardEmptyState.vue'

defineProps<{
  games: PlayerUpcomingGame[]
}>()

// Reloj de 30 s para la cuenta regresiva sin recargar la página.
const now = ref(Date.now())
let timer: ReturnType<typeof setInterval> | undefined
onMounted(() => { timer = setInterval(() => { now.value = Date.now() }, 30_000) })
onBeforeUnmount(() => { if (timer) clearInterval(timer) })

function whenLabel(game: PlayerUpcomingGame): string {
  if (game.in_progress) return 'En juego'
  if (game.is_manual || !game.starts_at) return 'Inicio manual'
  const minutes = Math.floor((new Date(game.starts_at).getTime() - now.value) / 60_000)
  if (minutes <= 0) return 'Por empezar'
  if (minutes < 60) return `En ${minutes} min`
  if (minutes < 1440) return `En ${Math.floor(minutes / 60)} h ${minutes % 60} min`
  return new Date(game.starts_at).toLocaleString('es', { weekday: 'short', hour: '2-digit', minute: '2-digit' })
}
</script>

<template>
  <div v-if="games.length" class="pug-list">
    <a v-for="game in games" :key="game.id" :href="game.href" class="pug-item" :class="{ 'pug-item--live': game.in_progress }">
      <span class="pug-item__when">
        <span v-if="game.in_progress" class="pug-item__dot"></span>
        {{ whenLabel(game) }}
      </span>
      <span class="pug-item__body">
        <span class="pug-item__name">{{ game.uuid_code }}</span>
        <span class="pug-item__meta">
          {{ game.cards }} cartón(es) · {{ game.total_players }} jugadores · Bote US$ {{ game.prize_pool_usd.toFixed(2) }}
        </span>
      </span>
      <span class="pug-item__cta">{{ game.in_progress ? 'Jugar' : 'Entrar' }}</span>
    </a>
  </div>
  <DashboardEmptyState
    v-else
    icon="fas fa-ticket-alt"
    title="No tienes partidas próximas"
    description="Compra cartones en una sala para verla aquí."
    compact
  />
</template>

<style scoped>
.pug-list { display: flex; flex-direction: column; gap: 0.5rem; }

.pug-item {
  display: flex; align-items: center; gap: 0.75rem;
  padding: 0.6rem 0.875rem; border-radius: 10px;
  background: var(--ds-surface-2); border-left: 3px solid transparent;
  text-decoration: none; color: inherit; transition: box-shadow 0.15s;
}
.pug-item:hover { box-shadow: var(--ds-card-hover-shadow); text-decoration: none; }
.pug-item--live { border-left-color: #22c55e; }

.pug-item__when {
  min-width: 96px; flex-shrink: 0; display: inline-flex; align-items: center; gap: 5px;
  font-size: 0.75rem; font-weight: 800; color: var(--ds-text);
}
.pug-item__dot {
  width: 7px; height: 7px; border-radius: 50%; background: #22c55e; box-shadow: 0 0 5px #22c55e;
  animation: pugPulse 1.8s ease-in-out infinite;
}
@keyframes pugPulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }

.pug-item__body { display: flex; flex-direction: column; min-width: 0; flex: 1; }
.pug-item__name { font-family: monospace; font-size: 0.8rem; color: var(--ds-text); }
.pug-item__meta { font-size: 0.72rem; color: var(--ds-text-muted); }

.pug-item__cta {
  flex-shrink: 0; font-size: 0.75rem; font-weight: 700;
  padding: 5px 12px; border-radius: 8px; background: #6366f1; color: #fff;
}
.pug-item--live .pug-item__cta { background: #16a34a; }
</style>
