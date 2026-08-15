<script setup lang="ts">
  import { ref, computed, watch } from 'vue'

  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'
  import StatTile from '@/js/Components/Common/StatTile/StatTile.vue'

  import backendClient from '@/js/plugins/axios'
  import { useAudit } from '@/js/composables/audit/useAudit'

  import type { AuditPageProps } from '@/js/types/audit/audit'
  import type { TableFilterInterface } from '@/js/types/common/table/table-filter'
  import type { ServerQuery } from '@/js/composables/common/table/useTableController'

  /* -------------------------------------------------------------------------- */
  /* Props                                                                       */
  /* -------------------------------------------------------------------------- */

  const props = defineProps<AuditPageProps>()

  /* -------------------------------------------------------------------------- */
  /* Composable                                                                  */
  /* -------------------------------------------------------------------------- */

  const { audits, detail, loading, lastQuery, setInitialAudits, onQueryChange, fetchDetail, clearDetail } = useAudit()

  setInitialAudits(props.audits)

  /* -------------------------------------------------------------------------- */
  /* Tabla                                                                       */
  /* -------------------------------------------------------------------------- */

  const rows = computed(() => audits.value.rows)

  const filters = computed<readonly TableFilterInterface[]>(() => [
    {
      key:     'event',
      label:   'Evento',
      options: props.filterOptions.events,
    },
    {
      key:     'actor_type',
      label:   'Tipo Actor',
      options: props.filterOptions.actorTypes,
    },
    {
      key:     'entity',
      label:   'Entidad',
      options: props.filterOptions.entities,
    },
  ])

  /* -------------------------------------------------------------------------- */
  /* Filtros ad-hoc (fuera de TableWithFilterBridge): fecha, usuario, sujeto     */
  /* -------------------------------------------------------------------------- */

  const { user_id: initialUserId, ...initialSubjectFilter } = props.initialFilters ?? {}
  const subjectFilter = ref<{ entity?: string; auditable_id?: string }>({ ...initialSubjectFilter })
  const showDateFilters = ref(false)
  const dateFrom = ref('')
  const dateTo   = ref('')
  const userId   = ref(initialUserId ?? '')

  const subjectFilterLabel = computed(() => {
    if (!subjectFilter.value.auditable_id) return null
    const entityLabel = props.filterOptions.entities.find(e => e.value === subjectFilter.value.entity)?.label
    return `${entityLabel ?? 'Entidad'} #${subjectFilter.value.auditable_id}`
  })

  /**
   * Filtros que TableWithFilterBridge no conoce (no son columnas de la
   * tabla) pero que el backend sí acepta — se combinan con el query-change
   * de la tabla en cada refetch.
   */
  const extraFilters = computed(() => ({
    ...subjectFilter.value,
    date_from: dateFrom.value || undefined,
    date_to:   dateTo.value   || undefined,
    user_id:   userId.value   || undefined,
  }))

  async function refetch(): Promise<void> {
    await onQueryChange(lastQuery.value, extraFilters.value)
  }

  async function clearSubjectFilter(): Promise<void> {
    subjectFilter.value = {}
    await refetch()
  }

  async function applyDateFilter(): Promise<void> {
    await refetch()
  }

  async function clearDateFilter(): Promise<void> {
    dateFrom.value = ''
    dateTo.value   = ''
    await refetch()
  }

  /**
   * Atajos de rango de fecha — cubren el caso de uso más común ("¿qué pasó
   * hoy/esta semana?") sin obligar a abrir el date picker manual.
   */
  function toDateInputValue(date: Date): string {
    return date.toISOString().slice(0, 10)
  }

  async function applyDateShortcut(shortcut: 'today' | 'last7' | 'thisMonth'): Promise<void> {
    const now = new Date()
    showDateFilters.value = true

    if (shortcut === 'today') {
      dateFrom.value = toDateInputValue(now)
      dateTo.value   = toDateInputValue(now)
    } else if (shortcut === 'last7') {
      const from = new Date(now)
      from.setDate(from.getDate() - 6)
      dateFrom.value = toDateInputValue(from)
      dateTo.value   = toDateInputValue(now)
    } else {
      const from = new Date(now.getFullYear(), now.getMonth(), 1)
      dateFrom.value = toDateInputValue(from)
      dateTo.value   = toDateInputValue(now)
    }

    await refetch()
  }

  async function applyUserFilter(): Promise<void> {
    await refetch()
  }

  async function clearUserFilter(): Promise<void> {
    userId.value = ''
    await refetch()
  }

  async function handleQueryChange(query: ServerQuery): Promise<void> {
    await onQueryChange(query, extraFilters.value)
    await loadMostChangedFields(query.filters.entity ?? subjectFilter.value.entity)
  }

  /* -------------------------------------------------------------------------- */
  /* Campos que más cambian en la entidad filtrada (insight bajo demanda)       */
  /* -------------------------------------------------------------------------- */

  const mostChangedFields = ref<{ field: string; count: number }[]>([])
  const loadingMostChanged = ref(false)

  async function loadMostChangedFields(entity: string | undefined): Promise<void> {
    if (!entity) {
      mostChangedFields.value = []
      return
    }
    try {
      loadingMostChanged.value = true
      const { data } = await backendClient.get<{ data: { field: string; count: number }[] }>(
        '/audit/most-changed-fields',
        { params: { entity } }
      )
      mostChangedFields.value = data?.data ?? []
    } catch (error) {
      console.error(error)
      mostChangedFields.value = []
    } finally {
      loadingMostChanged.value = false
    }
  }

  if (subjectFilter.value.entity) {
    loadMostChangedFields(subjectFilter.value.entity)
  }

  /* -------------------------------------------------------------------------- */
  /* Export CSV                                                                  */
  /* -------------------------------------------------------------------------- */

  function handleExport(): void {
    const params = new URLSearchParams()
    if (lastQuery.value.search) params.set('search', lastQuery.value.search)
    if (lastQuery.value.filters.event) params.set('event', lastQuery.value.filters.event)
    if (lastQuery.value.filters.actor_type) params.set('actor_type', lastQuery.value.filters.actor_type)
    if (lastQuery.value.filters.entity ?? subjectFilter.value.entity) {
      params.set('entity', lastQuery.value.filters.entity || subjectFilter.value.entity || '')
    }
    if (subjectFilter.value.auditable_id) params.set('auditable_id', subjectFilter.value.auditable_id)
    if (userId.value) params.set('user_id', userId.value)
    if (dateFrom.value) params.set('date_from', dateFrom.value)
    if (dateTo.value) params.set('date_to', dateTo.value)
    window.location.href = `/audit/export?${params.toString()}`
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

  /* -------------------------------------------------------------------------- */
  /* Diff campo a campo (old_values vs new_values)                              */
  /* -------------------------------------------------------------------------- */

  type DiffStatus = 'unchanged' | 'changed' | 'added' | 'removed'

  interface DiffRow {
    key: string
    status: DiffStatus
    oldValue: unknown
    newValue: unknown
  }

  function formatDiffValue(value: unknown): string {
    if (value === undefined) return '—'
    if (value === null) return 'null'
    if (typeof value === 'object') return JSON.stringify(value)
    return String(value)
  }

  const diffRows = computed<DiffRow[]>(() => {
    const oldValues = detail.value?.old_values ?? {}
    const newValues = detail.value?.new_values ?? {}
    const keys = Array.from(new Set([...Object.keys(oldValues), ...Object.keys(newValues)])).sort()

    return keys.map(key => {
      const hasOld = Object.prototype.hasOwnProperty.call(oldValues, key)
      const hasNew = Object.prototype.hasOwnProperty.call(newValues, key)
      const oldValue = oldValues[key]
      const newValue = newValues[key]

      let status: DiffStatus
      if (!hasOld && hasNew) status = 'added'
      else if (hasOld && !hasNew) status = 'removed'
      else if (JSON.stringify(oldValue) !== JSON.stringify(newValue)) status = 'changed'
      else status = 'unchanged'

      return { key, status, oldValue, newValue }
    })
  })

  const diffStatusClass: Record<DiffStatus, string> = {
    unchanged: 'text-muted',
    changed:   'table-warning',
    added:     'table-success',
    removed:   'table-danger',
  }

  const diffStatusLabel: Record<DiffStatus, string> = {
    unchanged: 'Sin cambio',
    changed:   'Modificado',
    added:     'Agregado',
    removed:   'Eliminado',
  }

  /* -------------------------------------------------------------------------- */
  /* Badge de evento                                                             */
  /* -------------------------------------------------------------------------- */

  const eventBadgeClass: Record<string, string> = {
    created:       'badge bg-success',
    updated:       'badge bg-warning text-dark',
    deleted:       'badge bg-danger',
    restored:      'badge bg-info',
    force_deleted: 'badge bg-dark',
  }

  function eventClass(event: string): string {
    return eventBadgeClass[event] ?? 'badge bg-secondary'
  }
</script>

<template>
  <!-- Stat tiles -->
  <div class="audit-stats mb-3">
    <StatTile label="Registros totales" :value="stats.total" icon="fa-list" />
    <StatTile
      v-for="item in stats.topEntities"
      :key="item.entity"
      :label="`${item.entity} (top entidad)`"
      :value="item.count"
      icon="fa-database"
      variant="info"
    />
  </div>

  <!-- Filtro por sujeto (deep-link desde otra pantalla, ej. ficha de usuario) -->
  <div v-if="subjectFilterLabel" class="alert alert-info d-flex align-items-center justify-content-between py-2 px-3 mb-3">
    <span><i class="fas fa-filter me-2"></i>Mostrando historial de <strong>{{ subjectFilterLabel }}</strong></span>
    <button class="btn btn-sm btn-outline-secondary" @click="clearSubjectFilter">
      <i class="fas fa-times me-1"></i>Quitar filtro
    </button>
  </div>

  <!-- Filtros ad-hoc: fecha + usuario actor -->
  <div class="card mb-3">
    <div class="card-body py-2">
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <button
          class="btn btn-sm btn-outline-secondary"
          @click="showDateFilters = !showDateFilters"
        >
          <i class="fas fa-calendar-alt me-1"></i> Filtrar por fecha
        </button>

        <div class="btn-group btn-group-sm" role="group">
          <button class="btn btn-outline-secondary" :disabled="loading" @click="applyDateShortcut('today')">Hoy</button>
          <button class="btn btn-outline-secondary" :disabled="loading" @click="applyDateShortcut('last7')">Últimos 7 días</button>
          <button class="btn btn-outline-secondary" :disabled="loading" @click="applyDateShortcut('thisMonth')">Este mes</button>
        </div>

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

        <div class="input-group input-group-sm" style="width: 220px">
          <span class="input-group-text">Usuario (ID)</span>
          <input type="text" class="form-control" v-model="userId" placeholder="ID de actor" />
        </div>
        <button class="btn btn-sm btn-primary" @click="applyUserFilter" :disabled="loading">
          <i class="fas fa-search me-1"></i> Aplicar
        </button>
        <button v-if="userId" class="btn btn-sm btn-outline-danger" @click="clearUserFilter" :disabled="loading">
          <i class="fas fa-times"></i>
        </button>
      </div>
    </div>
  </div>

  <TableWithFilterBridge
    table-id="audit-table"
    title="Auditoría"
    subtitle="Registro de actividad del sistema"
    :columns="audits.columns"
    :rows="rows"
    :filters="filters"
    search-placeholder="Buscar en la auditoría..."
    :debounce-delay="300"
    empty-message="No se encontraron registros de auditoría"
    :loading="loading"
    server-side
    :server-total-pages="audits.last_page ?? 1"
    :show-create-button="false"
    :show-edit-button="false"
    :show-delete-button="false"
    @query-change="handleQueryChange"
  >
    <template #action-buttons>
      <button class="btn btn-sm btn-outline-secondary" title="Exportar auditoría filtrada a CSV" @click="handleExport">
        <i class="fas fa-file-csv me-1"></i>Exportar
      </button>
    </template>

    <!-- Columna: Evento (badge de color) -->
    <template #cell-event="{ value }">
      <span :class="eventClass(value)">{{ value }}</span>
    </template>

    <!-- Columna: Actor (ícono según tipo) -->
    <template #cell-actor_type="{ value }">
      <span class="text-muted small">
        <i
          :class="{
            'fas fa-user':       value === 'user',
            'fas fa-cogs':       value === 'system',
            'fas fa-clock':      value === 'job',
            'fas fa-terminal':   value === 'command',
            'fas fa-plug':       value === 'webhook',
          }"
          class="me-1"
        ></i>
        {{ value }}
      </span>
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

  <!-- Campos que más cambian en la entidad filtrada (insight bajo demanda) -->
  <div v-if="mostChangedFields.length" class="card mt-3">
    <div class="card-body py-2">
      <small class="text-muted d-block mb-2">Campos que más cambian en esta entidad (muestra reciente)</small>
      <div class="d-flex flex-wrap gap-2">
        <span v-for="item in mostChangedFields" :key="item.field" class="badge bg-light text-dark border">
          {{ item.field }} <span class="text-muted">×{{ item.count }}</span>
        </span>
      </div>
    </div>
  </div>

  <!-- Modal: Detalle del registro -->
  <Modal
    v-model="showDetail"
    title="Detalle del Registro"
    icon="fa-history"
    size="lg"
  >
    <template v-if="detail">
      <div class="row g-3">
        <div class="col-md-6">
          <small class="text-muted d-block">Fecha</small>
          <span>{{ detail.date }}</span>
        </div>
        <div class="col-md-6">
          <small class="text-muted d-block">Actor</small>
          <span>{{ detail.actor }}</span>
        </div>
        <div class="col-md-6">
          <small class="text-muted d-block">Tipo de Actor</small>
          <span>{{ detail.actor_type }}</span>
        </div>
        <div class="col-md-6">
          <small class="text-muted d-block">Evento</small>
          <span :class="eventClass(detail.event)">{{ detail.event }}</span>
        </div>
        <div class="col-md-6">
          <small class="text-muted d-block">Entidad</small>
          <span>{{ detail.entity }}</span>
        </div>
        <div class="col-md-6">
          <small class="text-muted d-block">ID Entidad</small>
          <span class="font-monospace small">{{ detail.entity_id ?? '—' }}</span>
        </div>
        <div class="col-md-6">
          <small class="text-muted d-block">IP</small>
          <span>{{ detail.ip_address ?? '—' }}</span>
        </div>
        <div class="col-md-6">
          <small class="text-muted d-block">URL</small>
          <span class="text-break small">{{ detail.url ?? '—' }}</span>
        </div>

        <!-- Diff campo a campo -->
        <div v-if="diffRows.length" class="col-12">
          <small class="text-muted d-block mb-1">Cambios</small>
          <div class="table-responsive" style="max-height:280px;overflow:auto">
            <table class="table table-sm mb-0">
              <thead>
                <tr>
                  <th>Campo</th>
                  <th>Anterior</th>
                  <th>Nuevo</th>
                  <th>Estado</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="diff in diffRows" :key="diff.key" :class="diffStatusClass[diff.status]">
                  <td class="font-monospace small">{{ diff.key }}</td>
                  <td class="small text-break">{{ formatDiffValue(diff.oldValue) }}</td>
                  <td class="small text-break">{{ formatDiffValue(diff.newValue) }}</td>
                  <td class="small">{{ diffStatusLabel[diff.status] }}</td>
                </tr>
              </tbody>
            </table>
          </div>
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
.audit-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 0.75rem;
}
</style>
