import { ref, type Ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import type { PrizeSearchResult } from '@/js/types/prizes/prize'
import type { BingoRoomFormData } from '@/js/types/games/bingo/room/bingo-room'

/* ==========================================================================
 * Modal P/S — selección de prize para ronda
 * ========================================================================== */

export function useBingoRoomPrizeRound(
    form: Ref<BingoRoomFormData>
) {
    const prizeModalRoundIndex = ref<number | null>(null)
    const showPrizeModal = ref(false)
    const showNoPrizesAlert = ref(false)
    const prizeModalSelected = ref<PrizeSearchResult[]>([])

    async function openPrizeModal(index: number) {
        try {
            const { data } = await backendClient.get<{ data: any[] }>('/prizes/search', {
                params: { q: '' }
            })

            const hasPrizes = Array.isArray(data.data) && data.data.length > 0

            if (!hasPrizes) {
                showNoPrizesAlert.value = true
                return
            }
        } catch {
            // Si falla, abrir igual
        }

        prizeModalRoundIndex.value = index

        const current = form.value.roundsConfig?.[index]

        prizeModalSelected.value = current?.prizeId
            ? [
                {
                    id: current.prizeId,
                    name: current.prizeName ?? '',
                    code: '',
                    price: current.prizeValue ?? 0,
                    reward_type: 'PRODUCT'
                }
            ]
            : []

        showPrizeModal.value = true
    }

    function confirmPrizeSelection(): boolean {
        const idx = prizeModalRoundIndex.value
        if (idx === null) return false

        const selected = prizeModalSelected.value[0] ?? null
        const configs = [...(form.value.roundsConfig ?? [])]

        if (!configs[idx]) return false

        configs[idx] = {
            ...configs[idx],
            prizeId: selected ? selected.id : null,
            prizeName: selected ? selected.name : null,
            prizeValue: selected ? selected.price : null
        }

        form.value = {
            ...form.value,
            roundsConfig: configs
        }

        prizeModalRoundIndex.value = null
        return true
    }

    return {
        showPrizeModal,
        showNoPrizesAlert,
        prizeModalSelected,
        openPrizeModal,
        confirmPrizeSelection
    }
}
