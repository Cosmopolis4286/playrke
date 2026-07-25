<script setup lang="ts">
  import { ref, computed, watch } from 'vue'

  import CardHeader from '@js/Components/Common/Card/CardHeader.vue'
  import TableFilter from '@/js/Components/Common/Table/TableFilter.vue'
  import BaseTable from '@/js/Components/Common/Table/BaseTable.vue'
  import TableFooterInfo from '@/js/Components/Common/Table/TableFooterInfo.vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'

  import { useAudit } from '@/js/composables/audit/useAudit'
  import { useTableController } from '@/js/composables/common/table/useTableController'

  import type { AuditPageProps, AuditDetail } from '@/js/types/audit/audit'

  /* -------------------------------------------------------------------------- */
  /* Props                                                                       */
  /* -------------------------------------------------------------------------- */

  const props = defineProps<AuditPageProps>()

  /* -------------------------------------------------------------------------- */
  /* Composable                                                                  */
  /* -------------------------------------------------------------------------- */

  const { audits, detail, loading, setInitialAudits, fetchList, fetchDetail, clearDetail } = useAudit()

  setInitialAudits(props.audits)

  /* -------------------------------------------------------------------------- */
  /* Tabla                                                                       */
  /* -------------------------------------------------------------------------- */

  const columns = computed(() => audits.value.columns)
  const rows    = computed(() => audits.value.rows)

  const filters = computed(() => [
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

  const { handleFilter, filteredRows } = useTableController({ columns, rows })

  /* -------------------------------------------------------------------------- */
  /* Filtros activos — para recargar desde backend                              */
  /* -------------------------------------------------------------------------- */

  const activeFilters = ref<Record<string, string>>({})
  const showDateFilters = ref(false)
  const dateFrom = ref('')
  const dateTo   = ref('')

  async function onFilter(payload: { search: string; filters: Record<string, string> }): Promise<void> {
    activeFilters.value = payload.filters
    handleFilter(payload)
  }

  async function applyDateFilter(): Promise<void> {
    await fetchList(
      {
        ...activeFilters.value,
        date_from: dateFrom.value || undefined,
        date_to:   dateTo.value   || undefined,
      } as any,
      1
    )
  }

  async function clearDateFilter(): Promise<void> {
    dateFrom.value = ''
    dateTo.value   = ''
    await fetchList(activeFilters.value as any, 1)
  }

  /* -------------------------------------------------------------------------- */
  /* Paginación                                                                  */
  /* -------------------------------------------------------------------------- */

  const totalPages   = computed(() => audits.value.last_page ?? 1)
  const currentPage  = computed(() => audits.value.current_page ?? 1)
  const totalRecords = computed(() => audits.value.total ?? rows.value.length)

  async function goToPage(page: number): Promise<void> {
    await fetchList(
      {
        ...activeFilters.value,
        date_from: dateFrom.value || undefined,
        date_to:   dateTo.value   || undefined,
      } as any,
      page
    )
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
  <div class="card">
    <CardHeader title="Auditoría" subtitle="Registro de actividad del sistema" />

    <div class="card-body">

      <!-- Filtros rápidos por fecha -->
      <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
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

      <!-- Filtros de tabla -->
      <TableFilter
        search-placeholder="Buscar en la auditoría..."
        :filters="filters"
        :debounce-delay="300"
        @filter="onFilter"
      />

      <!-- Tabla -->
      <BaseTable
        table-id="audit-table"
        :columns="columns"
        :rows="filteredRows"
        empty-message="No se encontraron registros de auditoría"
      >
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
      </BaseTable>

      <!-- Footer: conteo + paginación -->
      <div class="d-flex align-items-center justify-content-between mt-3 flex-wrap gap-2">
        <TableFooterInfo
          :filtered-count="filteredRows.length"
          :total-count="totalRecords"
          :selected-count="0"
        />

        <!-- Paginación simple (solo si hay datos del servidor) -->
        <nav v-if="totalPages > 1">
          <ul class="pagination pagination-sm mb-0">
            <li class="page-item" :class="{ disabled: currentPage <= 1 }">
              <button class="page-link" @click="goToPage(currentPage - 1)" :disabled="loading">
                <i class="fas fa-chevron-left"></i>
              </button>
            </li>
            <li
              v-for="page in totalPages"
              :key="page"
              class="page-item"
              :class="{ active: page === currentPage }"
            >
              <button class="page-link" @click="goToPage(page)" :disabled="loading">
                {{ page }}
              </button>
            </li>
            <li class="page-item" :class="{ disabled: currentPage >= totalPages }">
              <button class="page-link" @click="goToPage(currentPage + 1)" :disabled="loading">
                <i class="fas fa-chevron-right"></i>
              </button>
            </li>
          </ul>
        </nav>
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

        <!-- Valores anteriores -->
        <div v-if="detail.old_values" class="col-12">
          <small class="text-muted d-block mb-1">Valores anteriores</small>
          <pre class="bg-light border rounded p-2 small text-danger mb-0" style="max-height:200px;overflow:auto">{{ JSON.stringify(detail.old_values, null, 2) }}</pre>
        </div>

        <!-- Valores nuevos -->
        <div v-if="detail.new_values" class="col-12">
          <small class="text-muted d-block mb-1">Valores nuevos</small>
          <pre class="bg-light border rounded p-2 small text-success mb-0" style="max-height:200px;overflow:auto">{{ JSON.stringify(detail.new_values, null, 2) }}</pre>
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
