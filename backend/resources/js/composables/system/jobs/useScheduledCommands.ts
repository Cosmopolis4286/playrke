import { ref } from 'vue'
import axios from 'axios'
import { showToast } from '@/js/utils/toastNotify'
import type { ScheduledCommand } from '@/js/types/system/jobs'

/**
 * Gestión de comandos programados (routes/console.php) desde el panel
 * "Jobs y Scheduler" — toggle persistido en BD, consultado por
 * ScheduledCommandGate antes de que el scheduler corra cada comando.
 */
export function useScheduledCommands() {
    const commands = ref<ScheduledCommand[]>([])
    const loading = ref(false)
    const togglingId = ref<string | null>(null)

    function setInitial(initial: ScheduledCommand[]): void {
        commands.value = initial
    }

    async function fetchList(): Promise<void> {
        loading.value = true
        try {
            const { data } = await axios.get('/system/jobs/scheduler')
            commands.value = data.data ?? []
        } catch (error) {
            console.error(error)
            showToast('No se pudo cargar la lista de comandos programados', 'error')
        } finally {
            loading.value = false
        }
    }

    /** Actualización optimista + rollback si el request falla. */
    async function toggle(command: ScheduledCommand, enabled: boolean): Promise<void> {
        const previous = command.is_enabled
        command.is_enabled = enabled
        togglingId.value = command.id

        try {
            const { data } = await axios.put(`/system/jobs/scheduler/${command.id}/toggle`, { enabled })
            showToast(data.message, 'success')
        } catch (error) {
            command.is_enabled = previous
            console.error(error)
            showToast('No se pudo actualizar el estado del comando', 'error')
        } finally {
            togglingId.value = null
        }
    }

    return { commands, loading, togglingId, setInitial, fetchList, toggle }
}
