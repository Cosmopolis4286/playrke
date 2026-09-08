import { ref } from 'vue'
import axios from 'axios'
import { showToast } from '@js/utils/toast'
import type { Listing, Paginated } from '@js/types/marketplace/listing'

/**
 * Listado de listings activos del Marketplace P2P. Ver
 * MARKETPLACE_ARCHITECTURE_AUDIT.md — mismo patrón que useOpportunityFeed.
 */
export function useMarketplace(initial: Paginated<Listing> | null = null) {
  const listings = ref<Paginated<Listing> | null>(initial)
  const loading   = ref(false)
  const buying    = ref<string | null>(null)
  const error     = ref<string | null>(null)

  async function refresh() {
    loading.value = true
    error.value = null
    try {
      const { data } = await axios.get<Paginated<Listing>>('/marketplace/fetch-list')
      listings.value = data
    } catch {
      error.value = 'No se pudieron cargar los listings del marketplace.'
    } finally {
      loading.value = false
    }
  }

  async function buy(listing: Listing, quantity: number = 1): Promise<{ success: boolean; message: string }> {
    buying.value = listing.id
    try {
      const { data } = await axios.post(`/marketplace/listings/${listing.id}/buy`, { quantity })
      await refresh()
      const message = data.message ?? 'Compra completada.'
      showToast(message, 'success')
      return { success: true, message }
    } catch (e: any) {
      const message = e?.response?.data?.message ?? 'No se pudo completar la compra.'
      showToast(message, 'error')
      return { success: false, message }
    } finally {
      buying.value = null
    }
  }

  return { listings, loading, buying, error, refresh, buy }
}
