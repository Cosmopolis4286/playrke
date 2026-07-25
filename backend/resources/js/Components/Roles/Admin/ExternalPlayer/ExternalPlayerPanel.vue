<template>
  <div class="ep-panel">
    <ExternalPlayerStats :statistics="statistics" />

    <ExternalPlayerFilters
      :loading="loading"
      :games="games"
      @change="filters => $emit('search', filters)"
      @change-debounced="filters => $emit('search-debounced', filters)"
      @export="$emit('export')"
    />

    <div v-if="loading && !players.length" class="ep-skeleton">
      <div v-for="n in 4" :key="n" class="ep-skeleton-row"></div>
    </div>

    <div v-else-if="!players.length" class="ep-empty">
      <i class="fas fa-user-friends"></i>
      <p class="mb-0">No se encontraron jugadores externos.</p>
    </div>

    <template v-else>
      <div class="ep-list">
        <ExternalPlayerRow
          v-for="player in players"
          :key="player.id"
          :player="player"
          :busy="busyId === player.id"
          @toggle-block="handleToggleBlock"
        />
      </div>

      <ExternalPlayerPagination
        :current-page="pagination.currentPage"
        :last-page="pagination.lastPage"
        :total="pagination.total"
        @change="page => $emit('page-change', page)"
      />
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import Swal from 'sweetalert2'
import ExternalPlayerStats from './ExternalPlayerStats.vue'
import ExternalPlayerFilters from './ExternalPlayerFilters.vue'
import ExternalPlayerRow from './ExternalPlayerRow.vue'
import ExternalPlayerPagination from './ExternalPlayerPagination.vue'
import type { ExternalPlayer, ExternalPlayerFilters as Filters, ExternalPlayerStatistics, GameOption } from '@/js/types/roles/admin/external-player'

defineProps<{
  players: ExternalPlayer[]
  statistics: ExternalPlayerStatistics
  pagination: { currentPage: number; lastPage: number; perPage: number; total: number }
  loading: boolean
  games: GameOption[]
}>()

const emit = defineEmits<{
  (e: 'search', filters: Filters): void
  (e: 'search-debounced', filters: Filters): void
  (e: 'page-change', page: number): void
  (e: 'toggle-block', playerId: string): void
  (e: 'export'): void
}>()

const busyId = ref<string | null>(null)

async function handleToggleBlock(player: ExternalPlayer) {
  const willBlock = player.status !== 'BLOCKED'

  if (willBlock) {
    const result = await Swal.fire({
      title: 'Bloquear jugador',
      html: `<p class="mb-0">"${player.first_name} ${player.last_name}" (${player.phone}) no podrá seguir comprando ni jugando mientras esté bloqueado.</p>`,
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

.ep-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  max-height: calc(100vh - 460px);
  overflow-y: auto;
}
</style>
