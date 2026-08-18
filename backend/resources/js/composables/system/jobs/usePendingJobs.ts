import { ref } from 'vue'
import axios from 'axios'
import { showToast } from '@/js/utils/toastNotify'
import type { PendingQueueSummary } from '@/js/types/system/jobs'

/** Resumen de jobs pendientes por cola Redis (default, bingo-draw, bingo-refunds). */
export function usePendingJobs() {
    const queues = ref<PendingQueueSummary[]>([])
    const loading = ref(false)

    function setInitial(initial: PendingQueueSummary[]): void {
        queues.value = initial
    }

    async function fetchSummary(): Promise<void> {
        loading.value = true
        try {
            const { data } = await axios.get('/system/jobs/pending')
            queues.value = data.data ?? []
        } catch (error) {
            console.error(error)
            showToast('No se pudo cargar el resumen de colas', 'error')
        } finally {
            loading.value = false
        }
    }

    return { queues, loading, setInitial, fetchSummary }
}
