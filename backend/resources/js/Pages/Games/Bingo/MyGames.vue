<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import backendClient      from '@js/plugins/axios'
import DashboardStatCard  from '@js/Components/Common/Dashboard/DashboardStatCard.vue'
import DashboardSection   from '@js/Components/Common/Dashboard/DashboardSection.vue'
import BaseTable          from '@js/Components/Common/Table/BaseTable.vue'
import TableFilter        from '@js/Components/Common/Table/TableFilter.vue'
import TableFooterInfo    from '@js/Components/Common/Table/TableFooterInfo.vue'
import Modal              from '@js/Components/Common/Modal/Modal.vue'
import BingoCardDisplay   from '@js/Components/Games/Bingo/Card/BingoCardDisplay.vue'
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
    ACTIVE:      { label: 'Abierta',    cls: 'badge bg-success' },
    IN_PROGRESS: { label: 'En curso',   cls: 'badge bg-primary' },
    INACTIVE:    { label: 'Inactiva',   cls: 'badge bg-light' },
    FINISHED:    { label: 'Finalizada', cls: 'badge bg-secondary' },
    CANCELLED:   { label: 'Cancelada',  cls: 'badge bg-danger' },
    DELETED:     { label: 'Eliminada',  cls: 'badge bg-danger' },
  }
  return map[status ?? ''] ?? { label: status ?? '—', cls: 'badge bg-light' }
}

/** Salas que aún no terminaron: "No gané" sería una conclusión prematura y engañosa. */
function isRoomOngoing(status: string | null): boolean {
  return status === 'ACTIVE' || status === 'IN_PROGRESS'
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
  return s === 'FINISHED' ? 'badge bg-secondary' : s === 'ACTIVE' ? 'badge bg-primary' : 'badge bg-light'
}

function prizeStatusCls(s: string): string {
  const map: Record<string, string> = {
    waiting:    'badge bg-warning text-dark',
    processing: 'badge bg-info text-dark',
    claimed:    'badge bg-success',
    cancelled:  'badge bg-danger',
    suspended:  'badge bg-light',
  }
  return map[s] ?? 'badge bg-light'
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

/** Gasto y neto vienen solo en moneda local (no traen su par USD por separado). */
function formatLocalOnly(amount: number, symbol: string, decimals: number, isUsd: boolean): string {
  return isUsd ? `${amount.toFixed(2)} USD` : `${symbol}${amount.toFixed(decimals)}`
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

// ── Carrusel de cartones ────────────────────────────────────────────────────
const cardIndex = ref(0)

watch(displayCards, () => { cardIndex.value = 0 })

/** Clamp defensivo: evita index-out-of-bounds si displayCards se acorta
 * (cambio de tab a "winners") antes de que el watcher reinicie cardIndex. */
const activeCard = computed(() => {
  const cards = displayCards.value
  return cards[cardIndex.value] ?? cards[0] ?? null
})

function prevCard() {
  cardIndex.value = cardIndex.value === 0 ? displayCards.value.length - 1 : cardIndex.value - 1
}
function nextCard() {
  cardIndex.value = cardIndex.value === displayCards.value.length - 1 ? 0 : cardIndex.value + 1
}

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
  } catch {
    // silencioso — tabla queda vacía
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
  } catch {
    showDetail.value = false
  } finally {
    detailLoading.value = false
  }
}
</script>

<template>
  <div class="mg-page p-3">

    <!-- Cabecera -->
    <div class="d-flex align-items-center gap-2 mb-3">
      <a href="/bingo/game-rooms" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Salas
      </a>
      <div>
        <h5 class="mb-0 fw-bold"><i class="fas fa-history me-2 text-primary"></i>{{ title }}</h5>
        <small class="text-muted">Historial completo de tus partidas de Bingo</small>
      </div>
    </div>

    <!-- Stats resumen -->
    <div class="row g-2 mb-3">
      <div class="col-6 col-md-4">
        <DashboardStatCard
          label="Partidas totales"
          :value="totalGames"
          icon="fas fa-gamepad"
          variant="primary"
        />
      </div>
      <div class="col-6 col-md-4">
        <DashboardStatCard
          label="Victorias (página)"
          :value="wonOnPage"
          icon="fas fa-trophy"
          variant="success"
        />
      </div>
      <div class="col-6 col-md-4">
        <DashboardStatCard
          label="Cartones (página)"
          :value="totalCards"
          icon="fas fa-th"
          variant="warning"
        />
      </div>
    </div>

    <!-- Tabla de historial -->
    <DashboardSection :title="`Historial (${totalGames} partidas)`" icon="fas fa-list" class="mg-history-section">
      <TableFilter
        class="mb-3"
        search-placeholder="Buscar por código de sala..."
        :filters="tableFilters"
        @filter="onFilter"
      />

      <div class="mg-table-wrap">
        <BaseTable
          table-id="my-games-table"
          :columns="columns"
          :rows="history"
          :loading="loading"
          emptyMessage="Aún no has jugado ninguna partida de Bingo."
        >
          <template #cell-game_date="{ row }">
            <span class="text-nowrap small">{{ formatDate(row.game_date) }}</span>
          </template>

          <template #cell-room_code="{ row }">
            <span class="font-monospace small mg-cell-code" :title="row.room_code">
              {{ row.room_code }}
            </span>
          </template>

          <template #cell-game_variant="{ row }">
            <span class="badge bg-info text-dark small">{{ variantLabel(row.game_variant) }}</span>
          </template>

          <template #cell-cards_purchased="{ row }">
            <span class="badge bg-light text-dark border">
              <i class="fas fa-th me-1"></i>{{ row.cards_purchased }}
            </span>
          </template>

          <template #cell-result="{ row }">
            <span v-if="row.won" class="badge bg-success">
              <i class="fas fa-trophy me-1"></i>Gané
            </span>
            <span v-else-if="isRoomOngoing(row.room_status)" class="badge bg-primary">
              <i class="fas fa-hourglass-half me-1"></i>En curso
            </span>
            <span v-else class="badge bg-light text-muted border">No gané</span>
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

      <template #footer>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
          <TableFooterInfo :filtered-count="history.length" :total-count="meta.total" />
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
      </template>
    </DashboardSection>

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
          :class="detail.won ? 'detail-banner--winner' : (isRoomOngoing(detail.room_status) ? 'detail-banner--live' : 'detail-banner--normal')"
        >
          <div class="detail-banner__left">
            <i
              :class="detail.won ? 'fas fa-trophy' : (isRoomOngoing(detail.room_status) ? 'fas fa-hourglass-half' : 'fas fa-gamepad')"
              class="detail-banner__icon"
            ></i>
            <div>
              <div class="detail-banner__title">
                {{ detail.won ? '¡Felicidades, ganaste!' : (isRoomOngoing(detail.room_status) ? 'Partida en curso' : 'Partida completada') }}
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

        <!-- Resumen financiero: gasto vs. ganado vs. neto — el feedback más
             directo de "¿me convino jugar esta partida?" ── -->
        <div class="result-summary mb-3">
          <div class="result-summary__item">
            <div class="result-summary__label"><i class="fas fa-coins"></i>Gastado</div>
            <div class="result-summary__value">{{ formatLocalOnly(detail.total_spent_local, detail.currency_symbol, detail.decimal_places, detail.is_usd) }}</div>
          </div>
          <div class="result-summary__sep"><i class="fas fa-minus"></i></div>
          <div class="result-summary__item">
            <div class="result-summary__label"><i class="fas fa-trophy"></i>Ganado</div>
            <div class="result-summary__value result-summary__value--win">{{ formatLocalOnly(detail.total_local, detail.currency_symbol, detail.decimal_places, detail.is_usd) }}</div>
          </div>
          <div class="result-summary__sep"><i class="fas fa-equals"></i></div>
          <div class="result-summary__item">
            <div class="result-summary__label">Neto</div>
            <div
              class="result-summary__value"
              :class="detail.net_result_local >= 0 ? 'result-summary__value--win' : 'result-summary__value--loss'"
            >
              {{ detail.net_result_local >= 0 ? '+' : '' }}{{ formatLocalOnly(detail.net_result_local, detail.currency_symbol, detail.decimal_places, detail.is_usd) }}
            </div>
          </div>
        </div>

        <!-- Info general -->
        <div class="row g-2 mb-3">
          <div class="col-6 col-md-3">
            <div class="detail-info-card">
              <div class="detail-info-card__label">Fecha</div>
              <div class="detail-info-card__value detail-info-card__value--sm">{{ formatDate(detail.game_date) }}</div>
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
              <div class="detail-info-card__value detail-info-card__value--accent">{{ detail.cards_purchased }}</div>
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
              <div class="detail-info-card__label">Bote total de la sala</div>
              <div class="detail-info-card__value detail-info-card__value--win">
                <template v-if="detail.is_usd">{{ detail.prize_pool.toFixed(2) }} USD</template>
                <template v-else>{{ detail.currency_symbol }}{{ detail.prize_pool_local.toFixed(detail.decimal_places) }}</template>
              </div>
              <div v-if="!detail.is_usd" class="detail-info-card__sub">({{ detail.prize_pool.toFixed(2) }} USD)</div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="detail-info-card">
              <div class="detail-info-card__label">Jugadores en la sala</div>
              <div class="detail-info-card__value"><i class="fas fa-users me-1 detail-info-card__icon"></i>{{ detail.total_players }}</div>
            </div>
          </div>
        </div>

        <!-- Premios obtenidos -->
        <div v-if="detail.prizes.length > 0" class="mb-3">
          <h6 class="section-heading">
            <i class="fas fa-star"></i>Premios obtenidos
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
                <span v-if="prize.usd_amount > 0" class="prize-item__amount">
                  {{ formatPrizeAmount(prize) }}
                </span>
                <span v-if="!prize.is_usd && prize.usd_amount > 0" class="prize-item__amount-usd">
                  ({{ prize.usd_amount.toFixed(2) }} USD)
                </span>
              </div>
            </div>
            <!-- Total -->
            <div v-if="detail.total_usd > 0" class="prize-total">
              <span>Total ganado</span>
              <div class="text-end">
                <span class="prize-total__amount">{{ formatTotalAmount(detail) }}</span>
                <span v-if="!detail.is_usd" class="prize-total__usd">({{ detail.total_usd.toFixed(2) }} USD)</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Rondas -->
        <div v-if="detail.rounds.length > 0" class="mb-3">
          <h6 class="section-heading">
            <i class="fas fa-list-ol"></i>Rondas
            <span class="section-heading__hint">{{ detail.rounds_finished }}/{{ detail.rounds_total }} finalizadas</span>
          </h6>
          <div class="rounds-grid">
            <div
              v-for="round in detail.rounds"
              :key="round.round"
              class="round-item"
            >
              <div class="round-item__number">#{{ round.round }}</div>
              <div class="round-item__info">{{ round.prize_type }}</div>
              <span :class="roundStatusCls(round.status)" class="round-item__status">
                {{ roundStatusLabel(round.status) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Cartones del jugador (carrusel) -->
        <div v-if="detail.player_cards.length > 0">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="section-heading mb-0">
              <i class="fas fa-th"></i>Mis cartones
              <span class="badge bg-light text-dark border ms-2">{{ detail.player_cards.length }}</span>
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

          <div v-if="activeCard" class="card-carousel">
            <button
              v-if="displayCards.length > 1"
              class="card-carousel__nav card-carousel__nav--prev"
              aria-label="Cartón anterior"
              @click="prevCard"
            ><i class="fas fa-chevron-left"></i></button>

            <div class="card-carousel__stage">
              <BingoCardDisplay
                :key="activeCard.id"
                :cardMatrix="activeCard.card_matrix"
                :variant="detail.game_variant"
                :serial="activeCard.serial"
                :isWinner="activeCard.is_winner"
              />
            </div>

            <button
              v-if="displayCards.length > 1"
              class="card-carousel__nav card-carousel__nav--next"
              aria-label="Cartón siguiente"
              @click="nextCard"
            ><i class="fas fa-chevron-right"></i></button>
          </div>

          <div v-if="displayCards.length > 1" class="card-carousel__dots">
            <button
              v-for="(card, i) in displayCards"
              :key="card.id"
              class="card-carousel__dot"
              :class="{ 'card-carousel__dot--active': i === cardIndex, 'card-carousel__dot--winner': card.is_winner }"
              :aria-label="`Ver cartón ${i + 1}`"
              @click="cardIndex = i"
            ></button>
          </div>

          <div v-if="!activeCard" class="text-center text-muted small py-3">
            Sin cartones ganadores en esta partida.
          </div>
        </div>

        <!-- Sin premios / en curso -->
        <div v-else-if="isRoomOngoing(detail.room_status)" class="alert alert-primary py-2">
          <i class="fas fa-hourglass-half me-2"></i>La partida sigue en curso — aún no hay resultado final.
        </div>
        <div v-else-if="!detail.won" class="alert alert-secondary py-2">
          <i class="fas fa-info-circle me-2"></i>No obtuviste premios en esta partida.
        </div>

      </template>
    </Modal>

  </div>
</template>

<style scoped>
.mg-page {
  max-width: 1200px;
  margin: 0 auto;
}

/* ── Botones secundarios (volver, paginación): mismo acento #667eea del
 * resto del design system en vez de los grises fijos de Bootstrap. ── */
.mg-page :deep(.btn-outline-secondary) {
  border-color: var(--ds-border-2);
  color: var(--ds-text-2);
}
.mg-page :deep(.btn-outline-secondary:hover:not(:disabled)) {
  background: rgba(102,126,234,.12);
  border-color: #667eea;
  color: #667eea;
}
.mg-page :deep(.btn-light.disabled) {
  background: var(--ds-surface-2);
  border: 1px solid var(--ds-border);
  color: var(--ds-text);
  opacity: 1;
}

/* ── Sección historial: el header de DashboardSection queda algo apretado
 * con el buscador dentro — se relaja a columna en pantallas angostas. ── */
:deep(.mg-history-section > .card-header) {
  flex-wrap: wrap;
}
.mg-table-wrap { margin: 0 -1rem; }
:deep(.mg-table-wrap .table-responsive) { padding: 0 1rem; }
.mg-cell-code {
  max-width: 140px; display: inline-block; overflow: hidden;
  text-overflow: ellipsis; white-space: nowrap; color: var(--ds-text-2);
}

/* ── TableFilter dentro del card-body de la sección: se integra sin fondo
 * propio duplicado, ya que DashboardSection ya provee el contenedor.
 * TableFilter.vue es un componente compartido con estilos claros fijos
 * (fondo #f8f9fa, inputs Bootstrap por defecto) — se sobrescribe aquí
 * localmente en vez de tocar el componente global, para no afectar otras
 * tablas del admin que sí usan el tema claro fijo intencionalmente. ── */
:deep(.mg-history-section .table-filter-container) {
  background: var(--ds-surface-2);
  border: 1px solid var(--ds-border);
}
:deep(.mg-history-section .form-control),
:deep(.mg-history-section .input-group-text) {
  background: var(--ds-surface);
  border-color: var(--ds-border-2);
  color: var(--ds-text);
}
:deep(.mg-history-section .form-control::placeholder) {
  color: var(--ds-text-muted);
}
:deep(.mg-history-section .form-control:focus) {
  background: var(--ds-surface);
  border-color: #667eea;
  color: var(--ds-text);
  box-shadow: 0 0 0 .2rem rgba(102,126,234,.15);
}
:deep(.mg-history-section select.form-control option) {
  background: var(--ds-surface);
  color: var(--ds-text);
}

/* ── Tabla: encabezado, filas y hover legibles en ambos temas ── */
:deep(.mg-history-section .table) { color: var(--ds-text); }
:deep(.mg-history-section .table thead th) {
  border-color: var(--ds-border);
  color: var(--ds-text-2);
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: .4px;
  font-weight: 700;
  white-space: nowrap;
}
:deep(.mg-history-section .table tbody td) {
  border-color: var(--ds-border);
  vertical-align: middle;
}
:deep(.mg-history-section .table tbody tr:hover) {
  background-color: var(--ds-surface-2);
}

/* ── Botón "ver detalle": borde visible en ambos temas (btn-outline-primary
 * de Bootstrap usa un azul fijo que pierde contraste sobre fondo oscuro). ── */
:deep(.mg-history-section .btn-outline-primary) {
  color: #667eea;
  border-color: rgba(102,126,234,.5);
}
:deep(.mg-history-section .btn-outline-primary:hover) {
  background: rgba(102,126,234,.15);
  border-color: #667eea;
  color: #667eea;
}

/* ── Section headings inside modal ──────────────────────── */
.section-heading {
  font-size: 13px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .6px;
  color: var(--ds-text-2);
  margin-bottom: 8px;
  display: flex;
  align-items: center;
  gap: 7px;
}
.section-heading i { color: #f59e0b; font-size: 12px; }
.section-heading__hint {
  font-weight: 500; text-transform: none; letter-spacing: 0;
  color: var(--ds-text-muted); font-size: 11px; margin-left: 2px;
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
  background: linear-gradient(135deg, rgba(255,193,7,.14), rgba(255,193,7,.05));
  border: 2px solid rgba(255,193,7,.45);
}
.detail-banner--live {
  background: linear-gradient(135deg, rgba(13,110,253,.12), rgba(13,110,253,.04));
  border: 1px solid rgba(13,110,253,.3);
}
.detail-banner--normal {
  background: var(--ds-surface-2);
  border: 1px solid var(--ds-border);
}
.detail-banner__left  { display: flex; align-items: center; gap: 12px; }
.detail-banner__icon  { font-size: 28px; color: #b08000; }
.detail-banner--live .detail-banner__icon { color: #0d6efd; }
.detail-banner--normal .detail-banner__icon { color: var(--ds-text-2); }
.detail-banner__title { font-weight: 700; font-size: 15px; color: var(--ds-text); }
.detail-banner__sub   { font-size: 11px; color: var(--ds-text-muted); }
.detail-banner__right { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.detail-banner__prize { font-size: 20px; font-weight: 800; color: #198754; }

/* ── Resumen financiero: gasto → ganado → neto, el feedback más accionable
 * del modal (¿me convino jugar?) — antes no existía en absoluto. ── */
.result-summary {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 18px;
  background: var(--ds-surface-2);
  border: 1px solid var(--ds-border);
  border-radius: 10px;
  padding: 12px 18px;
  flex-wrap: wrap;
}
.result-summary__item { text-align: center; }
.result-summary__label {
  font-size: 10px; text-transform: uppercase; letter-spacing: .4px;
  color: var(--ds-text-muted); margin-bottom: 2px;
  display: flex; align-items: center; justify-content: center; gap: 4px;
}
.result-summary__label i { font-size: 9px; }
.result-summary__value { font-size: 16px; font-weight: 700; color: var(--ds-text); }
.result-summary__value--win  { color: #10b981; }
.result-summary__value--loss { color: #ef4444; }
.result-summary__sep { color: var(--ds-text-muted); font-size: 11px; opacity: .6; }

/* ── Info cards ─────────────────────────────────────────── */
.detail-info-card {
  background: var(--ds-surface-2);
  border: 1px solid var(--ds-border);
  border-radius: 8px;
  padding: 10px 12px;
  height: 100%;
}
.detail-info-card__label { font-size: 10px; text-transform: uppercase; color: var(--ds-text-muted); letter-spacing: .4px; margin-bottom: 2px; }
.detail-info-card__value { font-weight: 600; font-size: 13px; color: var(--ds-text); }
.detail-info-card__value--sm     { font-size: 11.5px; }
.detail-info-card__value--accent { color: #667eea; }
.detail-info-card__value--win    { color: #10b981; }
.detail-info-card__icon          { color: var(--ds-text-muted); }
.detail-info-card__sub   { font-size: 10px; color: var(--ds-text-muted); margin-top: 1px; }

/* ── Prize list ─────────────────────────────────────────── */
.prize-list {
  border: 1px solid var(--ds-border);
  border-radius: 8px;
  overflow: hidden;
}
.prize-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 14px;
  border-bottom: 1px solid var(--ds-border);
  background: var(--ds-surface);
}
.prize-item:last-child { border-bottom: none; }
.prize-item__amount     { font-weight: 700; color: #10b981; margin-right: 6px; }
.prize-item__amount-usd { font-size: 11px; color: var(--ds-text-muted); }
.prize-total {
  display: flex;
  justify-content: space-between;
  padding: 10px 14px;
  background: rgba(16,185,129,.08);
  border-top: 2px solid rgba(16,185,129,.25);
  font-size: 14px;
  color: var(--ds-text-2);
}
.prize-total__amount { font-weight: 800; color: #10b981; font-size: 15px; }
.prize-total__usd    { display: block; font-size: 10px; color: var(--ds-text-muted); text-align: right; }

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
  background: var(--ds-surface-2);
  border: 1px solid var(--ds-border);
  border-radius: 8px;
  padding: 8px 12px;
  min-width: 160px;
}
.round-item__number { font-weight: 700; font-size: 13px; color: var(--ds-text); }
.round-item__info   { flex: 1; font-size: 12px; color: var(--ds-text-muted); }
.round-item__status { flex-shrink: 0; font-size: 10px; }

/* ── Carrusel de cartones ─────────────────────────────────
 * Se muestra un cartón a la vez (más legible que una grilla apretada
 * cuando el jugador compró varios), con flechas + puntos indicadores;
 * el punto del cartón ganador se resalta en dorado para ubicarlo rápido. ── */
.card-carousel {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
}
.card-carousel__stage {
  width: 100%;
  max-width: 280px;
  display: flex;
  justify-content: center;
}
.card-carousel__nav {
  flex-shrink: 0;
  width: 34px; height: 34px;
  border-radius: 50%;
  background: var(--ds-surface-2);
  border: 1px solid var(--ds-border-2);
  color: var(--ds-text-2);
  display: flex; align-items: center; justify-content: center;
  font-size: 12px;
  cursor: pointer;
  transition: background .2s, border-color .2s, color .2s, transform .15s;
}
.card-carousel__nav:hover {
  background: rgba(102,126,234,.15);
  border-color: #667eea;
  color: #667eea;
  transform: scale(1.05);
}
.card-carousel__dots {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  margin-top: 12px;
}
.card-carousel__dot {
  width: 8px; height: 8px;
  border-radius: 50%;
  background: var(--ds-border-2);
  border: none;
  cursor: pointer;
  padding: 0;
  transition: background .2s, transform .15s;
}
.card-carousel__dot:hover { transform: scale(1.2); }
.card-carousel__dot--active { background: #667eea; transform: scale(1.3); }
.card-carousel__dot--winner { background: #f59e0b; }
.card-carousel__dot--winner.card-carousel__dot--active { background: #f59e0b; }
</style>
