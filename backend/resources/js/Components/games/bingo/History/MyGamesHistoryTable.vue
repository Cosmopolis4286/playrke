<script setup lang="ts">
import DashboardSection from '@js/Components/Common/Dashboard/DashboardSection.vue'
import BaseTable        from '@js/Components/Common/Table/BaseTable.vue'
import TableFilter      from '@js/Components/Common/Table/TableFilter.vue'
import TableFooterInfo  from '@js/Components/Common/Table/TableFooterInfo.vue'
import type { TableFilterInterface } from '@js/types/common/table/table-filter'
import type { GameHistoryRow, GameHistoryPagination } from '@/js/types/games/bingo/history/my-games'
import { variantLabel, roomStatusBadge, isRoomOngoing, formatDate } from '@js/composables/games/bingo/history/useMyGamesFormatters'

defineProps<{
  history: GameHistoryRow[]
  meta: GameHistoryPagination
  loading: boolean
  columns: { key: string; label: string; sortable: boolean }[]
  tableFilters: TableFilterInterface[]
  totalGames: number
}>()

defineEmits<{
  filter: [payload: { search: string; filters: Record<string, string> }]
  'page-change': [page: number]
  'view-detail': [row: GameHistoryRow]
}>()
</script>

<template>
  <DashboardSection :title="`Historial (${totalGames} partidas)`" icon="fas fa-list" class="mg-history-section">
    <TableFilter
      class="mb-3"
      search-placeholder="Buscar por código de sala..."
      :filters="tableFilters"
      @filter="$emit('filter', $event)"
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
          <span class="text-nowrap small">{{ formatDate((row as GameHistoryRow).game_date) }}</span>
        </template>

        <template #cell-room_code="{ row }">
          <span class="font-monospace small mg-cell-code" :title="(row as GameHistoryRow).room_code">
            {{ (row as GameHistoryRow).room_code }}
          </span>
        </template>

        <template #cell-game_variant="{ row }">
          <span class="badge bg-info text-dark small">{{ variantLabel((row as GameHistoryRow).game_variant) }}</span>
        </template>

        <template #cell-cards_purchased="{ row }">
          <span class="badge bg-light text-dark border">
            <i class="fas fa-th me-1"></i>{{ (row as GameHistoryRow).cards_purchased }}
          </span>
        </template>

        <template #cell-result="{ row }">
          <span v-if="(row as GameHistoryRow).won" class="badge bg-success">
            <i class="fas fa-trophy me-1"></i>Gané
          </span>
          <span v-else-if="isRoomOngoing((row as GameHistoryRow).room_status)" class="badge bg-primary">
            <i class="fas fa-hourglass-half me-1"></i>En curso
          </span>
          <span v-else class="badge bg-light text-muted border">No gané</span>
        </template>

        <template #cell-room_status="{ row }">
          <span :class="roomStatusBadge((row as GameHistoryRow).room_status).cls">
            {{ roomStatusBadge((row as GameHistoryRow).room_status).label }}
          </span>
        </template>

        <template #cell-actions="{ row }">
          <button
            class="btn btn-sm btn-outline-primary"
            @click="$emit('view-detail', row as GameHistoryRow)"
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
            @click="$emit('page-change', meta.current_page - 1)"
          ><i class="fas fa-chevron-left"></i></button>
          <span class="btn btn-sm btn-light disabled">
            {{ meta.current_page }} / {{ meta.last_page }}
          </span>
          <button
            class="btn btn-sm btn-outline-secondary"
            :disabled="meta.current_page >= meta.last_page || loading"
            @click="$emit('page-change', meta.current_page + 1)"
          ><i class="fas fa-chevron-right"></i></button>
        </div>
      </div>
    </template>
  </DashboardSection>
</template>

<style scoped>
/* ── Botones secundarios (volver, paginación): mismo acento #667eea del
 * resto del design system en vez de los grises fijos de Bootstrap. ── */
:deep(.btn-outline-secondary) {
  border-color: var(--ds-border-2);
  color: var(--ds-text-2);
}
:deep(.btn-outline-secondary:hover:not(:disabled)) {
  background: rgba(102,126,234,.12);
  border-color: #667eea;
  color: #667eea;
}
:deep(.btn-light.disabled) {
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
:deep(.table-filter-container) {
  background: var(--ds-surface-2);
  border: 1px solid var(--ds-border);
}
:deep(.form-control),
:deep(.input-group-text) {
  background: var(--ds-surface);
  border-color: var(--ds-border-2);
  color: var(--ds-text);
}
:deep(.form-control::placeholder) {
  color: var(--ds-text-muted);
}
:deep(.form-control:focus) {
  background: var(--ds-surface);
  border-color: #667eea;
  color: var(--ds-text);
  box-shadow: 0 0 0 .2rem rgba(102,126,234,.15);
}
:deep(select.form-control option) {
  background: var(--ds-surface);
  color: var(--ds-text);
}

/* ── Tabla: encabezado, filas y hover legibles en ambos temas ── */
:deep(.table) { color: var(--ds-text); }
:deep(.table thead th) {
  border-color: var(--ds-border);
  color: var(--ds-text-2);
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: .4px;
  font-weight: 700;
  white-space: nowrap;
}
:deep(.table tbody td) {
  border-color: var(--ds-border);
  vertical-align: middle;
}
:deep(.table tbody tr:hover) {
  background-color: var(--ds-surface-2);
}

/* ── Botón "ver detalle": borde visible en ambos temas (btn-outline-primary
 * de Bootstrap usa un azul fijo que pierde contraste sobre fondo oscuro). ── */
:deep(.btn-outline-primary) {
  color: #667eea;
  border-color: rgba(102,126,234,.5);
}
:deep(.btn-outline-primary:hover) {
  background: rgba(102,126,234,.15);
  border-color: #667eea;
  color: #667eea;
}
</style>
