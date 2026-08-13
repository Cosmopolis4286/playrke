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
      <div class="ep-list-wrap">
        <div class="ep-list">
          <ExternalPlayerRow
            v-for="player in players"
            :key="player.id"
            :player="player"
            :busy="busyId === player.id"
            @toggle-block="handleToggleBlock"
            @regenerate-link="handleRegenerateLink"
            @reactivate="handleReactivate"
            @convert-to-user="handleConvertToUser"
          />
        </div>
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
  (e: 'regenerate-link', playerId: string): void
  (e: 'reactivate', playerId: string): void
  (e: 'convert-to-user', playerId: string): void
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

async function handleRegenerateLink(player: ExternalPlayer) {
  const result = await Swal.fire({
    title: 'Regenerar link',
    html: `<p class="mb-0">El link actual de "${player.first_name} ${player.last_name}" dejará de funcionar y se generará uno nuevo. Úsalo si el link se compartió por error.</p>`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#0d6efd',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Sí, regenerar',
    cancelButtonText: 'Cancelar',
    reverseButtons: true,
  })
  if (!result.isConfirmed) return

  busyId.value = player.id
  try {
    emit('regenerate-link', player.id)
  } finally {
    busyId.value = null
  }
}

async function handleReactivate(player: ExternalPlayer) {
  busyId.value = player.id
  try {
    emit('reactivate', player.id)
  } finally {
    busyId.value = null
  }
}

async function handleConvertToUser(player: ExternalPlayer) {
  const result = await Swal.fire({
    title: 'Convertir a usuario registrado',
    html: `<p class="mb-0">Se creará una cuenta con rol Jugador para "${player.first_name} ${player.last_name}". El comprador podrá iniciar sesión con una contraseña temporal que se mostrará solo una vez. Esta acción no se puede deshacer.</p>`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#0d6efd',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Sí, convertir',
    cancelButtonText: 'Cancelar',
    reverseButtons: true,
  })
  if (!result.isConfirmed) return

  busyId.value = player.id
  try {
    emit('convert-to-user', player.id)
  } finally {
    busyId.value = null
  }
}
</script>

<style scoped>
/* Panel de altura fija respecto al viewport (header + breadcrumb + padding
   de layout ~220px) para que .ep-list-wrap pueda repartirse el resto vía
   flex — antes .ep-list tenía su propio max-height fijo (calc(100vh-460px))
   que no descontaba la altura real de stats+toolbar+paginación, dejando un
   hueco vacío entre la lista y el borde inferior de la pantalla. */
.ep-panel {
  width: 100%;
  display: flex;
  flex-direction: column;
  min-height: calc(100vh - 220px);
}

.ep-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.6rem;
  padding: 3rem 1rem;
  color: var(--ds-text-muted);
  text-align: center;
  flex: 1;
}
.ep-empty i { font-size: 2.2rem; opacity: 0.5; }

/* ── Skeleton de carga ── */
.ep-skeleton { display: flex; flex-direction: column; gap: 0.5rem; flex: 1; }
.ep-skeleton-row {
  height: 58px;
  border-radius: 10px;
  background: linear-gradient(90deg, var(--ds-surface-2) 25%, var(--ds-border) 50%, var(--ds-surface-2) 75%);
  background-size: 200% 100%;
  animation: ep-skeleton-shimmer 1.4s ease-in-out infinite;
}
@keyframes ep-skeleton-shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

/* Absorbe todo el espacio vertical restante del panel; .ep-list scrollea
   dentro de ese espacio en vez de crecer sin límite. */
.ep-list-wrap {
  flex: 1;
  min-height: 0;
  display: flex;
  flex-direction: column;
}

.ep-list {
  flex: 1;
  min-height: 0;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  overflow-y: auto;
}
</style>
