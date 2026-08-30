import { ref } from 'vue'
import axios from 'axios'
import { showToast } from '@/js/utils/toastNotify'

export interface ScheduledCommandHistoryEntry {
    type:             'execution' | 'toggle' | 'frequency_change' | 'other_change'
    occurred_at:      string
    server_timezone:  string
    actor:            string | null
    /** Solo en entradas type: 'execution'. */
    status?:          'success' | 'error'
    output?:          string | null
    triggered_by?:    'scheduler' | 'manual'
    /** Solo en entradas type: 'toggle' | 'frequency_change' | 'other_change'. */
    detail?:          string
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
