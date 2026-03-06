import { ref, Ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast, showToastConfirmAction } from '@/js/utils/toastNotify'
import { showPasswordModal } from '@/js/helpers/user/password'
import type { Player } from '@/js/types/roles/player/player'

interface UseAdminPlayerReturn {
    playerData: Ref<Partial<Player>>
    players: Ref<Player[]>
    setInitialPlayers: (initial: Player[]) => void
    submit: () => Promise<boolean>
    create: () => void
    edit: (id: string | number) => Promise<boolean>
    reset: () => void
    toggleStatus: (id: string | number) => Promise<boolean>
    toggleStatusBulk: (ids: (string | number)[]) => Promise<boolean>
    resetPassword: (id: string | number) => Promise<boolean>
    remove: (id: string | number) => Promise<boolean>
    removeBulk: (ids: (string | number)[]) => Promise<boolean>
    fetchPlayers: () => Promise<boolean>
}

const emptyPlayer = (): Partial<Player> =>
    Object.freeze({
        username:    '',
        email:       '',
        first_name:  '',
        last_name:   '',
        phone:       '',
        country_id:  null,
        time_zone_id: null,
    })

export function useAdminPlayer(): UseAdminPlayerReturn {
    const loading   = ref(false)
    const playerData = ref<Partial<Player>>(emptyPlayer())
    const players   = ref<Player[]>([])

    function setInitialPlayers(initial: Player[]) {
        players.value = [...initial]
    }

    function reset(): void {
        playerData.value = { ...emptyPlayer() }
    }

    const create = (): void => { reset() }

    async function fetchPlayers(): Promise<boolean> {
        try {
            loading.value = true
            const response = await backendClient.get<{ data: Player[] }>('/admin/player/fetch-list')
            if (Array.isArray(response.data?.data)) {
                players.value = response.data.data
            }
            return true
        } catch (error) {
            console.error(error)
            showToast('Error al cargar los jugadores', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    function buildPayload(): Record<string, unknown> {
        const { country_id, time_zone_id, phone, ...rest } = playerData.value
        return {
            ...rest,
            country:   country_id,
            time_zone: time_zone_id,
            phone_number: phone,
        }
    }

    async function submit(): Promise<boolean> {
        if (loading.value) return false
        try {
            loading.value = true
            const payload = buildPayload()

            if (playerData.value.id) {
                await backendClient.put(`/admin/player/${playerData.value.id}/update`, payload)
                showToast('Actualización exitosa', 'success')
            } else {
                const { data } = await backendClient.post('/admin/player', payload)
                showToast('Jugador creado con éxito', 'success')
                await showPasswordModal(data.password)
            }

            await fetchPlayers()
            reset()
            return true
        } catch (error: any) {
            console.error(error)
            showToast(error?.response?.data?.message ?? 'Error al guardar el jugador', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    async function edit(id: string | number): Promise<boolean> {
        if (loading.value) return false
        try {
            loading.value = true
            const { data } = await backendClient.get<{ data: Record<string, any> }>(`/admin/player/${id}/show`)
            const p = data.data

            playerData.value = {
                id:           p.id,
                username:     p.username,
                email:        p.email,
                first_name:   p.first_name,
                last_name:    p.last_name,
                phone:        p.phone,
                country_id:   p.country?.id ?? null,
                time_zone_id: p.time_zone_id ?? null,
            }
            return true
        } catch (error) {
            console.error(error)
            showToast('Error al cargar el jugador', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    async function toggleStatus(id: string | number): Promise<boolean> {
        const player = players.value.find(p => p.id === id)
        const name = player ? `${player.first_name} ${player.last_name}` : 'este jugador'

        const confirm = await showToastConfirmAction({
            html: `¿Quieres cambiar el estado de <b>${name}</b>?`,
            icon: 'warning',
            confirmButtonText: 'Sí, cambiar estado'
        })
        if (!confirm) return false

        try {
            await backendClient.put(`/admin/player/${id}/status`)
            showToast('Estado cambiado con éxito', 'success')
            await fetchPlayers()
            return true
        } catch {
            showToast('Error al cambiar el estado', 'error')
            return false
        }
    }

    async function resetPassword(id: string | number): Promise<boolean> {
        const player = players.value.find(p => p.id === id)
        const name = player ? `${player.first_name} ${player.last_name}` : 'este jugador'

        if (player?.status !== 'ACTIVE') {
            showToast('Solo se puede resetear la contraseña de jugadores activos', 'warning')
            return false
        }

        const confirm = await showToastConfirmAction({
            html: `¿Deseas resetear la contraseña de <b>${name}</b>?`,
            icon: 'warning',
            confirmButtonText: 'Sí, resetear'
        })
        if (!confirm) return false

        try {
            const { data } = await backendClient.put(`/admin/player/${id}/reset-password`)
            showToast(data.message ?? 'Contraseña reseteada', 'success')
            await showPasswordModal(data.password)
            return true
        } catch (error) {
            console.error(error)
            showToast('Error al resetear la contraseña', 'error')
            return false
        }
    }

    async function remove(id: string | number): Promise<boolean> {
        const player = players.value.find(p => p.id === id)
        const name = player ? `${player.first_name} ${player.last_name}` : 'este jugador'

        const confirm = await showToastConfirmAction({
            html: `¿Estás seguro que deseas eliminar a <b>${name}</b>?`,
            icon: 'warning',
            confirmButtonText: 'Sí, eliminar'
        })
        if (!confirm) return false

        try {
            await backendClient.put(`/admin/player/${id}/delete`)
            showToast('Jugador eliminado con éxito', 'success')
            await fetchPlayers()
            return true
        } catch {
            showToast('Error al eliminar el jugador', 'error')
            return false
        }
    }

    function buildItemListHtml(ids: (string | number)[]): string {
        const items = ids
            .map(id => players.value.find(p => p.id === id))
            .filter(Boolean)
            .map(p => `${p!.first_name} ${p!.last_name}`)

        return `
            <div style="max-height:150px;overflow-y:auto;text-align:left;margin-top:10px;padding:10px;background:#f8f9fa;border-radius:6px;">
                <ul style="margin:0;padding-left:20px;">
                    ${items.map(name => `<li style="padding:4px 0;">${name}</li>`).join('')}
                </ul>
            </div>
        `
    }

    async function toggleStatusBulk(ids: (string | number)[]): Promise<boolean> {
        if (ids.length === 0) return false
        if (ids.length === 1) return toggleStatus(ids[0])

        const confirm = await showToastConfirmAction({
            html: `¿Quieres cambiar el estado de estos ${ids.length} jugadores?${buildItemListHtml(ids)}`,
            icon: 'warning',
            confirmButtonText: 'Sí, cambiar estado'
        })
        if (!confirm) return false

        try {
            for (const id of ids) await backendClient.put(`/admin/player/${id}/status`)
            showToast(`Estado cambiado para ${ids.length} jugadores`, 'success')
            await fetchPlayers()
            return true
        } catch {
            showToast('Error al cambiar el estado', 'error')
            return false
        }
    }

    async function removeBulk(ids: (string | number)[]): Promise<boolean> {
        if (ids.length === 0) return false
        if (ids.length === 1) return remove(ids[0])

        const confirm = await showToastConfirmAction({
            html: `¿Estás seguro que deseas eliminar estos ${ids.length} jugadores?${buildItemListHtml(ids)}`,
            icon: 'warning',
            confirmButtonText: 'Sí, eliminar'
        })
        if (!confirm) return false

        try {
            for (const id of ids) await backendClient.put(`/admin/player/${id}/delete`)
            showToast(`${ids.length} jugadores eliminados con éxito`, 'success')
            await fetchPlayers()
            return true
        } catch {
            showToast('Error al eliminar los jugadores', 'error')
            return false
        }
    }

    return {
        playerData,
        players,
        setInitialPlayers,
        submit,
        create,
        edit,
        reset,
        toggleStatus,
        toggleStatusBulk,
        resetPassword,
        remove,
        removeBulk,
        fetchPlayers,
    }
}
