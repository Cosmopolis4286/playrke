<template>
  <div class="ep-panel">
    <!-- Estadísticas -->
    <div class="ep-stats">
      <div class="ep-stat">
        <span class="ep-stat-value">{{ statistics.total }}</span>
        <span class="ep-stat-label">Total</span>
      </div>
      <div class="ep-stat ep-stat--active">
        <span class="ep-stat-value">{{ statistics.active }}</span>
        <span class="ep-stat-label">Activos</span>
      </div>
      <div class="ep-stat ep-stat--inactive">
        <span class="ep-stat-value">{{ statistics.inactive }}</span>
        <span class="ep-stat-label">Inactivos</span>
      </div>
      <div class="ep-stat ep-stat--blocked">
        <span class="ep-stat-value">{{ statistics.blocked }}</span>
        <span class="ep-stat-label">Bloqueados</span>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="ep-toolbar">
      <div class="ep-search">
        <i class="fas fa-search"></i>
        <input
          v-model="search"
          type="text"
          class="form-control"
          placeholder="Buscar por nombre, teléfono, identificación o link..."
          @keydown.enter="$emit('search', { search, status })"
        />
      </div>
      <select v-model="status" class="form-control ep-status-filter" @change="$emit('search', { search, status })">
        <option value="">Todos los estados</option>
        <option value="ACTIVE">Activos</option>
        <option value="INACTIVE">Inactivos</option>
        <option value="BLOCKED">Bloqueados</option>
      </select>
      <button class="btn btn-outline-primary" :disabled="loading" @click="$emit('search', { search, status })">
        <i class="fas fa-sync-alt mr-2" :class="{ 'fa-spin': loading }"></i>Buscar
      </button>
    </div>

    <!-- Lista -->
    <div v-if="!players.length" class="ep-empty">
      <i class="fas fa-user-friends"></i>
      <p class="mb-0">No se encontraron jugadores externos.</p>
    </div>

    <div v-else class="ep-list">
      <div v-for="player in players" :key="player.id" class="ep-row" :class="`is-${player.status.toLowerCase()}`">
        <div class="ep-row-main">
          <span class="badge ep-status-badge" :class="statusBadgeClass(player.status)">
            {{ statusLabel(player.status) }}
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
          <span v-if="player.seller_user" class="ep-tag"><i class="fas fa-user-tie mr-1"></i>{{ player.seller_user.first_name }} {{ player.seller_user.last_name }}</span>
          <span v-if="player.country" class="ep-tag"><i class="fas fa-globe-americas mr-1"></i>{{ player.country.name }}</span>
          <span v-if="player.expiration_date" class="ep-tag" :title="'Expira: ' + player.expiration_date">
            <i class="fas fa-clock mr-1"></i>{{ formatExpiration(player.expiration_date) }}
          </span>
        </div>

        <button
          class="btn btn-sm"
          :class="player.status === 'BLOCKED' ? 'btn-outline-success' : 'btn-outline-danger'"
          :disabled="busyId === player.id"
          @click="handleToggleBlock(player)"
        >
          <i class="fas" :class="player.status === 'BLOCKED' ? 'fa-unlock' : 'fa-ban'"></i>
          {{ player.status === 'BLOCKED' ? 'Desbloquear' : 'Bloquear' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import Swal from 'sweetalert2'
import type { ExternalPlayer, ExternalPlayerStatistics } from '@/js/types/roles/admin/external-player'

const props = defineProps<{
  players: ExternalPlayer[]
  statistics: ExternalPlayerStatistics
  loading: boolean
}>()

const emit = defineEmits<{
  (e: 'search', filters: { search: string; status: string }): void
  (e: 'toggle-block', playerId: string): void
}>()

const search = ref('')
const status = ref('')
const busyId = ref<string | null>(null)

function statusLabel(status: ExternalPlayer['status']): string {
  const map = { ACTIVE: 'Activo', INACTIVE: 'Inactivo', BLOCKED: 'Bloqueado' }
  return map[status] ?? status
}

function statusBadgeClass(status: ExternalPlayer['status']): string {
  const map = { ACTIVE: 'badge-success', INACTIVE: 'badge-secondary', BLOCKED: 'badge-danger' }
  return map[status] ?? 'badge-secondary'
}

function formatExpiration(date: string): string {
  const d = new Date(date)
  return d.toLocaleDateString('es-VE', { day: '2-digit', month: 'short', year: 'numeric' })
}

async function handleToggleBlock(player: ExternalPlayer) {
  const willBlock = player.status !== 'BLOCKED'

  if (willBlock) {
    const result = await Swal.fire({
      title: 'Bloquear jugador',
      html: `<p class="mb-0">"${player.first_name} ${player.last_name}" (${player.phone}) no podrá seguir comprando cartones ni jugando mientras esté bloqueado.</p>`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Sí, bloquear',
      cancelButtonText: 'Cancelar',
      reverseButtons: true,
    })
    if (!result.isConfirmed) return
  }

  busyId.value = player.id
  try {
    emit('toggle-block', player.id)
  } finally {
    busyId.value = null
  }
}
</script>

<style scoped>
.ep-panel { width: 100%; }

/* ── Estadísticas ── */
.ep-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 0.6rem;
  margin-bottom: 1rem;
}
.ep-stat {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.15rem;
  padding: 0.75rem;
  border: 1px solid var(--ds-border);
  border-radius: 10px;
  background: var(--ds-surface);
}
.ep-stat-value { font-size: 1.3rem; font-weight: 700; color: var(--ds-text); }
.ep-stat-label { font-size: 0.75rem; color: var(--ds-text-muted); font-weight: 600; text-transform: uppercase; }
.ep-stat--active .ep-stat-value { color: #34d399; }
.ep-stat--inactive .ep-stat-value { color: var(--ds-text-muted); }
.ep-stat--blocked .ep-stat-value { color: #ef4444; }

/* ── Toolbar ── */
.ep-toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 0.6rem;
  margin-bottom: 1rem;
}
.ep-search {
  position: relative;
  flex: 1 1 280px;
  display: flex;
  align-items: center;
}
.ep-search i { position: absolute; left: 0.75rem; color: var(--ds-text-muted); font-size: 0.8rem; }
.ep-search .form-control { padding-left: 2.1rem; }
.ep-status-filter { flex: 0 1 180px; }

/* ── Estado vacío ── */
.ep-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.6rem;
  padding: 3rem 1rem;
  color: var(--ds-text-muted);
  text-align: center;
}
.ep-empty i { font-size: 2.2rem; opacity: 0.5; }

/* ── Lista ── */
.ep-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  max-height: calc(100vh - 420px);
  overflow-y: auto;
}

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
  .ep-toolbar { flex-direction: column; }
  .ep-status-filter { flex: 1; }
  .ep-row { flex-direction: column; align-items: stretch; }
}
</style>
