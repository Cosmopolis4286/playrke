<template>
  <div class="ep-row" :class="`is-${player.status.toLowerCase()}`">
    <div class="ep-row-main">
      <span class="badge ep-status-badge" :class="statusBadgeClass">
        {{ statusLabel }}
      </span>
      <div class="ep-row-info">
        <span class="ep-name">{{ player.first_name }} {{ player.last_name }}</span>
        <span class="ep-meta">
          <i class="fas fa-phone-alt mr-1"></i>{{ player.phone }}
          <template v-if="player.identification">
            <span class="mx-1">·</span><i class="fas fa-id-card mr-1"></i>{{ player.identification }}
          </template>
        </span>
      </div>
    </div>

    <div class="ep-row-extra">
      <span v-if="player.game" class="ep-tag"><i class="fas fa-dice mr-1"></i>{{ player.game.name }}</span>
      <span v-if="player.seller_user" class="ep-tag">
        <i class="fas fa-user-tie mr-1"></i>{{ player.seller_user.first_name }} {{ player.seller_user.last_name }}
      </span>
      <span v-if="player.country" class="ep-tag"><i class="fas fa-globe-americas mr-1"></i>{{ player.country.name }}</span>
      <span v-if="player.expiration_date" class="ep-tag" :title="'Expira: ' + player.expiration_date">
        <i class="fas fa-clock mr-1"></i>{{ formattedExpiration }}
      </span>
    </div>

    <button
      class="btn btn-sm"
      :class="player.status === 'BLOCKED' ? 'btn-outline-success' : 'btn-outline-danger'"
      :disabled="busy"
      @click="$emit('toggle-block', player)"
    >
      <i class="fas" :class="player.status === 'BLOCKED' ? 'fa-unlock' : 'fa-ban'"></i>
      {{ player.status === 'BLOCKED' ? 'Desbloquear' : 'Bloquear' }}
    </button>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { ExternalPlayer } from '@/js/types/roles/admin/external-player'

const props = defineProps<{
  player: ExternalPlayer
  busy: boolean
}>()

defineEmits<{
  (e: 'toggle-block', player: ExternalPlayer): void
}>()

const STATUS_LABELS: Record<ExternalPlayer['status'], string> = {
  ACTIVE: 'Activo',
  INACTIVE: 'Inactivo',
  BLOCKED: 'Bloqueado',
}

const STATUS_BADGE_CLASSES: Record<ExternalPlayer['status'], string> = {
  ACTIVE: 'badge-success',
  INACTIVE: 'badge-secondary',
  BLOCKED: 'badge-danger',
}

const statusLabel = computed(() => STATUS_LABELS[props.player.status])
const statusBadgeClass = computed(() => STATUS_BADGE_CLASSES[props.player.status])

const formattedExpiration = computed(() => {
  if (!props.player.expiration_date) return ''
  return new Date(props.player.expiration_date).toLocaleDateString('es-VE', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  })
})
</script>

<style scoped>
.ep-row {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.65rem 0.9rem;
  border: 1px solid var(--ds-border);
  border-radius: 10px;
  background: var(--ds-surface);
  flex-wrap: wrap;
}
.ep-row.is-blocked { border-color: rgba(239, 68, 68, 0.35); background: rgba(239, 68, 68, 0.04); }
.ep-row.is-inactive { opacity: 0.65; }

.ep-row-main { display: flex; align-items: center; gap: 0.6rem; flex: 1 1 260px; min-width: 0; }
.ep-status-badge { font-size: 0.7rem; padding: 0.3rem 0.55rem; flex-shrink: 0; }
.ep-row-info { display: flex; flex-direction: column; gap: 0.1rem; min-width: 0; }
.ep-name { font-size: 0.87rem; font-weight: 600; color: var(--ds-text); }
.ep-meta { font-size: 0.76rem; color: var(--ds-text-muted); }

.ep-row-extra { display: flex; flex-wrap: wrap; gap: 0.4rem; flex: 1 1 260px; }
.ep-tag {
  font-size: 0.72rem;
  color: var(--ds-text-2);
  background: var(--ds-surface-2);
  border-radius: 999px;
  padding: 0.2rem 0.55rem;
  white-space: nowrap;
}

.badge-success   { background-color: rgba(52,211,153,.18); color: #34d399; }
.badge-secondary { background-color: var(--ds-surface-2);  color: var(--ds-text-2); }
.badge-danger    { background-color: rgba(239,68,68,.18);  color: #ef4444; }

@media (max-width: 768px) {
  .ep-row { flex-direction: column; align-items: stretch; }
}
</style>
