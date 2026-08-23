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
    const updatingFrequencyId = ref<string | null>(null)

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

    /**
     * Actualiza cron_expression. Devuelve true/false para que el modal que
     * invoca esto sepa si debe cerrarse o mantenerse abierto mostrando el
     * error — errores de validación (422, ej. viola min_interval_seconds)
     * se propagan como excepción para que el llamador lea el mensaje exacto
     * del backend en vez de uno genérico.
     */
    async function updateFrequency(command: ScheduledCommand, cronExpression: string): Promise<void> {
        updatingFrequencyId.value = command.id
        try {
            const { data } = await axios.put(`/system/jobs/scheduler/${command.id}/frequency`, { cron_expression: cronExpression })
            command.cron_expression = cronExpression
            command.frequency = data.data?.frequency ?? command.frequency
            command.is_default_frequency = false
            showToast(data.message, 'success')
        } finally {
            updatingFrequencyId.value = null
        }
    }

    async function resetFrequency(command: ScheduledCommand): Promise<void> {
        updatingFrequencyId.value = command.id
        try {
            const { data } = await axios.delete(`/system/jobs/scheduler/${command.id}/frequency`)
            showToast(data.message, 'success')
            await fetchList()
        } finally {
            updatingFrequencyId.value = null
        }
    }

    /**
     * No toca el estado global de `commands` ni muestra toast — se usa para
     * preview en vivo mientras el usuario escribe (con debounce), así que
     * errores de validación (cron inválido a medio escribir) son esperados
     * y se propagan como excepción para que el llamador los muestre inline.
     */
    async function previewFrequency(command: ScheduledCommand, cronExpression: string): Promise<{ frequency: string, next_runs: string[] }> {
        const { data } = await axios.post(`/system/jobs/scheduler/${command.id}/frequency/preview`, { cron_expression: cronExpression })
        return data.data
    }

    return { commands, loading, togglingId, updatingFrequencyId, setInitial, fetchList, toggle, updateFrequency, resetFrequency, previewFrequency }
}
