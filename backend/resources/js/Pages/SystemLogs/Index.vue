<script setup lang="ts">
  import { ref, computed, watch, onMounted, onUnmounted } from 'vue'

  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'
  import PageHeader from '@/js/Components/Common/PageHeader/PageHeader.vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'
  import StatTile from '@/js/Components/Common/StatTile/StatTile.vue'
  import LogLevelBadge from '@/js/Components/Common/Badge/LogLevelBadge.vue'

  import { useSystemLogs } from '@/js/composables/audit/useSystemLogs'
  import { useLocalDateTime } from '@/js/composables/common/useLocalDateTime'
  import { formatLevelName } from '@/js/utils/logs/logLevelDisplay'
  import { showToast } from '@/js/utils/toastNotify'
  import { showToastConfirmAction } from '@/js/helpers/form/ui/crudModalUI'

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

  const {
    logs, detail, relatedLogs, relatedLoading, loading, lastQuery, newLogsCount,
    setInitialLogs, initPolling, onQueryChange, fetchDetail, fetchRelatedLogs, toggleReviewed, toggleIgnored, assign, clearDetail,
    startPolling, stopPolling,
  } = useSystemLogs()

  setInitialLogs(props.logs)
  initPolling()
  onMounted(() => startPolling())
  onUnmounted(() => stopPolling())

  /* -------------------------------------------------------------------------- */
  /* Stat tiles                                                                  */
  /* -------------------------------------------------------------------------- */

  const statVariant: Record<string, 'danger' | 'warning' | 'default'> = {
    emergency: 'danger',
    alert:     'danger',
    critical:  'danger',
    error:     'danger',
    warn:      'warning',
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
        { label: 'Emergencia',    value: 'emergency' },
        { label: 'Alerta',        value: 'alert' },
        { label: 'Crítico',       value: 'critical' },
        { label: 'Error',         value: 'error' },
        { label: 'Advertencia',   value: 'warn' },
      ],
    },
    {
      key:     'context',
      label:   'Contexto',
      options: props.contexts,
    },
    {
      key:     'assigned_to_user_id',
      label:   'Asignado a',
      options: [{ label: 'Asignados a mí', value: 'mine' }, ...props.assignableUsers],
    },
  ])

  /* -------------------------------------------------------------------------- */
  /* Filtros ad-hoc: fecha + ignorados                                           */
  /* -------------------------------------------------------------------------- */

  const showDateFilters = ref(false)
  const dateFrom = ref('')
  const dateTo   = ref('')
  const showIgnored = ref(false)
  const showUnreviewedOnly = ref(false)

  const extraFilters = computed(() => ({
    date_from: dateFrom.value || undefined,
    date_to:   dateTo.value   || undefined,
    ignored:   showIgnored.value ? ('only' as const) : undefined,
    reviewed:  showUnreviewedOnly.value ? ('no' as const) : undefined,
  }))

  async function toggleShowIgnored(): Promise<void> {
    showIgnored.value = !showIgnored.value
    await refetch()
  }

  async function toggleShowUnreviewedOnly(): Promise<void> {
    showUnreviewedOnly.value = !showUnreviewedOnly.value
    await refetch()
  }

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
  /* Export CSV                                                                  */
  /* -------------------------------------------------------------------------- */

  function handleExport(): void {
    const params = new URLSearchParams()
    if (lastQuery.value.search) params.set('search', lastQuery.value.search)
    if (lastQuery.value.filters.level) params.set('level', lastQuery.value.filters.level)
    if (lastQuery.value.filters.context) params.set('context', lastQuery.value.filters.context)
    if (lastQuery.value.filters.assigned_to_user_id) params.set('assigned_to_user_id', lastQuery.value.filters.assigned_to_user_id)
    if (dateFrom.value) params.set('date_from', dateFrom.value)
    if (dateTo.value) params.set('date_to', dateTo.value)
    if (showIgnored.value) params.set('ignored', 'only')
    if (showUnreviewedOnly.value) params.set('reviewed', 'no')
    window.location.href = `/logs/export?${params.toString()}`
  }

  /* -------------------------------------------------------------------------- */
  /* Detalle de registro                                                         */
  /* -------------------------------------------------------------------------- */

  const showDetail = ref(false)

  async function openDetail(id: string): Promise<void> {
    const ok = await fetchDetail(id)
    if (ok) {
      showDetail.value = true
      if (detail.value?.meta?.user_id) await fetchRelatedLogs(id)
    }
  }

  watch(showDetail, isOpen => {
    if (!isOpen) clearDetail()
  })

  async function handleToggleReviewed(): Promise<void> {
    if (!detail.value) return
    const wasReviewed = !!detail.value.reviewed_at

    const confirmed = wasReviewed
      ? await showToastConfirmAction('Este log volverá a marcarse como sin revisar.', 'question', 'Sí, marcar como no revisado')
      : await showToastConfirmAction('Este log se marcará como revisado.', 'question', 'Sí, marcar como revisado')
    if (!confirmed) return

    const ok = await toggleReviewed(detail.value.id)
    if (ok) {
      showToast(wasReviewed ? 'Log marcado como no revisado' : 'Log marcado como revisado', 'success')
      await refetch()
    }
  }

  /* -------------------------------------------------------------------------- */
  /* Ignorar: "conocido, no molestar más" a nivel de fingerprint — distinto de  */
  /* revisado, que es por fila y se resetea con cada nueva ocurrencia.          */
  /* -------------------------------------------------------------------------- */

  async function handleToggleIgnored(): Promise<void> {
    if (!detail.value) return
    const wasIgnored = !!detail.value.ignored_at

    const confirmed = wasIgnored
      ? await showToastConfirmAction('Este log volverá a aparecer en la lista y a contar en las alertas.', 'question', 'Sí, reactivar')
      : await showToastConfirmAction('Este error se ocultará de la lista y ya no contará como pendiente, incluso si vuelve a ocurrir.', 'warning', 'Sí, ignorar')
    if (!confirmed) return

    const ok = await toggleIgnored(detail.value.id)
    if (ok) {
      showToast(wasIgnored ? 'Log reactivado' : 'Log marcado como ignorado', 'success')
      await refetch()
    }
  }

  /* -------------------------------------------------------------------------- */
  /* Asignación: reparte la carga cuando hay varios admins mirando /logs        */
  /* -------------------------------------------------------------------------- */

  async function handleAssign(userId: string): Promise<void> {
    if (!detail.value) return
    const ok = await assign(detail.value.id, userId || null)
    if (ok) {
      showToast(userId ? 'Log asignado' : 'Asignación removida', 'success')
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

  /* -------------------------------------------------------------------------- */
  /* Zona horaria: "Primera vez" / "Última vez" en hora local del navegador,    */
  /* con subinfo comparando contra la zona horaria del servidor.                */
  /* -------------------------------------------------------------------------- */

  const { describe: describeDateTime } = useLocalDateTime()

  const firstSeenInfo = computed(() =>
    describeDateTime(detail.value?.date, detail.value?.server_timezone ?? null)
  )
  const lastSeenInfo = computed(() =>
    describeDateTime(detail.value?.last_seen_at ?? detail.value?.date, detail.value?.server_timezone ?? null)
  )
  const reviewedInfo = computed(() =>
    describeDateTime(detail.value?.reviewed_at, detail.value?.server_timezone ?? null)
  )

  function rowDateInfo(row: Record<string, any>) {
    return describeDateTime(row.date, row.server_timezone ?? null)
  }
</script>

<template>
  <PageHeader
    icon="fa-server"
    title="Logs del Sistema"
    subtitle="Registro de errores y advertencias capturados por el sistema."
    :breadcrumb-items="[
      { label: 'Dashboard', route: '/dashboard', icon: 'fas fa-home' },
      { label: 'Sistema', icon: 'fas fa-cogs' },
      { label: 'Logs' },
    ]"
  />

  <!-- Banner: logs nuevos detectados por el polling en background -->
  <div v-if="newLogsCount > 0" class="alert alert-warning d-flex align-items-center justify-content-between py-2 px-3 mb-3">
    <span>
      <i class="fas fa-bell me-2"></i>
      Hay {{ newLogsCount }} log{{ newLogsCount === 1 ? '' : 's' }} nuevo{{ newLogsCount === 1 ? '' : 's' }} desde la última carga
    </span>
    <button class="btn btn-sm btn-warning" @click="refetch">
      <i class="fas fa-rotate-right me-1"></i>Actualizar
    </button>
  </div>

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
    <button
      type="button"
      class="log-stat-tile-btn"
      :class="{ 'log-stat-tile-btn--active': showUnreviewedOnly }"
      title="Filtrar solo logs sin revisar"
      @click="toggleShowUnreviewedOnly"
    >
      <StatTile
        label="Sin revisar"
        :value="stats.unreviewedCount"
        icon="fa-circle-question"
        :variant="stats.unreviewedCount > 0 ? 'warning' : 'default'"
      />
    </button>
    <StatTile label="Ignorados" :value="stats.ignoredCount" icon="fa-eye-slash" variant="default" />
  </div>

  <!-- Filtros ad-hoc: fecha + ignorados -->
  <div class="card mb-3">
    <div class="card-body py-2">
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <button
          class="btn btn-sm btn-outline-secondary"
          @click="showDateFilters = !showDateFilters"
        >
          <i class="fas fa-calendar-alt me-1"></i> Filtrar por fecha
        </button>

        <button
          class="btn btn-sm"
          :class="showIgnored ? 'btn-secondary' : 'btn-outline-secondary'"
          @click="toggleShowIgnored"
        >
          <i class="fas fa-eye-slash me-1"></i> {{ showIgnored ? 'Viendo ignorados' : 'Ver ignorados' }}
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

      <div class="log-retention-info mt-2">
        <i class="fas fa-info-circle me-1"></i>
        Se conservan <strong>{{ retention.days }} días</strong> de logs (purga automática diaria) · alerta si hay
        <strong>{{ retention.alertThreshold }}+ errores</strong> en <strong>{{ retention.alertWindowMinutes }} min</strong>.
        <a href="/app-parameters" class="ms-1">Editar en Parámetros</a>
        ·
        <a href="/system/jobs">Ver historial de ejecución</a>
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
    <template #action-buttons>
      <button class="btn btn-sm btn-outline-secondary" title="Exportar logs filtrados a CSV" @click="handleExport">
        <i class="fas fa-file-csv me-1"></i>Exportar
      </button>
    </template>

    <!-- Columna: Última vez, en hora local del navegador (tooltip con la hora
         del servidor si difiere) — misma conversión que el modal de detalle. -->
    <template #cell-date="{ row }">
      <div
        v-if="rowDateInfo(row)"
        class="log-row-date"
        :title="rowDateInfo(row)!.differsFromServer ? `Servidor (${rowDateInfo(row)!.serverTimezone}): ${rowDateInfo(row)!.server}` : `Hora local (${rowDateInfo(row)!.localTimezone})`"
      >
        <span class="log-row-date-date">{{ rowDateInfo(row)!.localDate }}</span>
        <span class="log-row-date-time">{{ rowDateInfo(row)!.localTime }}</span>
      </div>
      <span v-else class="text-muted">—</span>
    </template>

    <!-- Columna: Nivel (badge de color + indicador de revisado adosado) -->
    <template #cell-level="{ row, value }">
      <div class="d-flex align-items-center gap-2">
        <LogLevelBadge :level="value" />
        <i
          :class="['fas', row.reviewed ? 'fa-check-circle text-success' : 'fa-circle text-muted']"
          :style="!row.reviewed ? 'font-size: 0.5rem' : undefined"
          :title="row.reviewed ? 'Revisado' : 'Sin revisar'"
        ></i>
      </div>
    </template>

    <!-- Columna: Mensaje (truncado, con contexto + ocurrencias + asignado como metadata secundaria) -->
    <template #cell-message="{ row, value }">
      <div class="log-message-cell">
        <span class="text-truncate d-inline-block" style="max-width: 420px" :title="value">{{ value }}</span>
        <small class="text-muted log-message-meta">
          <span class="font-monospace">{{ row.context }}</span>
          <span v-if="row.occurrences > 1" class="badge bg-secondary ms-1">×{{ row.occurrences }}</span>
          <span v-if="row.ignored" class="badge bg-secondary ms-1"><i class="fas fa-eye-slash me-1"></i>Ignorado</span>
          <span v-if="row.assigned_to" class="badge bg-info text-dark ms-1"><i class="fas fa-user me-1"></i>{{ row.assigned_to }}</span>
        </small>
      </div>
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
      <div class="d-flex justify-content-end align-items-center gap-2 mb-2 flex-wrap">
        <select
          class="form-select form-select-sm"
          style="width: auto"
          :value="detail.assigned_to_user_id ?? ''"
          @change="handleAssign(($event.target as HTMLSelectElement).value)"
        >
          <option value="">Sin asignar</option>
          <option v-for="u in props.assignableUsers" :key="u.value" :value="u.value">{{ u.label }}</option>
        </select>
        <button class="btn btn-sm" :class="detail.ignored_at ? 'btn-outline-secondary' : 'btn-outline-dark'" @click="handleToggleIgnored">
          <i class="fas" :class="detail.ignored_at ? 'fa-eye' : 'fa-eye-slash'"></i>
          {{ detail.ignored_at ? 'Reactivar' : 'Ignorar' }}
        </button>
        <button class="btn btn-sm" :class="detail.reviewed_at ? 'btn-outline-secondary' : 'btn-success'" @click="handleToggleReviewed">
          <i class="fas" :class="detail.reviewed_at ? 'fa-times' : 'fa-check'"></i>
          {{ detail.reviewed_at ? 'Marcar como no revisado' : 'Marcar como revisado' }}
        </button>
      </div>

      <div v-if="detail.ignored_at" class="alert alert-secondary py-2 px-3 mb-3 small">
        <i class="fas fa-eye-slash me-1"></i>
        Ignorado{{ detail.ignored_by ? ` por ${detail.ignored_by}` : '' }} — no cuenta en las stats ni en las alertas.
      </div>

      <div class="row g-3">
        <div class="col-md-6">
          <small class="text-muted d-block">Primera vez</small>
          <template v-if="firstSeenInfo">
            <span>{{ firstSeenInfo.local }} <span class="text-muted small">({{ firstSeenInfo.localAbbrev }})</span></span>
            <small v-if="firstSeenInfo.differsFromServer" class="text-muted d-block log-tz-subinfo">
              <i class="fas fa-globe-americas me-1"></i>Servidor ({{ firstSeenInfo.serverTimezone }}): {{ firstSeenInfo.server }}
            </small>
          </template>
          <span v-else>—</span>
        </div>
        <div class="col-md-6">
          <small class="text-muted d-block">Última vez</small>
          <template v-if="lastSeenInfo">
            <span>{{ lastSeenInfo.local }} <span class="text-muted small">({{ lastSeenInfo.localAbbrev }})</span></span>
            <small v-if="lastSeenInfo.differsFromServer" class="text-muted d-block log-tz-subinfo">
              <i class="fas fa-globe-americas me-1"></i>Servidor ({{ lastSeenInfo.serverTimezone }}): {{ lastSeenInfo.server }}
            </small>
          </template>
          <span v-else>—</span>
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
          <span v-if="reviewedInfo" class="text-success small">
            <i class="fas fa-check-circle me-1"></i>{{ reviewedInfo.local }}{{ detail.reviewed_by ? ` — ${detail.reviewed_by}` : '' }}
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

        <!-- Logs relacionados: mismo usuario, ventana de ±10 min -->
        <div v-if="relatedUserId" class="col-12">
          <small class="text-muted d-block mb-1">
            Otros logs de este usuario (±10 min)
            <span v-if="relatedLoading" class="spinner-border spinner-border-sm ms-1" style="width:0.7rem;height:0.7rem"></span>
          </small>
          <ul v-if="relatedLogs.length" class="log-related-list list-unstyled mb-0">
            <li v-for="rel in relatedLogs" :key="rel.id" class="log-related-item">
              <button type="button" class="log-related-btn" @click="openDetail(rel.id)">
                <LogLevelBadge :level="rel.level" />
                <span class="log-related-message text-truncate">{{ rel.message }}</span>
                <span class="log-related-date text-muted">{{ rel.date ? describeDateTime(rel.date, detail.server_timezone)?.localTime : '' }}</span>
              </button>
            </li>
          </ul>
          <span v-else-if="!relatedLoading" class="text-muted small">Sin otros logs de este usuario en esta ventana de tiempo.</span>
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

.log-retention-info {
  font-size: 0.78rem;
  color: var(--ds-text-muted, #6c757d);
}

.log-stat-tile-btn {
  display: block;
  width: 100%;
  padding: 0;
  background: none;
  border: none;
  border-radius: 12px;
  cursor: pointer;
  text-align: left;
}

.log-stat-tile-btn:hover {
  opacity: 0.85;
}

.log-stat-tile-btn--active {
  outline: 2px solid var(--ds-primary, #6366f1);
  outline-offset: 2px;
  border-radius: 12px;
}

.log-related-list {
  border: 1px solid var(--ds-border, #dee2e6);
  border-radius: 6px;
  overflow: hidden;
}

.log-related-item + .log-related-item {
  border-top: 1px solid var(--ds-border, #dee2e6);
}

.log-related-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  width: 100%;
  padding: 0.4rem 0.6rem;
  background: transparent;
  border: none;
  text-align: left;
  cursor: pointer;
}

.log-related-btn:hover {
  background: var(--ds-surface-2, rgba(0,0,0,.03));
}

.log-related-message {
  flex: 1;
  min-width: 0;
  font-size: 0.8rem;
}

.log-related-date {
  font-size: 0.72rem;
  white-space: nowrap;
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

.log-tz-subinfo {
  font-size: 0.72rem;
  margin-top: 2px;
}

.log-row-date {
  display: flex;
  flex-direction: column;
  white-space: nowrap;
  cursor: default;
}

.log-row-date-date {
  font-size: 0.82rem;
}

.log-row-date-time {
  font-size: 0.72rem;
  color: var(--ds-text-muted, #6c757d);
}

.log-message-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.log-message-meta {
  font-size: 0.72rem;
}
</style>
