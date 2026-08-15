<script setup lang="ts">
  import { ref, computed, watch } from 'vue'

  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'
  import StatTile from '@/js/Components/Common/StatTile/StatTile.vue'
  import LogLevelBadge from '@/js/Components/Common/Badge/LogLevelBadge.vue'

  import { useSystemLogs } from '@/js/composables/audit/useSystemLogs'
  import { formatLevelName } from '@/js/utils/logs/logLevelDisplay'
  import { showToast } from '@/js/utils/toastNotify'

  import type { SystemLogPageProps } from '@/js/types/audit/system-log'
  import type { TableFilterInterface } from '@/js/types/common/table/table-filter'
  import type { ServerQuery } from '@/js/composables/common/table/useTableController'

  /* -------------------------------------------------------------------------- */
  /* Props                                                                       */
  /* -------------------------------------------------------------------------- */

  const props = defineProps<SystemLogPageProps>()

  /* -------------------------------------------------------------------------- */
  /* Composable                                                                  */
  /* -------------------------------------------------------------------------- */

  const { logs, detail, loading, lastQuery, setInitialLogs, onQueryChange, fetchDetail, toggleReviewed, clearDetail } = useSystemLogs()

  setInitialLogs(props.logs)

  /* -------------------------------------------------------------------------- */
  /* Stat tiles                                                                  */
  /* -------------------------------------------------------------------------- */

  const statVariant: Record<string, 'danger' | 'warning' | 'default'> = {
    error: 'danger',
    warn:  'warning',
  }

  /* -------------------------------------------------------------------------- */
  /* Tabla                                                                       */
  /* -------------------------------------------------------------------------- */

  const rows = computed(() => logs.value.rows)

  const filters = computed<readonly TableFilterInterface[]>(() => [
    {
      key:   'level',
      label: 'Nivel',
      options: [
        { label: 'Error',        value: 'error' },
        { label: 'Advertencia',  value: 'warn' },
      ],
    },
    {
      key:     'context',
      label:   'Contexto',
      options: props.contexts,
    },
  ])

  /* -------------------------------------------------------------------------- */
  /* Filtros ad-hoc: fecha                                                       */
  /* -------------------------------------------------------------------------- */

  const showDateFilters = ref(false)
  const dateFrom = ref('')
  const dateTo   = ref('')

  const extraFilters = computed(() => ({
    date_from: dateFrom.value || undefined,
    date_to:   dateTo.value   || undefined,
  }))

  async function refetch(): Promise<void> {
    await onQueryChange(lastQuery.value, extraFilters.value)
  }

  async function applyDateFilter(): Promise<void> {
    await refetch()
  }

  async function clearDateFilter(): Promise<void> {
    dateFrom.value = ''
    dateTo.value   = ''
    await refetch()
  }

  async function handleQueryChange(query: ServerQuery): Promise<void> {
    await onQueryChange(query, extraFilters.value)
  }

  /* -------------------------------------------------------------------------- */
  /* Detalle de registro                                                         */
  /* -------------------------------------------------------------------------- */

  const showDetail = ref(false)

  async function openDetail(id: string): Promise<void> {
    const ok = await fetchDetail(id)
    if (ok) showDetail.value = true
  }

  watch(showDetail, isOpen => {
    if (!isOpen) clearDetail()
  })

  async function handleToggleReviewed(): Promise<void> {
    if (!detail.value) return
    const wasReviewed = !!detail.value.reviewed_at
    const ok = await toggleReviewed(detail.value.id)
    if (ok) {
      showToast(wasReviewed ? 'Log marcado como no revisado' : 'Log marcado como revisado', 'success')
      await refetch()
    }
  }

  /* -------------------------------------------------------------------------- */
  /* Stack trace: resaltar la primera línea "propia" (no vendor/)               */
  /* -------------------------------------------------------------------------- */

  const stackLines = computed(() => {
    if (!detail.value?.stack) return []
    let highlighted = false
    return detail.value.stack.split('\n').map(line => {
      const isOrigin = !highlighted && !line.includes('/vendor/') && line.trim() !== ''
      if (isOrigin) highlighted = true
      return { text: line, isOrigin }
    })
  })

  /* -------------------------------------------------------------------------- */
  /* Correlación con /audit: si el log capturó un usuario autenticado           */
  /* -------------------------------------------------------------------------- */

  const relatedUserId = computed(() => {
    const value = detail.value?.meta?.user_id
    return typeof value === 'string' || typeof value === 'number' ? String(value) : null
  })
</script>

<template>
  <!-- Stat tiles -->
  <div class="logs-stats mb-3">
    <StatTile label="Logs totales" :value="stats.total" icon="fa-server" />
    <StatTile
      v-for="item in stats.byLevel"
      :key="item.level"
      :label="formatLevelName(item.level)"
      :value="item.count"
      icon="fa-exclamation-circle"
      :variant="statVariant[item.level] ?? 'default'"
    />
  </div>

  <!-- Filtros ad-hoc: fecha -->
  <div class="card mb-3">
    <div class="card-body py-2">
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <button
          class="btn btn-sm btn-outline-secondary"
          @click="showDateFilters = !showDateFilters"
        >
          <i class="fas fa-calendar-alt me-1"></i> Filtrar por fecha
        </button>

        <div v-if="showDateFilters" class="d-flex align-items-center gap-2 flex-wrap">
          <div class="input-group input-group-sm" style="width: 170px">
            <span class="input-group-text">Desde</span>
            <input type="date" class="form-control" v-model="dateFrom" />
          </div>
          <div class="input-group input-group-sm" style="width: 170px">
            <span class="input-group-text">Hasta</span>
            <input type="date" class="form-control" v-model="dateTo" />
          </div>
          <button class="btn btn-sm btn-primary" @click="applyDateFilter" :disabled="loading">
            <i class="fas fa-search me-1"></i> Aplicar
          </button>
          <button class="btn btn-sm btn-outline-danger" @click="clearDateFilter" :disabled="loading">
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>
    </div>
  </div>

  <TableWithFilterBridge
    table-id="system-logs-table"
    title="Logs del Sistema"
    subtitle="Errores y advertencias registrados por la aplicación"
    :columns="logs.columns"
    :rows="rows"
    :filters="filters"
    search-placeholder="Buscar en mensaje o contexto..."
    :debounce-delay="300"
    empty-message="No se encontraron registros de log"
    :loading="loading"
    server-side
    :server-total-pages="logs.last_page ?? 1"
    :show-create-button="false"
    :show-edit-button="false"
    :show-delete-button="false"
    @query-change="handleQueryChange"
  >
    <!-- Columna: Nivel (badge de color) -->
    <template #cell-level="{ value }">
      <LogLevelBadge :level="value" />
    </template>

    <!-- Columna: Mensaje (truncado) -->
    <template #cell-message="{ value }">
      <span class="text-truncate d-inline-block" style="max-width: 420px" :title="value">{{ value }}</span>
    </template>

    <!-- Columna: Ocurrencias (agrupación de errores repetidos) -->
    <template #cell-occurrences="{ value }">
      <span v-if="value > 1" class="badge bg-secondary">×{{ value }}</span>
      <span v-else class="text-muted small">—</span>
    </template>

    <!-- Columna: Revisado -->
    <template #cell-reviewed="{ value }">
      <i v-if="value" class="fas fa-check-circle text-success" title="Revisado"></i>
      <i v-else class="fas fa-circle text-muted" style="font-size: 0.5rem" title="Sin revisar"></i>
    </template>

    <!-- Columna: Acciones -->
    <template #cell-id="{ row }">
      <button
        class="btn btn-xs btn-outline-info"
        title="Ver detalle"
        @click="openDetail(String(row.id))"
      >
        <i class="fas fa-eye"></i>
      </button>
    </template>
  </TableWithFilterBridge>

  <!-- Modal: Detalle del registro -->
  <Modal
    v-model="showDetail"
    title="Detalle del Log"
    icon="fa-server"
    size="lg"
  >
    <template v-if="detail">
      <div class="d-flex justify-content-end mb-2">
        <button class="btn btn-sm" :class="detail.reviewed_at ? 'btn-outline-secondary' : 'btn-success'" @click="handleToggleReviewed">
          <i class="fas" :class="detail.reviewed_at ? 'fa-times' : 'fa-check'"></i>
          {{ detail.reviewed_at ? 'Marcar como no revisado' : 'Marcar como revisado' }}
        </button>
      </div>

      <div class="row g-3">
        <div class="col-md-6">
          <small class="text-muted d-block">Primera vez</small>
          <span>{{ detail.date }}</span>
        </div>
        <div class="col-md-6">
          <small class="text-muted d-block">Última vez</small>
          <span>{{ detail.last_seen_at ?? detail.date }}</span>
        </div>
        <div class="col-md-6">
          <small class="text-muted d-block">Nivel</small>
          <LogLevelBadge :level="detail.level" />
        </div>
        <div class="col-md-6">
          <small class="text-muted d-block">Ocurrencias</small>
          <span>{{ detail.occurrences }}</span>
        </div>
        <div class="col-md-6">
          <small class="text-muted d-block">Contexto</small>
          <span class="font-monospace small">{{ detail.context ?? '—' }}</span>
        </div>
        <div v-if="relatedUserId" class="col-md-6">
          <small class="text-muted d-block">Usuario en sesión</small>
          <a :href="`/audit?user_id=${relatedUserId}`" class="small">
            <i class="fas fa-history me-1"></i>Ver actividad de este usuario en /audit
          </a>
        </div>
        <div class="col-md-6">
          <small class="text-muted d-block">Revisado</small>
          <span v-if="detail.reviewed_at" class="text-success small">
            <i class="fas fa-check-circle me-1"></i>{{ detail.reviewed_at }}{{ detail.reviewed_by ? ` — ${detail.reviewed_by}` : '' }}
          </span>
          <span v-else class="text-muted small">Sin revisar</span>
        </div>
        <div class="col-12">
          <small class="text-muted d-block">Mensaje</small>
          <span>{{ detail.message }}</span>
        </div>

        <!-- Stack trace (primera línea propia resaltada) -->
        <div v-if="detail.stack" class="col-12">
          <small class="text-muted d-block mb-1">Stack trace</small>
          <pre class="log-stack rounded p-3 small mb-0" style="max-height:300px;overflow:auto"><span
            v-for="(line, idx) in stackLines"
            :key="idx"
            :class="{ 'log-stack-origin': line.isOrigin }"
          >{{ line.text }}<br /></span></pre>
        </div>

        <!-- Meta -->
        <div v-if="detail.meta" class="col-12">
          <small class="text-muted d-block mb-1">Meta</small>
          <pre class="bg-light border rounded p-2 small mb-0" style="max-height:200px;overflow:auto">{{ JSON.stringify(detail.meta, null, 2) }}</pre>
        </div>
      </div>
    </template>
    <template v-else>
      <div class="text-center py-4">
        <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
      </div>
    </template>
  </Modal>
</template>

<style scoped>
.logs-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 0.75rem;
}

.log-stack {
  background: #1a1d23;
  color: #4ade80;
  font-family: 'Courier New', monospace;
  white-space: pre-wrap;
  word-break: break-word;
}

.log-stack-origin {
  background: rgba(248, 113, 113, 0.15);
  color: #fca5a5;
  font-weight: 700;
  display: inline-block;
  width: 100%;
}
</style>
