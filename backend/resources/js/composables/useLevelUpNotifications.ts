import echo from '@/js/plugins/echo'
import { showToast } from '@js/utils/toastNotify'

interface NotificationCreatedPayload {
    id: string
    type: string
    title: string
    message: string
    data: Record<string, any>
}

let joinedUserId: string | number | null = null

/**
 * Une al usuario autenticado al canal privado App.Models.User.{id} y muestra
 * un toast instantáneo cuando sube de nivel, desbloquea un logro o completa
 * una misión diaria — antes solo se enteraba al recargar /profile. La
 * persistencia (tabla notifications) y el broadcast ya existen
 * (NotificationService::create() + NotificationCreated); esta función solo
 * reacciona a los tipos que le interesan e ignora el resto (wallet, trivia,
 * etc.), que ya tienen su propio manejo en otros composables/canales.
 *
 * Debe llamarse una sola vez al iniciar la SPA (ver joinOnlinePresence en
 * inertia.ts, mismo patrón), no por componente — de lo contrario Echo
 * registra múltiples listeners redundantes sobre el mismo canal.
 */
const RELEVANT_TYPES = ['LEVEL_UP', 'ACHIEVEMENT_UNLOCKED', 'MISSION_COMPLETED']

export function joinLevelUpNotifications(userId: string | number): void {
    if (joinedUserId === userId) return
    joinedUserId = userId

    echo.private(`App.Models.User.${userId}`)
        .listen('.notification.created', (payload: NotificationCreatedPayload) => {
            if (RELEVANT_TYPES.includes(payload.type)) {
                showToast(`${payload.title} — ${payload.message}`, 'success')
            }
        })
}
