import { ref } from 'vue'
import axios from 'axios'
import type { PlayerAsset } from '@js/types/marketplace/listing'

export function useInventory(initial: PlayerAsset[] = []) {
  const assets  = ref<PlayerAsset[]>(initial)
  const loading = ref(false)
  const error   = ref<string | null>(null)

  async function refresh() {
    loading.value = true
    error.value = null
    try {
      const { data } = await axios.get<PlayerAsset[]>('/inventory/fetch-list')
      assets.value = data
    } catch {
      error.value = 'No se pudo cargar tu inventario.'
    } finally {
      loading.value = false
    }
  }

  async function createListing(playerAssetId: string, quantity: number, price: number, currencyId: string) {
    try {
      const { data } = await axios.post('/marketplace/listings', {
        player_asset_id: playerAssetId,
        quantity,
        price,
        currency_id: currencyId,
      })
      await refresh()
      return { success: true, message: data.message ?? 'Publicado.' }
    } catch (e: any) {
      return { success: false, message: e?.response?.data?.message ?? 'No se pudo publicar el activo.' }
    }
  }

  return { assets, loading, error, refresh, createListing }
}
