import { ref, Ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast, showToastConfirmAction } from '@/js/utils/toastNotify'
import type { BingoPattern } from '@/js/types/games/bingo/pattern/bingo-pattern'

interface UseBingoPatternReturn {
    bingoPatternData: Ref<Partial<BingoPattern>>
    bingoPatterns: Ref<BingoPattern[]>
    setInitialBingoPatterns: (initial: BingoPattern[]) => void
    submit: () => Promise<boolean>
    create: () => void
    edit: (id: string | number) => Promise<boolean>
    reset: () => void
    toggleStatus: (id: string | number) => Promise<boolean>
    toggleStatusBulk: (ids: (string | number)[]) => Promise<boolean>
    remove: (id: string | number) => Promise<boolean>
    removeBulk: (ids: (string | number)[]) => Promise<boolean>
    fetchBingoPatterns: () => Promise<boolean>
}

const emptyBingoPattern = (): Partial<BingoPattern> =>
    Object.freeze({
        name:            '',
        type_pattern:    'UNIQUE' as const,
        default_pattern: 'N' as const,
        boxes:           '',
    })

export function useBingoPattern(): UseBingoPatternReturn {
    const loading          = ref(false)
    const bingoPatternData = ref<Partial<BingoPattern>>(emptyBingoPattern())
    const bingoPatterns    = ref<BingoPattern[]>([])

    function setInitialBingoPatterns(initial: BingoPattern[]) {
        bingoPatterns.value = [...initial]
    }

    async function fetchBingoPatterns(): Promise<boolean> {
        try {
            loading.value = true
            const response = await backendClient.get<{ data: BingoPattern[] }>('/bingo/patterns/fetch-list')
            if (Array.isArray(response.data?.data)) {
                bingoPatterns.value = response.data.data
            }
            return true
        } catch {
            showToast('Error al cargar los patrones', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    async function submit(): Promise<boolean> {
        if (loading.value) return false
        try {
            loading.value = true
            if (bingoPatternData.value.id) {
                await backendClient.patch(
                    `/bingo/patterns/${bingoPatternData.value.id}/update`,
                    bingoPatternData.value
                )
                showToast('Patrón actualizado con éxito', 'success')
            } else {
                await backendClient.post('/bingo/patterns', bingoPatternData.value)
                showToast('Patrón creado con éxito', 'success')
            }
            await fetchBingoPatterns()
            reset()
            return true
        } catch (error: any) {
            showToast(error?.response?.data?.message ?? 'Error al guardar el patrón', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    function create(): void {
        bingoPatternData.value = { ...emptyBingoPattern() }
    }

    async function edit(id: string | number): Promise<boolean> {
        if (loading.value) return false
        try {
            loading.value = true
            const { data } = await backendClient.get<{ data: BingoPattern }>(`/bingo/patterns/${id}/show`)
            bingoPatternData.value = {
                id:              data.data.id,
                name:            data.data.name,
                type_pattern:    data.data.type_pattern,
                default_pattern: data.data.default_pattern,
                boxes:           data.data.boxes,
                status:          data.data.status,
            }
            return true
        } catch {
            showToast('Error al cargar el patrón', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    function reset(): void {
        bingoPatternData.value = { ...emptyBingoPattern() }
    }

    async function toggleStatus(id: string | number): Promise<boolean> {
        const pattern = bingoPatterns.value.find(p => p.id === id)
        const name    = pattern?.name ?? 'este patrón'
        const confirmed = await showToastConfirmAction({
            html:              `¿Quieres cambiar el estado de <b>${name}</b>?`,
            icon:              'warning',
            confirmButtonText: 'Sí, cambiar estado',
        })
        if (!confirmed) return false
        try {
            await backendClient.patch(`/bingo/patterns/${id}/status`)
            showToast('Estado cambiado con éxito', 'success')
            await fetchBingoPatterns()
            return true
        } catch {
            showToast('Error al cambiar el estado', 'error')
            return false
        }
    }

    async function remove(id: string | number): Promise<boolean> {
        const pattern = bingoPatterns.value.find(p => p.id === id)
        const name    = pattern?.name ?? 'este patrón'
        const confirmed = await showToastConfirmAction({
            html:              `¿Estás seguro que deseas eliminar <b>${name}</b>?`,
            icon:              'warning',
            confirmButtonText: 'Sí, eliminar',
        })
        if (!confirmed) return false
        try {
            await backendClient.delete(`/bingo/patterns/${id}/delete`)
            showToast('Patrón eliminado con éxito', 'success')
            await fetchBingoPatterns()
            return true
        } catch {
            showToast('Error al eliminar el patrón', 'error')
            return false
        }
    }

    function buildItemListHtml(ids: (string | number)[]): string {
        const items = ids
            .map(id => bingoPatterns.value.find(p => p.id === id))
            .filter(Boolean)
            .map(p => p!.name)
        return [
            '<div style="max-height:150px;overflow-y:auto;text-align:left;margin-top:10px;padding:10px;background:#f8f9fa;border-radius:6px;">',
            '<ul style="margin:0;padding-left:20px;">',
            ...items.map(name => `<li style="padding:4px 0;">${name}</li>`),
            '</ul></div>',
        ].join('')
    }

    async function toggleStatusBulk(ids: (string | number)[]): Promise<boolean> {
        if (ids.length === 0) return false
        if (ids.length === 1) return toggleStatus(ids[0])
        const confirmed = await showToastConfirmAction({
            html:              `¿Quieres cambiar el estado de estos ${ids.length} patrones?${buildItemListHtml(ids)}`,
            icon:              'warning',
            confirmButtonText: 'Sí, cambiar estado',
        })
        if (!confirmed) return false
        try {
            for (const id of ids) {
                await backendClient.patch(`/bingo/patterns/${id}/status`)
            }
            showToast(`Estado cambiado para ${ids.length} patrones`, 'success')
            await fetchBingoPatterns()
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
            html:              `¿Estás seguro que deseas eliminar estos ${ids.length} patrones?${buildItemListHtml(ids)}`,
            icon:              'warning',
            confirmButtonText: 'Sí, eliminar',
        })
        if (!confirmed) return false
        try {
            for (const id of ids) {
                await backendClient.delete(`/bingo/patterns/${id}/delete`)
            }
            showToast(`${ids.length} patrones eliminados`, 'success')
            await fetchBingoPatterns()
            return true
        } catch {
            showToast('Error al eliminar los patrones', 'error')
            return false
        }
    }

    return {
        bingoPatternData,
        bingoPatterns,
        setInitialBingoPatterns,
        submit,
        create,
        edit,
        reset,
        toggleStatus,
        toggleStatusBulk,
        remove,
        removeBulk,
        fetchBingoPatterns,
    }
}
