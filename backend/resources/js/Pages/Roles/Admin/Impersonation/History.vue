<script setup lang="ts">
  /**
   * Historial consolidado de sesiones de impersonación (soporte a
   * jugadores) — solo lectura, SuperAdmin. Cada fila empareja el evento
   * impersonation_started con su impersonation_ended (si existe); sin
   * "ended" se marca como sesión activa (ver ImpersonationController::resolveHistory()).
   */

  import { computed } from 'vue'

  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'

  import { useImpersonationHistory } from '@/js/composables/roles/admin/impersonation/useImpersonationHistory'

  import type { ImpersonationHistoryPageProps } from '@/js/types/roles/admin/impersonation'
  import type { ServerQuery } from '@/js/composables/common/table/useTableController'

  const props = defineProps<ImpersonationHistoryPageProps>()

  const { loading, history, setInitialHistory, onQueryChange } = useImpersonationHistory()
  setInitialHistory(props.history)

  const rows = computed(() => history.value.rows)

  async function handleQueryChange(query: ServerQuery): Promise<void> {
    await onQueryChange(query)
  }

  function formatDuration(minutes: number | null): string {
    if (minutes === null) return '—'
    if (minutes < 60) return `${minutes} min`
    const hours = Math.floor(minutes / 60)
    const rest = minutes % 60
    return `${hours}h ${rest}min`
  }
</script>

<template>
  <TableWithFilterBridge
    table-id="impersonation-history-table"
    title="Historial de impersonación"
    subtitle="Sesiones de soporte iniciadas por administradores sobre cuentas de jugadores"
    :columns="history.columns"
    :rows="rows"
    empty-message="Sin sesiones de impersonación registradas"
    :loading="loading"
    server-side
    :server-total-pages="history.last_page ?? 1"
    :show-create-button="false"
    :show-edit-button="false"
    :show-delete-button="false"
    @query-change="handleQueryChange"
  >
    <!-- Columna: Estado -->
    <template #cell-is_active="{ row, value }">
      <span v-if="value" class="badge bg-warning text-dark">
        <i class="fas fa-circle-dot me-1"></i>Activa
      </span>
      <span v-else class="badge bg-secondary">
        Finalizada <template v-if="row.duration_minutes !== null">· {{ formatDuration(row.duration_minutes) }}</template>
      </span>
    </template>
  </TableWithFilterBridge>
</template>
