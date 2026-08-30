import { ref } from 'vue'
import axios from 'axios'
import { showToast } from '@/js/utils/toastNotify'

export interface ScheduledCommandHistoryEntry {
    status:           'success' | 'error'
    executed_at:      string
    output:           string | null
    server_timezone:  string
}

/** Historial completo (sin truncar) de ejecuciones de un comando programado. */
export function useScheduledCommandHistory() {
    const entries = ref<ScheduledCommandHistoryEntry[]>([])
    const loading = ref(false)

    async function fetchHistory(command: string): Promise<void> {
        loading.value = true
        entries.value = []
        try {
            const { data } = await axios.get(`/system/jobs/scheduler/${encodeURIComponent(command)}/history`)
            entries.value = data.data ?? []
        } catch (error) {
            console.error(error)
            showToast('No se pudo cargar el historial del comando', 'error')
        } finally {
            loading.value = false
        }
    }

    function clear(): void {
        entries.value = []
    }

    return { entries, loading, fetchHistory, clear }
}
