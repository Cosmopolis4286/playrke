import { ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import type { SystemHealth } from '@/js/types/roles/superadmin/dashboard/dashboard-superadmin'

interface SystemHealthResponse extends SystemHealth {
    success: boolean
}

/**
 * Auto-refresh del widget "Salud del sistema" — mismo patrón de
 * startPolling()/stopPolling() que useSystemLogs.ts
 * (backend/resources/js/composables/audit/useSystemLogs.ts:152-191), pero
 * en vez de pedir un delta reemplaza el snapshot completo en cada tick
 * (GET /superadmin/system-health, payload chico, cacheado 20s server-side).
 *
 * Se pausa cuando la pestaña no está visible (sin precedente en el
 * proyecto) para no gastar requests con el dashboard en background.
 */
export function useSystemHealthPolling(initial: SystemHealth) {
    const health = ref<SystemHealth>(initial)
    const refreshing = ref(false)
    const lastCheckedAt = ref<string | null>(initial.checkedAt ?? null)

    let pollTimer: ReturnType<typeof setInterval> | null = null
    let visibilityHandler: (() => void) | null = null

    async function refresh(): Promise<void> {
        refreshing.value = true
        try {
            const { data } = await backendClient.get<SystemHealthResponse>('/superadmin/system-health')
            const { success, ...rest } = data
            health.value = rest
            lastCheckedAt.value = rest.checkedAt ?? new Date().toISOString()
        } catch (error) {
            // Silencioso a propósito (mismo criterio que checkForNewLogs()):
            // un fallo de polling no debe interrumpir al admin, el siguiente
            // tick reintenta solo.
            console.error(error)
        } finally {
            refreshing.value = false
        }
    }

    function startPolling(intervalMs: number = 30_000): void {
        stopPolling()
        pollTimer = setInterval(refresh, intervalMs)

        if (typeof document !== 'undefined' && !visibilityHandler) {
            visibilityHandler = () => {
                if (document.hidden) {
                    if (pollTimer !== null) {
                        clearInterval(pollTimer)
                        pollTimer = null
                    }
                } else {
                    refresh()
                    if (pollTimer === null) {
                        pollTimer = setInterval(refresh, intervalMs)
                    }
                }
            }
            document.addEventListener('visibilitychange', visibilityHandler)
        }
    }

    function stopPolling(): void {
        if (pollTimer !== null) {
            clearInterval(pollTimer)
            pollTimer = null
        }
        if (visibilityHandler) {
            document.removeEventListener('visibilitychange', visibilityHandler)
            visibilityHandler = null
        }
    }

    return {
        health,
        refreshing,
        lastCheckedAt,
        refresh,
        startPolling,
        stopPolling,
    }
}
