<script setup lang="ts">
  /**
   * Página "Jobs y Scheduler" (superadmin, permiso system.jobs).
   * Centro de control único: comandos programados (routes/console.php,
   * activar/desactivar), colas pendientes (Redis) y jobs fallidos
   * (failed_jobs, mismo FailedJobService que ya usa el modal del dashboard).
   */

  import Breadcrumb from '@/js/Components/Common/Breadcrumb/Breadcrumb.vue'
  import SchedulerTable from '@/js/Components/System/Jobs/SchedulerTable.vue'
  import PendingQueuesSummary from '@/js/Components/System/Jobs/PendingQueuesSummary.vue'
  import FailedJobsPanel from '@/js/Components/System/Jobs/FailedJobsPanel.vue'

  import { useScheduledCommands } from '@/js/composables/system/jobs/useScheduledCommands'
  import { usePendingJobs } from '@/js/composables/system/jobs/usePendingJobs'
  import { useFailedJobs } from '@/js/composables/roles/superadmin/dashboard/useFailedJobs'

  import type { JobsCenterPageProps } from '@/js/types/system/jobs'
  import type { ScheduledCommand } from '@/js/types/system/jobs'

  const props = defineProps<JobsCenterPageProps>()

  const {
    commands, loading: schedulerLoading, togglingId,
    setInitial: setInitialCommands, toggle,
  } = useScheduledCommands()
  setInitialCommands(props.scheduledCommands)

  const { queues, setInitial: setInitialQueues } = usePendingJobs()
  setInitialQueues(props.pendingJobs)

  const {
    jobs: failedJobsList, loading: failedJobsLoading, retryingUuid, retryingAll,
    loadJobs: loadFailedJobs, retryJob, retryAll: retryAllFailedJobs, forgetJob,
  } = useFailedJobs('/system/jobs/failed')
  failedJobsList.value = props.failedJobs

  function handleToggle(command: ScheduledCommand, enabled: boolean): void {
    toggle(command, enabled)
  }
</script>

<template>
  <div class="jc-page">
    <Breadcrumb :items="[{ label: 'Sistema' }, { label: 'Jobs y Scheduler' }]" />

    <h2 class="jc-title"><i class="fas fa-gears mr-2"></i>Jobs y Scheduler</h2>
    <p class="jc-subtitle">Comandos programados, colas de trabajo y jobs fallidos del sistema.</p>

    <div class="jc-section">
      <PendingQueuesSummary :queues="queues" />
    </div>

    <div class="jc-section">
      <SchedulerTable
        :commands="commands"
        :loading="schedulerLoading"
        :toggling-id="togglingId"
        @toggle="handleToggle"
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
  </div>
</template>

<style scoped>
.jc-page { padding: 1.5rem; max-width: 1200px; margin: 0 auto; }
.jc-title { font-size: 1.25rem; font-weight: 700; color: var(--ds-text); margin: 1rem 0 0.2rem; }
.jc-subtitle { font-size: 0.85rem; color: var(--ds-text-muted); margin: 0 0 1.5rem; }
.jc-section { margin-bottom: 1.25rem; }
</style>
