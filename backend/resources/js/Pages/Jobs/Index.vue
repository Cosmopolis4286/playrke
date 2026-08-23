<script setup lang="ts">
  /**
   * Página "Jobs y Scheduler" (superadmin, permiso system.jobs).
   * Centro de control único: comandos programados (routes/console.php,
   * activar/desactivar), colas pendientes (Redis) y jobs fallidos
   * (failed_jobs, mismo FailedJobService que ya usa el modal del dashboard).
   */

  import { onMounted, onUnmounted, ref } from 'vue'
  import Breadcrumb from '@/js/Components/Common/Breadcrumb/Breadcrumb.vue'
  import SchedulerTable from '@/js/Components/System/Jobs/SchedulerTable.vue'
  import PendingQueuesSummary from '@/js/Components/System/Jobs/PendingQueuesSummary.vue'
  import FailedJobsPanel from '@/js/Components/System/Jobs/FailedJobsPanel.vue'
  import ScheduledCommandHistoryModal from '@/js/Components/System/Jobs/ScheduledCommandHistoryModal.vue'
  import FrequencyEditorModal from '@/js/Components/System/Jobs/FrequencyEditorModal.vue'

  import { useScheduledCommands } from '@/js/composables/system/jobs/useScheduledCommands'
  import { usePendingJobs } from '@/js/composables/system/jobs/usePendingJobs'
  import { useFailedJobs } from '@/js/composables/roles/superadmin/dashboard/useFailedJobs'
  import { useScheduledCommandHistory } from '@/js/composables/system/jobs/useScheduledCommandHistory'
  import { showToast } from '@/js/utils/toastNotify'

  import type { JobsCenterPageProps, ScheduledCommand } from '@/js/types/system/jobs'

  const props = defineProps<JobsCenterPageProps>()

  const {
    commands, loading: schedulerLoading, togglingId, updatingFrequencyId,
    setInitial: setInitialCommands, fetchList: refetchCommands, toggle,
    updateFrequency, resetFrequency, previewFrequency,
  } = useScheduledCommands()
  setInitialCommands(props.scheduledCommands)

  const { queues, loading: queuesLoading, setInitial: setInitialQueues, fetchSummary: refetchQueues } = usePendingJobs()
  setInitialQueues(props.pendingJobs)

  const {
    jobs: failedJobsList, loading: failedJobsLoading, retryingUuid, retryingAll,
    loadJobs: refetchFailedJobs, retryJob, retryAll: retryAllFailedJobs, forgetJob,
  } = useFailedJobs('/system/jobs/failed')
  failedJobsList.value = props.failedJobs

  // Historial de un comando (modal)
  const showHistoryModal = ref(false)
  const historyCommand = ref<ScheduledCommand | null>(null)
  const { entries: historyEntries, loading: historyLoading, fetchHistory, clear: clearHistory } = useScheduledCommandHistory()

  function openHistory(command: ScheduledCommand): void {
    historyCommand.value = command
    showHistoryModal.value = true
    fetchHistory(command.command)
  }

  function closeHistory(open: boolean): void {
    showHistoryModal.value = open
    if (!open) {
      historyCommand.value = null
      clearHistory()
    }
  }

  // Edición de frecuencia (modal)
  const showFrequencyModal = ref(false)
  const frequencyCommand = ref<ScheduledCommand | null>(null)

  function openFrequencyEditor(command: ScheduledCommand): void {
    frequencyCommand.value = command
    showFrequencyModal.value = true
  }

  async function handleSaveFrequency(cronExpression: string): Promise<void> {
    if (!frequencyCommand.value) return
    try {
      await updateFrequency(frequencyCommand.value, cronExpression)
      showFrequencyModal.value = false
    } catch (error: any) {
      console.error(error)
      const firstError = error?.response?.data?.errors?.cron_expression?.[0]
      showToast(firstError ?? 'No se pudo actualizar la frecuencia.', 'error')
    }
  }

  async function handleResetFrequency(): Promise<void> {
    if (!frequencyCommand.value) return
    try {
      await resetFrequency(frequencyCommand.value)
      showFrequencyModal.value = false
    } catch (error) {
      console.error(error)
      showToast('No se pudo restablecer la frecuencia.', 'error')
    }
  }

  // Auto-refresh cada 30s — los datos son operativos (scheduler corre cada
  // minuto como mínimo), sin esto la página queda desactualizada apenas se
  // deja abierta un rato. No se usa WebSocket: no hay evento backend dedicado
  // para estos 3 dominios, y polling cada 30s es más que suficiente dado que
  // nada aquí cambia más rápido que 1 vez por minuto.
  //
  // Los 3 composables (useScheduledCommands, usePendingJobs, useFailedJobs)
  // ya capturan sus propios errores de red y muestran un toast, así que
  // Promise.all() aquí nunca rechaza. document.hidden evita refrescos (y
  // sus toasts de error, si la red falla) mientras la pestaña está en
  // background — sin esto, dejar la pestaña abierta y minimizada seguía
  // generando tráfico y notificaciones que nadie ve.
  const lastRefreshedAt = ref(new Date())
  let refreshTimer: ReturnType<typeof setInterval> | null = null

  async function refreshAll(): Promise<void> {
    if (document.hidden) return
    await Promise.all([refetchCommands(), refetchQueues(), refetchFailedJobs()])
    lastRefreshedAt.value = new Date()
  }

  onMounted(() => {
    refreshTimer = setInterval(refreshAll, 30_000)
  })
  onUnmounted(() => {
    if (refreshTimer) clearInterval(refreshTimer)
  })
</script>

<template>
  <div class="jc-page">
    <Breadcrumb :items="[{ label: 'Sistema' }, { label: 'Jobs y Scheduler' }]" />

    <div class="jc-title-row">
      <div>
        <h2 class="jc-title"><i class="fas fa-gears mr-2"></i>Jobs y Scheduler</h2>
        <p class="jc-subtitle">Comandos programados, colas de trabajo y jobs fallidos del sistema.</p>
      </div>
      <div class="jc-refresh-info">
        <i class="fas fa-rotate"></i>
        Actualizado {{ lastRefreshedAt.toLocaleTimeString('es-VE') }}
      </div>
    </div>

    <div class="jc-section">
      <PendingQueuesSummary :queues="queues" :refreshing="queuesLoading" />
    </div>

    <div class="jc-section">
      <SchedulerTable
        :commands="commands"
        :loading="schedulerLoading"
        :toggling-id="togglingId"
        @toggle="toggle"
        @view-history="openHistory"
        @edit-frequency="openFrequencyEditor"
      />
    </div>

    <div class="jc-section">
      <FailedJobsPanel
        :jobs="failedJobsList"
        :loading="failedJobsLoading"
        :retrying-uuid="retryingUuid"
        :retrying-all="retryingAll"
        @retry="retryJob"
        @retry-all="retryAllFailedJobs"
        @forget="forgetJob"
      />
    </div>

    <ScheduledCommandHistoryModal
      v-model="showHistoryModal"
      :command="historyCommand"
      :entries="historyEntries"
      :loading="historyLoading"
      @update:model-value="closeHistory"
    />

    <FrequencyEditorModal
      v-model="showFrequencyModal"
      :command="frequencyCommand"
      :saving="frequencyCommand ? updatingFrequencyId === frequencyCommand.id : false"
      :fetch-preview="previewFrequency"
      @save="handleSaveFrequency"
      @reset="handleResetFrequency"
    />
  </div>
</template>

<style scoped>
.jc-page { padding: 1.5rem; max-width: 1200px; margin: 0 auto; }
.jc-title-row { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; flex-wrap: wrap; margin: 1rem 0 1.5rem; }
.jc-title { font-size: 1.25rem; font-weight: 700; color: var(--ds-text); margin: 0 0 0.2rem; }
.jc-subtitle { font-size: 0.85rem; color: var(--ds-text-muted); margin: 0; }
.jc-refresh-info { font-size: 0.76rem; color: var(--ds-text-muted); white-space: nowrap; }
.jc-section { margin-bottom: 1.25rem; }
</style>
