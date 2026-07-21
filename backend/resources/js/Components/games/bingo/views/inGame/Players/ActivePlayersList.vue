<script setup lang="ts">
import type { ActivePlayer } from '@js/composables/games/bingo/game/useActivePlayers'

defineProps<{
  activePlayers: ActivePlayer[]
  loadingPlayers: boolean
  playersError: boolean
  userId?: string | null
}>()

defineEmits<{
  retry: []
}>()
</script>

<template>
  <div class="pm-section-label"><i class="fas fa-users" /> Jugadores</div>

  <!-- Cargando -->
  <div v-if="loadingPlayers" class="pm-empty">
    <span class="pm-spinner" /> Cargando jugadores…
  </div>

  <!-- Error -->
  <div v-else-if="playersError" class="pm-empty pm-empty--error">
    <i class="fas fa-exclamation-circle" />
    <span>Error al cargar</span>
    <button class="pm-retry-btn" @click="$emit('retry')">
      <i class="fas fa-redo" /> Reintentar
    </button>
  </div>

  <!-- Sin jugadores -->
  <div v-else-if="activePlayers.length === 0" class="pm-empty">
    <i class="fas fa-user-slash" />
    <span>Aún no hay jugadores registrados.</span>
  </div>

  <!-- Lista -->
  <div
    v-else
    v-for="(p, idx) in activePlayers"
    :key="p.user_id"
    class="pm-row"
    :class="{ 'pm-row--me': String(p.user_id) === String(userId) }"
  >
    <div class="pm-row__rank">
      <template v-if="idx === 0"><i class="fas fa-medal" style="color:#ffc200" /></template>
      <template v-else-if="idx === 1"><i class="fas fa-medal" style="color:#b0c4d8" /></template>
      <template v-else-if="idx === 2"><i class="fas fa-medal" style="color:#c87840" /></template>
      <template v-else>#{{ idx + 1 }}</template>
    </div>
    <div class="pm-row__info">
      <div class="pm-row__name">
        {{ p.name }}
        <span v-if="String(p.user_id) === String(userId)" class="pm-you">TÚ</span>
      </div>
    </div>
    <div class="pm-row__card-count">
      <i class="fas fa-ticket-alt" /> {{ p.cards_purchased }}
    </div>
  </div>
</template>

<style scoped>
.pm-section-label { font-size: 9px; font-weight: 700; color: #3a5080; text-transform: uppercase; letter-spacing: 1.2px; display: flex; align-items: center; gap: 5px; padding: 4px 0; }
.pm-section-label i { color: #ffd700; font-size: 8px; }

.pm-row {
  display: flex; align-items: center; gap: 8px;
  padding: 8px 10px; border-radius: 10px;
  background: rgba(255,255,255,0.025); border: 1px solid rgba(80,120,255,0.08);
  transition: background 0.2s;
}
.pm-row--me { background: rgba(42,245,152,0.06); border-color: rgba(42,245,152,0.2); }
.pm-row__rank { font-size: 10px; font-weight: 700; color: #4a68a8; width: 22px; flex-shrink: 0; }
.pm-row__info { flex: 1; min-width: 0; }
.pm-row__name { font-size: 12px; font-weight: 700; color: #c8d8ff; display: flex; align-items: center; gap: 5px; }
.pm-you { font-size: 8px; font-weight: 900; color: #2af598; background: rgba(42,245,152,0.12); border: 1px solid rgba(42,245,152,0.3); border-radius: 4px; padding: 1px 5px; letter-spacing: 0.5px; }

.pm-empty { display: flex; align-items: center; gap: 8px; padding: 20px 0; color: #3a5080; font-size: 12px; }
.pm-empty i { font-size: 14px; }
.pm-empty--error { color: #ff8a8a !important; flex-direction: column; gap: 8px !important; }
.pm-empty--error i { font-size: 20px; color: #ff4d6d; }

.pm-spinner {
  display: inline-block; width: 16px; height: 16px; flex-shrink: 0;
  border: 2px solid rgba(100,140,255,.2); border-top-color: #2af598;
  border-radius: 50%; animation: pmSpin .7s linear infinite;
}
@keyframes pmSpin { to { transform: rotate(360deg); } }

.pm-retry-btn {
  padding: 5px 14px; border-radius: 7px; font-size: 10px; font-weight: 700;
  cursor: pointer; display: inline-flex; align-items: center; gap: 5px;
  background: rgba(255,77,109,.1); border: 1px solid rgba(255,77,109,.28); color: #ff6e8a;
  transition: background .15s;
}
.pm-retry-btn:hover { background: rgba(255,77,109,.2); }

.pm-row__card-count {
  font-size: 10px; font-weight: 700; color: #4a6090; flex-shrink: 0;
  display: flex; align-items: center; gap: 4px;
}
.pm-row__card-count i { font-size: 8px; opacity: .7; }
</style>
