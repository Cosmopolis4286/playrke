import { ref } from 'vue'
import axios from 'axios'
import { showDetailedToast } from '@/js/utils/toastNotify'
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
    const runningId = ref<string | null>(null)

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
            showDetailedToast('No se pudo cargar la lista de comandos', 'Verifica tu conexión e intenta de nuevo.', 'error')
        } finally {
            loading.value = false
        }
    }

    /** Actualización optimista + rollback si el request falla. */
    async function toggle(command: ScheduledCommand, enabled: boolean): Promise<void> {
        const previous = command.is_enabled
        command.is_enabled = enabled
        togglingId.value = command.id
        const label = command.label ?? command.command

        try {
            await axios.put(`/system/jobs/scheduler/${command.id}/toggle`, { enabled })
            showDetailedToast(
                enabled ? `"${label}" activado` : `"${label}" desactivado`,
                enabled ? 'Volverá a ejecutarse automáticamente según su frecuencia.' : 'No se ejecutará automáticamente hasta que se reactive.',
                enabled ? 'success' : 'warning'
            )
        } catch (error) {
            command.is_enabled = previous
            console.error(error)
            showDetailedToast(`No se pudo actualizar "${label}"`, 'El estado no cambió — intenta de nuevo.', 'error')
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
        const label = command.label ?? command.command
        try {
            const { data } = await axios.put(`/system/jobs/scheduler/${command.id}/frequency`, { cron_expression: cronExpression })
            command.cron_expression = cronExpression
            command.frequency = data.data?.frequency ?? command.frequency
            command.is_default_frequency = false
            showDetailedToast(`Frecuencia de "${label}" actualizada`, `Ahora corre: ${command.frequency}`, 'success')
        } finally {
            updatingFrequencyId.value = null
        }
    }

    async function resetFrequency(command: ScheduledCommand): Promise<void> {
        updatingFrequencyId.value = command.id
        const label = command.label ?? command.command
        try {
            await axios.delete(`/system/jobs/scheduler/${command.id}/frequency`)
            showDetailedToast(`Frecuencia de "${label}" restablecida`, 'Vuelve a usar la cadencia por defecto del sistema.', 'success')
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
    async function previewFrequency(command: ScheduledCommand, cronExpression: string): Promise<{ frequency: string, next_runs: string[], server_timezone: string }> {
        const { data } = await axios.post(`/system/jobs/scheduler/${command.id}/frequency/preview`, { cron_expression: cronExpression })
        return data.data
    }

    /**
     * "Ejecutar ahora" — dispara inmediatamente el comando ya catalogado (no
     * acepta texto libre, ver JobsCenterController::runScheduledCommand). El
     * caller es responsable de pedir confirmación antes de invocar esto.
     */
    async function runNow(command: ScheduledCommand): Promise<void> {
        runningId.value = command.id
        const label = command.label ?? command.command
        try {
            const { data } = await axios.post(`/system/jobs/scheduler/${command.id}/run`)
            const output: string | null = data.data?.output?.trim() || null
            showDetailedToast(
                data.success ? `"${label}" ejecutado correctamente` : `"${label}" finalizó con error`,
                output,
                data.success ? 'success' : 'error'
            )
            await fetchList()
        } catch (error) {
            console.error(error)
            const backendMessage = (error as { response?: { data?: { message?: string } } })?.response?.data?.message ?? null
            showDetailedToast(`No se pudo ejecutar "${label}"`, backendMessage, 'error')
        } finally {
            runningId.value = null
        }
    }

    return { commands, loading, togglingId, updatingFrequencyId, runningId, setInitial, fetchList, toggle, updateFrequency, resetFrequency, previewFrequency, runNow }
}
