import { ref, Ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast, showToastConfirmAction } from '@/js/utils/toastNotify'

/**
 * Operaciones CRUD de lista compartidas entre salas normales (useBingoRoom) y
 * automáticas (useBingoRoomAutomatic): ambos composables repetían casi
 * exactamente la misma lógica de fetch/copy/toggle/remove (individual y masivo),
 * variando solo el prefijo del endpoint REST.
 *
 * submit/edit NO se incluyen aquí: cada composable mapea su propio
 * BingoRoomFormData (con campos y manejo de errores distintos), así que se
 * dejan como responsabilidad de cada composable específico.
 */
export interface BingoRoomCrudFactoryOptions {
    /** Prefijo del endpoint REST, ej. 'bingo-rooms' o 'bingo-automatic-rooms'. */
    endpoint: string
    /** Etiqueta usada en los mensajes de confirmación/toast, ej. 'sala'. */
    entityLabel?: string
}

export interface BingoRoomListItem {
    id: string | number
    uuid_code?: string | null
}

export function useBingoRoomCrudFactory<TRoom extends BingoRoomListItem>(
    tokenId: string,
    { endpoint, entityLabel = 'sala' }: BingoRoomCrudFactoryOptions
) {
    const loading = ref(false)
    const rooms = ref<TRoom[]>([]) as Ref<TRoom[]>

    function setInitialRooms(initialRooms: TRoom[]) {
        rooms.value = [...initialRooms]
    }

    async function fetchRooms(): Promise<boolean> {
        try {
            loading.value = true

            const response = await backendClient.get<{ data: TRoom[] }>(
                `/${endpoint}/fetch-list`,
                { params: { token: tokenId } }
            )

            if (Array.isArray(response.data?.data)) {
                rooms.value = response.data.data
            }

            return true
        } catch {
            showToast(`Error al cargar las ${entityLabel}s de bingo`, 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    function roomLabel(id: string | number): string {
        return String(rooms.value.find(r => r.id === id)?.uuid_code ?? id)
    }

    function buildItemListHtml(ids: (string | number)[]): string {
        const items = ids
            .map(id => rooms.value.find(r => r.id === id))
            .filter((r): r is TRoom => Boolean(r))
            .map(r => r.uuid_code ?? r.id)

        return `
            <div style="max-height: 150px; overflow-y: auto; text-align: left; margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 6px;">
                <ul style="margin: 0; padding-left: 20px;">
                    ${items.map(code => `<li style="padding: 4px 0;">${code}</li>`).join('')}
                </ul>
            </div>
        `
    }

    async function toggleStatus(id: string | number): Promise<boolean> {
        const confirm = await showToastConfirmAction({
            html: `¿Quieres cambiar el estado de esta ${entityLabel} <b>${roomLabel(id)}</b>?`,
            icon: 'warning',
            confirmButtonText: 'Sí, cambiar estado'
        })

        if (!confirm) return false

        try {
            await backendClient.patch(`/${endpoint}/${id}/status`)
            showToast('Estado cambiado con éxito', 'success')
            await fetchRooms()
            return true
        } catch (error) {
            showToast('Error al cambiar el estado', 'error')
            return false
        }
    }

    async function toggleStatusBulk(ids: (string | number)[]): Promise<boolean> {
        if (ids.length === 0) return false
        if (ids.length === 1) return toggleStatus(ids[0])

        const confirm = await showToastConfirmAction({
            html: `¿Quieres cambiar el estado de estas ${ids.length} ${entityLabel}s?${buildItemListHtml(ids)}`,
            icon: 'warning',
            confirmButtonText: 'Sí, cambiar estado'
        })

        if (!confirm) return false

        const results = await Promise.allSettled(
            ids.map(id => backendClient.patch(`/${endpoint}/${id}/status`))
        )
        const failed = results.filter(r => r.status === 'rejected').length
        await fetchRooms()

        if (failed === 0) {
            showToast(`Estado cambiado con éxito para ${ids.length} ${entityLabel}s`, 'success')
            return true
        }

        const ok = ids.length - failed
        showToast(
            ok > 0
                ? `${ok} ${entityLabel}s actualizadas, ${failed} fallaron`
                : `Error al cambiar el estado`,
            ok > 0 ? 'warning' : 'error'
        )
        return ok > 0
    }

    async function remove(id: string | number): Promise<boolean> {
        const confirm = await showToastConfirmAction({
            html: `¿Estás seguro que deseas eliminar esta ${entityLabel} <b>${roomLabel(id)}</b>?`,
            icon: 'warning',
            confirmButtonText: 'Sí, eliminar'
        })

        if (!confirm) return false

        try {
            await backendClient.delete(`/${endpoint}/${id}/delete`)
            showToast(`${entityLabel[0].toUpperCase()}${entityLabel.slice(1)} eliminada con éxito`, 'success')
            await fetchRooms()
            return true
        } catch (error) {
            showToast(`Error al eliminar la ${entityLabel}`, 'error')
            return false
        }
    }

    async function removeBulk(ids: (string | number)[]): Promise<boolean> {
        if (ids.length === 0) return false
        if (ids.length === 1) return remove(ids[0])

        const confirm = await showToastConfirmAction({
            html: `¿Estás seguro que deseas eliminar estas ${ids.length} ${entityLabel}s?${buildItemListHtml(ids)}`,
            icon: 'warning',
            confirmButtonText: 'Sí, eliminar'
        })

        if (!confirm) return false

        const results = await Promise.allSettled(
            ids.map(id => backendClient.delete(`/${endpoint}/${id}/delete`))
        )
        const failed = results.filter(r => r.status === 'rejected').length
        await fetchRooms()

        if (failed === 0) {
            showToast(`${ids.length} ${entityLabel}s eliminadas con éxito`, 'success')
            return true
        }

        const ok = ids.length - failed
        showToast(
            ok > 0
                ? `${ok} ${entityLabel}s eliminadas, ${failed} fallaron`
                : `Error al eliminar las ${entityLabel}s`,
            ok > 0 ? 'warning' : 'error'
        )
        return ok > 0
    }

    async function copyRoom(id: string | number): Promise<boolean> {
        const confirm = await showToastConfirmAction({
            html: `¿Quieres copiar esta ${entityLabel} <b>${roomLabel(id)}</b>? Se creará como borrador.`,
            icon: 'warning',
            confirmButtonText: 'Sí, copiar'
        })

        if (!confirm) return false

        try {
            await backendClient.post(`/${endpoint}/${id}/copy`)
            showToast(`${entityLabel[0].toUpperCase()}${entityLabel.slice(1)} copiada como borrador exitosamente`, 'success')
            await fetchRooms()
            return true
        } catch (error) {
            showToast(`Error al copiar la ${entityLabel}`, 'error')
            return false
        }
    }

    return {
        loading,
        rooms,
        setInitialRooms,
        fetchRooms,
        toggleStatus,
        toggleStatusBulk,
        remove,
        removeBulk,
        copyRoom,
        buildItemListHtml,
    }
}
