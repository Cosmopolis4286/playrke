<script setup lang="ts">
import { ref, computed } from 'vue'
import backendClient    from '@js/plugins/axios'
import CardHeader       from '@js/Components/Common/Card/CardHeader.vue'
import BaseTable        from '@js/Components/Common/Table/BaseTable.vue'
import TableFilter      from '@js/Components/Common/Table/TableFilter.vue'
import TableFooterInfo  from '@js/Components/Common/Table/TableFooterInfo.vue'
import Modal            from '@js/Components/Common/Modal/Modal.vue'
import BingoCardDisplay from '@js/Components/Games/Bingo/Card/BingoCardDisplay.vue'
import type { TableFilterInterface } from '@js/types/common/table/table-filter'
import type {
  MyGamesPageProps,
  GameHistoryRow,
  GameHistoryDetail,
  GameHistoryRound,
} from '@/js/types/games/bingo/history/my-games'

// ── Props ──────────────────────────────────────────────────────────────────
const props = defineProps<MyGamesPageProps>()

// ── Estado lista ───────────────────────────────────────────────────────────
const history    = ref<GameHistoryRow[]>(props.history)
const meta       = ref(props.pagination)
const loading    = ref(false)
const lastSearch = ref('')
const lastFilter = ref('')

// ── Estado detalle ─────────────────────────────────────────────────────────
const showDetail    = ref(false)
const detailLoading = ref(false)
const detail        = ref<GameHistoryDetail | null>(null)
const activeCardTab = ref<'all' | 'winners'>('all')

// ── Columnas ───────────────────────────────────────────────────────────────
const columns = [
  { key: 'game_date',       label: 'Fecha',      sortable: false },
  { key: 'room_code',       label: 'Sala',        sortable: false },
  { key: 'game_variant',    label: 'Modalidad',   sortable: false },
  { key: 'cards_purchased', label: 'Cartones',    sortable: false },
  { key: 'result',          label: 'Resultado',   sortable: false },
  { key: 'room_status',     label: 'Estado',      sortable: false },
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

// ── Helpers display ────────────────────────────────────────────────────────
function formatDate(iso: string | null): string {
  if (!iso) return '—'
  const d = new Date(iso)
  return d.toLocaleDateString('es-VE', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}

function variantLabel(v: string): string {
  const map: Record<string, string> = {
    BINGO_75: '75 bolas',
    BINGO_90: '90 bolas',
    BINGO_80: '80 bolas',
    BINGO_30: '30 bolas',
  }
  return map[v] ?? v
}

function roomStatusBadge(status: string | null): { label: string; cls: string } {
  const map: Record<string, { label: string; cls: string }> = {
    ACTIVE:    { label: 'En curso',   cls: 'badge bg-success' },
    INACTIVE:  { label: 'Inactiva',   cls: 'badge bg-secondary' },
    FINISHED:  { label: 'Finalizada', cls: 'badge bg-dark' },
    CANCELLED: { label: 'Cancelada',  cls: 'badge bg-danger' },
    DELETED:   { label: 'Eliminada',  cls: 'badge bg-danger' },
  }
  return map[status ?? ''] ?? { label: status ?? '—', cls: 'badge bg-secondary' }
}

function roundStatusLabel(s: string): string {
  const map: Record<string, string> = {
    PENDING:  'Pendiente',
    ACTIVE:   'En curso',
    FINISHED: 'Finalizada',
  }
  return map[s] ?? s
}

function roundStatusCls(s: string): string {
  return s === 'FINISHED' ? 'badge bg-dark' : s === 'ACTIVE' ? 'badge bg-success' : 'badge bg-secondary'
}

function prizeStatusCls(s: string): string {
  const map: Record<string, string> = {
    waiting:   'badge bg-warning text-dark',
    claimed:   'badge bg-success',
    cancelled: 'badge bg-danger',
    suspended: 'badge bg-secondary',
  }
  return map[s] ?? 'badge bg-secondary'
}

function formatAmount(amount: number, isUsd: boolean, localAmount: number, symbol: string, decimals: number): string {
  if (isUsd) {
    return `${amount.toFixed(2)} USD`
  }
  const local = localAmount > 0 ? localAmount : amount
  return `${symbol}${local.toFixed(decimals)}`
}

function formatPrizeAmount(prize: { usd_amount: number; local_amount: number; currency: string; currency_symbol: string; decimal_places: number; is_usd: boolean }): string {
  return formatAmount(prize.usd_amount, prize.is_usd, prize.local_amount, prize.currency_symbol, prize.decimal_places)
}

function formatTotalAmount(d: { total_usd: number; total_local: number; currency: string; currency_symbol: string; decimal_places: number; is_usd: boolean }): string {
  return formatAmount(d.total_usd, d.is_usd, d.total_local, d.currency_symbol, d.decimal_places)
}

// ── Stats ──────────────────────────────────────────────────────────────────
const totalGames   = computed(() => meta.value.total)
const wonOnPage    = computed(() => history.value.filter(r => r.won).length)
const totalCards   = computed(() => history.value.reduce((a, r) => a + r.cards_purchased, 0))

// ── Cards para el detalle ──────────────────────────────────────────────────
const displayCards = computed(() => {
  if (!detail.value) return []
  if (activeCardTab.value === 'winners') {
    return detail.value.player_cards.filter(c => c.is_winner)
  }
  return detail.value.player_cards
})

// ── Fetch lista ────────────────────────────────────────────────────────────
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

// ── Fetch detalle ──────────────────────────────────────────────────────────
async function openDetail(row: GameHistoryRow) {
  detail.value        = null
  activeCardTab.value = 'all'
  showDetail.value    = true
  detailLoading.value = true
  try {
    const { data } = await backendClient.get(`/bingo/my-games/${row.room_id}/detail`, {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
    detail.value = data.data ?? null
    if (detail.value?.player_cards.some(c => c.is_winner)) {
      activeCardTab.value = 'winners'
    }
  } catch (e) {
    console.error('[my-games] Error cargando detalle:', e)
  } finally {
    detailLoading.value = false
  }
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
      <div class="col-6 col-md-4">
        <div class="stat-card stat-card--primary">
          <i class="fas fa-gamepad stat-icon"></i>
          <div>
            <div class="stat-label">Partidas totales</div>
            <div class="stat-value text-primary">{{ totalGames }}</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-4">
        <div class="stat-card stat-card--success">
          <i class="fas fa-trophy stat-icon text-success"></i>
          <div>
            <div class="stat-label">Victorias (página)</div>
            <div class="stat-value text-success">{{ wonOnPage }}</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-4">
        <div class="stat-card stat-card--info">
          <i class="fas fa-th stat-icon text-info"></i>
          <div>
            <div class="stat-label">Cartones (página)</div>
            <div class="stat-value text-info">{{ totalCards }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabla de historial -->
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
          <template #cell-game_date="{ row }">
            <span class="text-nowrap small">{{ formatDate(row.game_date) }}</span>
          </template>

          <template #cell-room_code="{ row }">
            <span class="font-monospace small" style="max-width:140px;display:inline-block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" :title="row.room_code">
              {{ row.room_code }}
            </span>
          </template>

          <template #cell-game_variant="{ row }">
            <span class="badge bg-info text-dark small">{{ variantLabel(row.game_variant) }}</span>
          </template>

          <template #cell-cards_purchased="{ row }">
            <span class="badge bg-secondary">
              <i class="fas fa-th me-1"></i>{{ row.cards_purchased }}
            </span>
          </template>

          <template #cell-result="{ row }">
            <span v-if="row.won" class="badge bg-success">
              <i class="fas fa-trophy me-1"></i>Gané
            </span>
            <span v-else class="badge bg-light text-secondary border">No gané</span>
          </template>

          <template #cell-room_status="{ row }">
            <span :class="roomStatusBadge(row.room_status).cls">
              {{ roomStatusBadge(row.room_status).label }}
            </span>
          </template>

          <template #cell-actions="{ row }">
            <button
              class="btn btn-sm btn-outline-primary"
              @click="openDetail(row)"
              title="Ver detalle"
            >
              <i class="fas fa-eye"></i>
            </button>
          </template>
        </BaseTable>
      </div>

      <div class="card-footer bg-transparent d-flex justify-content-between align-items-center flex-wrap gap-2">
        <TableFooterInfo :meta="meta" />
        <div class="d-flex gap-1">
          <button
            class="btn btn-sm btn-outline-secondary"
            :disabled="meta.current_page <= 1 || loading"
            @click="fetchHistory(meta.current_page - 1)"
          ><i class="fas fa-chevron-left"></i></button>
          <span class="btn btn-sm btn-light disabled">
            {{ meta.current_page }} / {{ meta.last_page }}
          </span>
          <button
            class="btn btn-sm btn-outline-secondary"
            :disabled="meta.current_page >= meta.last_page || loading"
            @click="fetchHistory(meta.current_page + 1)"
          ><i class="fas fa-chevron-right"></i></button>
        </div>
      </div>
    </div>

    <!-- ─────────────────────────────────────────────────────── -->
    <!-- Modal detalle de partida                               -->
    <!-- ─────────────────────────────────────────────────────── -->
    <Modal v-model="showDetail" title="Detalle de la partida" size="xl">

      <!-- Skeleton carga -->
      <div v-if="detailLoading" class="text-center py-5 text-muted">
        <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
        <div>Cargando detalle…</div>
      </div>

      <template v-else-if="detail">

        <!-- Cabecera coloreada según resultado -->
        <div
          class="detail-banner mb-3"
          :class="detail.won ? 'detail-banner--winner' : 'detail-banner--normal'"
        >
          <div class="detail-banner__left">
            <i :class="detail.won ? 'fas fa-trophy' : 'fas fa-gamepad'" class="detail-banner__icon"></i>
            <div>
              <div class="detail-banner__title">
                {{ detail.won ? '¡Felicidades, ganaste!' : 'Partida completada' }}
              </div>
              <div class="detail-banner__sub font-monospace">{{ detail.room_code }}</div>
            </div>
          </div>
          <div class="detail-banner__right">
            <div v-if="detail.total_usd > 0" class="detail-banner__prize">
              +{{ formatTotalAmount(detail) }}
            </div>
            <span :class="roomStatusBadge(detail.room_status).cls">
              {{ roomStatusBadge(detail.room_status).label }}
            </span>
          </div>
        </div>

        <!-- Info general -->
        <div class="row g-2 mb-3">
          <div class="col-6 col-md-3">
            <div class="detail-info-card">
              <div class="detail-info-card__label">Fecha</div>
              <div class="detail-info-card__value small">{{ formatDate(detail.game_date) }}</div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="detail-info-card">
              <div class="detail-info-card__label">Modalidad</div>
              <div class="detail-info-card__value">{{ variantLabel(detail.game_variant) }}</div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="detail-info-card">
              <div class="detail-info-card__label">Tipo de sala</div>
              <div class="detail-info-card__value">{{ detail.room_type ?? '—' }}</div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="detail-info-card">
              <div class="detail-info-card__label">Cartones</div>
              <div class="detail-info-card__value text-primary fw-bold">{{ detail.cards_purchased }}</div>
            </div>
          </div>
          <div v-if="detail.game_mode" class="col-6 col-md-3">
            <div class="detail-info-card">
              <div class="detail-info-card__label">Modo de juego</div>
              <div class="detail-info-card__value">{{ detail.game_mode }}</div>
            </div>
          </div>
          <div v-if="detail.prize_pool_type" class="col-6 col-md-3">
            <div class="detail-info-card">
              <div class="detail-info-card__label">Tipo de bote</div>
              <div class="detail-info-card__value">{{ detail.prize_pool_type }}</div>
            </div>
          </div>
          <div v-if="detail.prize_pool > 0" class="col-6 col-md-3">
            <div class="detail-info-card">
              <div class="detail-info-card__label">Bote</div>
              <div class="detail-info-card__value text-success fw-bold">
                <template v-if="detail.is_usd">{{ detail.prize_pool.toFixed(2) }} USD</template>
                <template v-else>{{ detail.currency_symbol }}{{ detail.prize_pool_local.toFixed(detail.decimal_places) }}</template>
              </div>
              <div v-if="!detail.is_usd" class="detail-info-card__sub">({{ detail.prize_pool.toFixed(2) }} USD)</div>
            </div>
          </div>
        </div>

        <!-- Premios obtenidos -->
        <div v-if="detail.prizes.length > 0" class="mb-3">
          <h6 class="section-heading">
            <i class="fas fa-star me-2 text-warning"></i>Premios obtenidos
          </h6>
          <div class="prize-list">
            <div
              v-for="(prize, i) in detail.prizes"
              :key="i"
              class="prize-item"
            >
              <div class="prize-item__left">
                <span class="badge bg-info text-dark me-2">{{ prize.type }}</span>
                <span :class="prizeStatusCls(prize.status)">{{ prize.status_label }}</span>
              </div>
              <div class="prize-item__right">
                <span v-if="prize.usd_amount > 0" class="fw-bold text-success me-2">
                  {{ formatPrizeAmount(prize) }}
                </span>
                <span v-if="!prize.is_usd && prize.usd_amount > 0" class="small text-muted">
                  ({{ prize.usd_amount.toFixed(2) }} USD)
                </span>
              </div>
            </div>
            <!-- Total -->
            <div v-if="detail.total_usd > 0" class="prize-total">
              <span>Total ganado</span>
              <div class="text-end">
                <span class="fw-bold text-success">{{ formatTotalAmount(detail) }}</span>
                <span v-if="!detail.is_usd" class="d-block small text-muted">({{ detail.total_usd.toFixed(2) }} USD)</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Rondas -->
        <div v-if="detail.rounds.length > 0" class="mb-3">
          <h6 class="section-heading">
            <i class="fas fa-list-ol me-2 text-primary"></i>Rondas
          </h6>
          <div class="rounds-grid">
            <div
              v-for="round in detail.rounds"
              :key="round.round"
              class="round-item"
            >
              <div class="round-item__number">#{{ round.round }}</div>
              <div class="round-item__info">
                <span class="small text-muted">{{ round.prize_type }}</span>
              </div>
              <span :class="roundStatusCls(round.status)" class="round-item__status">
                {{ roundStatusLabel(round.status) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Cartones del jugador -->
        <div v-if="detail.player_cards.length > 0">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="section-heading mb-0">
              <i class="fas fa-th me-2 text-secondary"></i>Mis cartones
              <span class="badge bg-secondary ms-2">{{ detail.player_cards.length }}</span>
            </h6>
            <div v-if="detail.player_cards.some(c => c.is_winner)" class="btn-group btn-group-sm">
              <button
                class="btn"
                :class="activeCardTab === 'winners' ? 'btn-warning text-dark' : 'btn-outline-secondary'"
                @click="activeCardTab = 'winners'"
              >
                <i class="fas fa-trophy me-1"></i>Ganadores
              </button>
              <button
                class="btn"
                :class="activeCardTab === 'all' ? 'btn-secondary' : 'btn-outline-secondary'"
                @click="activeCardTab = 'all'"
              >
                Todos
              </button>
            </div>
          </div>

          <div class="cards-grid">
            <BingoCardDisplay
              v-for="card in displayCards"
              :key="card.id"
              :cardMatrix="card.card_matrix"
              :variant="detail.game_variant"
              :serial="card.serial"
              :isWinner="card.is_winner"
            />
          </div>

          <div v-if="displayCards.length === 0" class="text-center text-muted small py-3">
            Sin cartones ganadores en esta partida.
          </div>
        </div>

        <!-- Sin premios -->
        <div v-else-if="!detail.won" class="alert alert-secondary py-2">
          <i class="fas fa-info-circle me-2"></i>No obtuviste premios en esta partida.
        </div>

      </template>
    </Modal>

  </div>
</template>

<style scoped>
.my-games-page {
  max-width: 1200px;
  margin: 0 auto;
}

/* ── Stat cards ─────────────────────────────────────────── */
.stat-card {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #fff;
  border: 1px solid #e9ecef;
  border-radius: 10px;
  padding: 14px 16px;
  box-shadow: 0 1px 4px rgba(0,0,0,.06);
}
.stat-icon {
  font-size: 22px;
  opacity: .7;
}
.stat-label { font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: .5px; }
.stat-value { font-size: 22px; font-weight: 800; line-height: 1.1; }

/* ── Section headings inside modal ──────────────────────── */
.section-heading {
  font-size: 13px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .6px;
  color: #555;
  margin-bottom: 8px;
}

/* ── Detail banner ──────────────────────────────────────── */
.detail-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  border-radius: 10px;
  padding: 14px 18px;
}
.detail-banner--winner {
  background: linear-gradient(135deg, #fffde7, #fff9c4);
  border: 2px solid #ffd700;
}
.detail-banner--normal {
  background: #f8f9fa;
  border: 1px solid #dee2e6;
}
.detail-banner__left  { display: flex; align-items: center; gap: 12px; }
.detail-banner__icon  { font-size: 28px; color: #f59e0b; }
.detail-banner__title { font-weight: 700; font-size: 15px; }
.detail-banner__sub   { font-size: 11px; color: #888; }
.detail-banner__right { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.detail-banner__prize { font-size: 20px; font-weight: 800; color: #16a34a; }

/* ── Info cards ─────────────────────────────────────────── */
.detail-info-card {
  background: #f8f9fa;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  padding: 10px 12px;
}
.detail-info-card__label { font-size: 10px; text-transform: uppercase; color: #999; letter-spacing: .4px; margin-bottom: 2px; }
.detail-info-card__value { font-weight: 600; font-size: 13px; color: #333; }
.detail-info-card__sub   { font-size: 10px; color: #999; margin-top: 1px; }

/* ── Prize list ─────────────────────────────────────────── */
.prize-list {
  border: 1px solid #e9ecef;
  border-radius: 8px;
  overflow: hidden;
}
.prize-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 14px;
  border-bottom: 1px solid #f0f0f0;
  background: #fff;
}
.prize-item:last-child { border-bottom: none; }
.prize-total {
  display: flex;
  justify-content: space-between;
  padding: 10px 14px;
  background: #f0fdf4;
  border-top: 2px solid #bbf7d0;
  font-size: 14px;
  color: #555;
}

/* ── Rounds grid ────────────────────────────────────────── */
.rounds-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
.round-item {
  display: flex;
  align-items: center;
  gap: 8px;
  background: #f8f9fa;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  padding: 8px 12px;
  min-width: 160px;
}
.round-item__number { font-weight: 700; font-size: 13px; color: #333; }
.round-item__info   { flex: 1; font-size: 12px; }
.round-item__status { flex-shrink: 0; font-size: 10px; }

/* ── Cards grid ─────────────────────────────────────────── */
.cards-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  justify-content: flex-start;
}
</style>
