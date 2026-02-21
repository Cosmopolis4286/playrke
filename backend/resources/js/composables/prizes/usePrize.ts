import { ref, Ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast, showToastConfirmAction } from '@/js/utils/toastNotify'
import type { Prize, PrizeSearchResult } from '@/js/types/prizes/prize'

/**
 * Contrato público del composable usePrize
 */
interface UsePrizeReturn {
    /** Datos del premio (para crear o editar) */
    prizeData: Ref<Partial<Prize>>

    /** Objetos completos de ítems del kit (con precio para auto-sum) */
    kitItemObjects: Ref<PrizeSearchResult[]>

    /** Lista reactiva de premios */
    prizes: Ref<Prize[]>

    /** Si el usuario puede crear kits (>= 2 premios individuales activos) */
    canCreateKit: Ref<boolean>

    /** Inicializa la lista local y el valor inicial de canCreateKit */
    setInitialPrizes: (initial: Prize[], initialCanCreateKit?: boolean) => void

    /** Envío del formulario */
    submit: () => Promise<boolean>

    /** Modo creación */
    create: () => void

    /** Modo edición */
    edit: (id: string | number) => Promise<boolean>

    /** Reset del formulario */
    reset: () => void

    /** Cambio de estado (individual) */
    toggleStatus: (id: string | number) => Promise<boolean>

    /** Cambio de estado masivo */
    toggleStatusBulk: (ids: (string | number)[]) => Promise<boolean>

    /** Eliminación individual */
    remove: (id: string | number) => Promise<boolean>

    /** Eliminación masiva */
    removeBulk: (ids: (string | number)[]) => Promise<boolean>

    /** Fetch desde backend */
    fetchPrizes: () => Promise<boolean>
}

/**
 * Factory para un premio vacío (el code lo genera el backend automáticamente)
 */
const emptyPrize = (): Partial<Prize> =>
    Object.freeze({
        reward_type: 'PRODUCT',
        name:        '',
        kit:         'N',
        num_items:   1,
        kit_items:   [],
        price:       '',
        description: '',
    })

/**
 * Composable para manejar premios (CRUD + estado local).
 *
 * Responsabilidades:
 * - Control del estado del formulario (create / edit / reset)
 * - Envío de datos (POST / PUT)
 * - Gestión local de la lista
 *
 * ⚠️ No controla UI directamente
 */
export function usePrize(): UsePrizeReturn {
    const loading        = ref(false)
    const prizeData      = ref<Partial<Prize>>(emptyPrize())
    const prizes         = ref<Prize[]>([])
    const kitItemObjects = ref<PrizeSearchResult[]>([])
    const canCreateKit   = ref(false)

    function setInitialPrizes(initial: Prize[], initialCanCreateKit = false) {
        prizes.value      = [...initial]
        canCreateKit.value = initialCanCreateKit
    }

    async function fetchPrizes(): Promise<boolean> {
        try {
            loading.value = true
            const response = await backendClient.get<{
                data: { prizes: { rows: Prize[] }; can_create_kit: boolean }
            }>('/prizes/fetch-list')
            const rows = response.data?.data?.prizes?.rows

            if (Array.isArray(rows)) {
                prizes.value = rows
            }
            if (typeof response.data?.data?.can_create_kit === 'boolean') {
                canCreateKit.value = response.data.data.can_create_kit
            }
            return true
        } catch (error) {
            console.error(error)
            showToast('Error al cargar los premios', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    async function submit(): Promise<boolean> {
        if (loading.value) return false

        try {
            loading.value = true

            if (prizeData.value.id) {
                const updateRes = await backendClient.put<{ affected_kits: number }>(
                    `/prizes/${prizeData.value.id}/update`,
                    prizeData.value
                )
                const affectedKits = updateRes.data?.affected_kits ?? 0
                showToast('Premio actualizado con éxito', 'success')
                if (affectedKits > 0) {
                    const kitWord = affectedKits === 1 ? 'kit' : 'kits'
                    showToast(
                        `Se recalculó el precio de ${affectedKits} ${kitWord} que contenían este premio`,
                        'info'
                    )
                }
            } else {
                await backendClient.post('/prizes', prizeData.value)
                showToast('Premio creado con éxito', 'success')
            }

            await fetchPrizes()
            reset()
            return true
        } catch (error: any) {
            console.error(error)
            showToast(
                error?.response?.data?.message ?? 'Error al guardar el premio',
                'error'
            )
            return false
        } finally {
            loading.value = false
        }
    }

    function create(): void {
        prizeData.value      = { ...emptyPrize() }
        kitItemObjects.value = []
    }

    async function edit(id: string | number): Promise<boolean> {
        if (loading.value) return false

        try {
            loading.value = true
            const { data } = await backendClient.get<{ data: Prize }>(`/prizes/${id}/show`)

            // kit_items_data contains full objects with hashed IDs — use those as source of truth
            const kitData = (data.data.kit_items_data ?? []).map((item: PrizeSearchResult) => ({
                id:    item.id,
                code:  item.code,
                name:  item.name,
                price: item.price,
            }))

            prizeData.value = {
                id:          data.data.id,
                code:        data.data.code,
                slug:        data.data.slug,
                reward_type: data.data.reward_type,
                name:        data.data.name,
                kit:         data.data.kit,
                num_items:   data.data.num_items,
                kit_items:   kitData.map(i => String(i.id)),
                price:       data.data.price,
                description: data.data.description ?? '',
                status:      data.data.status,
            }

            kitItemObjects.value = kitData

            return true
        } catch (error) {
            console.error(error)
            showToast('Error al cargar el premio', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    function reset(): void {
        prizeData.value      = { ...emptyPrize() }
        kitItemObjects.value = []
    }

    async function toggleStatus(id: string | number): Promise<boolean> {
        const prize = prizes.value.find(p => p.id === id)
        const name  = prize?.name ?? 'este premio'

        const confirmed = await showToastConfirmAction({
            html:              `¿Quieres cambiar el estado de <b>${name}</b>?`,
            icon:              'warning',
            confirmButtonText: 'Sí, cambiar estado',
        })

        if (!confirmed) return false

        try {
            await backendClient.put(`/prizes/${id}/status`)
            showToast('Estado cambiado con éxito', 'success')
            await fetchPrizes()
            return true
        } catch {
            showToast('Error al cambiar el estado', 'error')
            return false
        }
    }

    async function remove(id: string | number): Promise<boolean> {
        const prize = prizes.value.find(p => p.id === id)
        const name  = prize?.name ?? 'este premio'

        const confirmed = await showToastConfirmAction({
            html:              `¿Estás seguro que deseas eliminar <b>${name}</b>?`,
            icon:              'warning',
            confirmButtonText: 'Sí, eliminar',
        })

        if (!confirmed) return false

        try {
            await backendClient.delete(`/prizes/${id}`)
            showToast('Premio eliminado con éxito', 'success')
            await fetchPrizes()
            return true
        } catch {
            showToast('Error al eliminar el premio', 'error')
            return false
        }
    }

    function buildItemListHtml(ids: (string | number)[]): string {
        const items = ids
            .map(id => prizes.value.find(p => p.id === id))
            .filter(Boolean)
            .map(p => p!.name)

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

        const confirmed = await showToastConfirmAction({
            html:              `¿Quieres cambiar el estado de estos ${ids.length} premios?${buildItemListHtml(ids)}`,
            icon:              'warning',
            confirmButtonText: 'Sí, cambiar estado',
        })

        if (!confirmed) return false

        try {
            for (const id of ids) {
                await backendClient.put(`/prizes/${id}/status`)
            }
            showToast(`Estado cambiado para ${ids.length} premios`, 'success')
            await fetchPrizes()
            return true
        } catch {
            showToast('Error al cambiar el estado', 'error')
            return false
        }
    }

    async function removeBulk(ids: (string | number)[]): Promise<boolean> {
        if (ids.length === 0) return false
        if (ids.length === 1) return remove(ids[0])

        const confirmed = await showToastConfirmAction({
            html:              `¿Estás seguro que deseas eliminar estos ${ids.length} premios?${buildItemListHtml(ids)}`,
            icon:              'warning',
            confirmButtonText: 'Sí, eliminar',
        })

        if (!confirmed) return false

        try {
            for (const id of ids) {
                await backendClient.delete(`/prizes/${id}`)
            }
            showToast(`${ids.length} premios eliminados`, 'success')
            await fetchPrizes()
            return true
        } catch {
            showToast('Error al eliminar los premios', 'error')
            return false
        }
    }

    return {
        prizeData,
        kitItemObjects,
        prizes,
        canCreateKit,
        setInitialPrizes,
        submit,
        create,
        edit,
        reset,
        toggleStatus,
        toggleStatusBulk,
        remove,
        removeBulk,
        fetchPrizes,
    }
}
