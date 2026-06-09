<script setup lang="ts">
import { ref, computed } from 'vue'
import backendClient from '@js/plugins/axios'
import CardHeader      from '@js/Components/Common/Card/CardHeader.vue'
import BaseTable       from '@js/Components/Common/Table/BaseTable.vue'
import TableFilter     from '@js/Components/Common/Table/TableFilter.vue'
import TableFooterInfo from '@js/Components/Common/Table/TableFooterInfo.vue'
import Modal           from '@js/Components/Common/Modal/Modal.vue'
import type { TableFilterInterface } from '@js/types/common/table/table-filter'
import type { MyGamesPageProps, GameHistoryRow, GameHistoryPrize } from '@/js/types/games/bingo/history/my-games'

// ── Props ──────────────────────────────────────────────────────────────────
const props = defineProps<MyGamesPageProps>()

// ── Estado ─────────────────────────────────────────────────────────────────
const history    = ref<GameHistoryRow[]>(props.history)
const meta       = ref(props.pagination)
const loading    = ref(false)
const lastSearch = ref('')
const lastFilter = ref('')

// Modal detalle
const showDetail  = ref(false)
const detailRow   = ref<GameHistoryRow | null>(null)

// ── Columnas ───────────────────────────────────────────────────────────────
const columns = [
  { key: 'game_date',       label: 'Fecha',      sortable: false },
  { key: 'room_code',       label: 'Sala',        sortable: false },
  { key: 'cards_purchased', label: 'Cartones',    sortable: false },
  { key: 'result',          label: 'Resultado',   sortable: false },
  { key: 'prizes_count',    label: 'Premios',     sortable: false },
  { key: 'room_status',     label: 'Estado Sala', sortable: false },
  { key: 'actions',         label: '',            sortable: false },
]

// ── Filtros ────────────────────────────────────────────────────────────────
const tableFilters: TableFilterInterface[] = [
  {
    key: 'result',
    label: 'Resultado',
    placeholder: 'Todos',
    options: [
      { value: 'won',  label: 'Gané' },
      { value: 'lost', label: 'No gané' },
    ],
  },
]

// ── Helpers de display ─────────────────────────────────────────────────────
function formatDate(iso: string | null): string {
  if (!iso) return '—'
  const d = new Date(iso)
  return d.toLocaleDateString('es-VE', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function roomStatusBadge(status: string | null): { label: string; cls: string } {
  const map: Record<string, { label: string; cls: string }> = {
    ACTIVE:    { label: 'En curso',     cls: 'badge bg-success' },
    INACTIVE:  { label: 'Inactiva',     cls: 'badge bg-secondary' },
    FINISHED:  { label: 'Finalizada',   cls: 'badge bg-dark' },
    CANCELLED: { label: 'Cancelada',    cls: 'badge bg-danger' },
    DELETED:   { label: 'Eliminada',    cls: 'badge bg-danger' },
  }
  return map[status ?? ''] ?? { label: status ?? '—', cls: 'badge bg-secondary' }
}

const totalGames   = computed(() => meta.value.total)
const totalWon     = computed(() => history.value.filter(r => r.won).length)
const totalPending = computed(() => history.value.filter(r => !r.won).length)

// ── Fetch ──────────────────────────────────────────────────────────────────
async function fetchHistory(page = 1) {
  loading.value = true
  try {
    const { data } = await backendClient.get('/bingo/my-games', {
      params: {
        page,
        per_page: 15,
        search: lastSearch.value || undefined,
        result: lastFilter.value || undefined,
      },
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
    // Inertia JSON response
    history.value = data.props?.history ?? data.history ?? []
    meta.value    = data.props?.pagination ?? data.pagination ?? meta.value
  } catch (e) {
    console.error('[my-games] Error cargando historial:', e)
  } finally {
    loading.value = false
  }
}

function onFilter(payload: { search: string; filters: Record<string, string> }) {
  lastSearch.value = payload.search
  lastFilter.value = payload.filters.result ?? ''
  fetchHistory(1)
}

function openDetail(row: GameHistoryRow) {
  detailRow.value  = row
  showDetail.value = true
}
</script>

<template>
  <div class="my-games-page p-3">

    <!-- Cabecera -->
    <div class="d-flex align-items-center gap-2 mb-3">
      <a href="/bingo/game-rooms" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Salas
      </a>
      <h5 class="mb-0 fw-bold">
        <i class="fas fa-history me-2 text-primary"></i>{{ title }}
      </h5>
    </div>

    <!-- Stats resumen -->
    <div class="row g-2 mb-3">
      <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-2 px-3">
          <div class="text-muted small">Partidas jugadas</div>
          <div class="fw-bold fs-5 text-primary">{{ totalGames }}</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-2 px-3">
          <div class="text-muted small">Victorias</div>
          <div class="fw-bold fs-5 text-success">{{ history.filter(r => r.won).length }}</div>
        </div>
      </div>
    </div>

    <!-- Filtro y tabla -->
    <div class="card shadow-sm border-0">
      <CardHeader :title="`Historial (${totalGames} partidas)`" icon="fas fa-list" />

      <TableFilter
        search-placeholder="Buscar por código de sala..."
        :filters="tableFilters"
        @filter="onFilter"
      />

      <div class="card-body p-0">
        <BaseTable
          :columns="columns"
          :rows="history"
          :loading="loading"
          emptyMessage="Aún no has jugado ninguna partida de Bingo."
        >
          <!-- Fecha -->
          <template #cell-game_date="{ row }">
            <span class="text-nowrap small">{{ formatDate(row.game_date) }}</span>
          </template>

          <!-- Sala -->
          <template #cell-room_code="{ row }">
            <span class="font-monospace small text-truncate" style="max-width:120px;display:inline-block" :title="row.room_code">
              {{ row.room_code }}
            </span>
          </template>

          <!-- Cartones -->
          <template #cell-cards_purchased="{ row }">
            <span class="badge bg-info text-dark">{{ row.cards_purchased }}</span>
          </template>

          <!-- Resultado -->
          <template #cell-result="{ row }">
            <span v-if="row.won" class="badge bg-success">
              <i class="fas fa-trophy me-1"></i>Gané
            </span>
            <span v-else class="badge bg-secondary">No gané</span>
          </template>

          <!-- Premios -->
          <template #cell-prizes_count="{ row }">
            <span v-if="row.prizes_count > 0" class="text-success fw-semibold">
              {{ row.prizes_count }} premio{{ row.prizes_count > 1 ? 's' : '' }}
            </span>
            <span v-else class="text-muted small">—</span>
          </template>

          <!-- Estado sala -->
          <template #cell-room_status="{ row }">
            <span :class="roomStatusBadge(row.room_status).cls">
              {{ roomStatusBadge(row.room_status).label }}
            </span>
          </template>

          <!-- Acciones -->
          <template #cell-actions="{ row }">
            <button
              v-if="row.prizes_count > 0 || row.cards_purchased > 0"
              class="btn btn-sm btn-outline-primary"
              @click="openDetail(row)"
              title="Ver detalle"
            >
              <i class="fas fa-eye"></i>
            </button>
          </template>
        </BaseTable>
      </div>

      <!-- Paginación -->
      <div class="card-footer bg-transparent d-flex justify-content-between align-items-center flex-wrap gap-2">
        <TableFooterInfo :meta="meta" />
        <div class="d-flex gap-1">
          <button
            class="btn btn-sm btn-outline-secondary"
            :disabled="meta.current_page <= 1 || loading"
            @click="fetchHistory(meta.current_page - 1)"
          >
            <i class="fas fa-chevron-left"></i>
          </button>
          <span class="btn btn-sm btn-light disabled">
            {{ meta.current_page }} / {{ meta.last_page }}
          </span>
          <button
            class="btn btn-sm btn-outline-secondary"
            :disabled="meta.current_page >= meta.last_page || loading"
            @click="fetchHistory(meta.current_page + 1)"
          >
            <i class="fas fa-chevron-right"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- Modal detalle de partida -->
    <Modal v-model="showDetail" title="Detalle de la partida" size="md">
      <template v-if="detailRow">
        <div class="row g-3">
          <div class="col-6">
            <div class="text-muted small">Sala</div>
            <div class="font-monospace fw-semibold small">{{ detailRow.room_code }}</div>
          </div>
          <div class="col-6">
            <div class="text-muted small">Fecha del juego</div>
            <div class="small">{{ formatDate(detailRow.game_date) }}</div>
          </div>
          <div class="col-6">
            <div class="text-muted small">Cartones comprados</div>
            <div class="fw-bold">{{ detailRow.cards_purchased }}</div>
          </div>
          <div class="col-6">
            <div class="text-muted small">Resultado</div>
            <span v-if="detailRow.won" class="badge bg-success fs-6">
              <i class="fas fa-trophy me-1"></i>Ganador
            </span>
            <span v-else class="badge bg-secondary fs-6">No gané</span>
          </div>

          <!-- Premios -->
          <div v-if="detailRow.prizes.length > 0" class="col-12">
            <div class="text-muted small mb-2">Premios obtenidos</div>
            <div
              v-for="(prize, i) in detailRow.prizes"
              :key="i"
              class="d-flex justify-content-between align-items-center border rounded p-2 mb-1 bg-light"
            >
              <div>
                <span class="badge bg-warning text-dark me-2">{{ prize.type }}</span>
                <span class="small text-muted">{{ prize.status }}</span>
              </div>
              <div class="text-end">
                <div v-if="prize.usd_amount > 0" class="small fw-semibold text-success">
                  {{ prize.usd_amount.toFixed(2) }} USD
                </div>
                <div v-if="prize.local_amount > 0 && prize.currency !== 'USD'" class="small text-muted">
                  {{ prize.local_amount.toFixed(2) }} {{ prize.currency }}
                </div>
              </div>
            </div>

            <!-- Total -->
            <div class="d-flex justify-content-end mt-2 pt-2 border-top">
              <div class="text-end">
                <div v-if="detailRow.total_usd > 0" class="fw-bold text-success">
                  Total: {{ detailRow.total_usd.toFixed(2) }} USD
                </div>
              </div>
            </div>
          </div>

          <div v-else-if="!detailRow.won" class="col-12">
            <div class="alert alert-secondary py-2 mb-0">
              <i class="fas fa-info-circle me-2"></i>No obtuviste premios en esta partida.
            </div>
          </div>
        </div>
      </template>
    </Modal>

  </div>
</template>

<style scoped>
.my-games-page {
  max-width: 1100px;
  margin: 0 auto;
}
</style>
