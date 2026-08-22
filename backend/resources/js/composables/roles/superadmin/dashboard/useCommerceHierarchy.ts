import { ref } from 'vue'
import axios from 'axios'

export interface CommerceHierarchyRow {
  commerce_id:        string
  commerce_name:       string
  status:              string
  direct_sellers:      number
  subsidiaries_count:  number
  subsidiary_sellers:  number
  total_sellers:       number
}

export interface CommerceHierarchy {
  commerces: CommerceHierarchyRow[]
  totals: { commerce_count: number; total_sellers: number; total_subsidiaries: number }
}

/**
 * Jerarquía real Comercializadora → Agencia → Vendedor, para el panel de
 * SuperAdmin — "Entidades por rol" cuenta comercios/vendedores como bloques
 * planos independientes sin mostrar la relación real entre ellos.
 */
export function useCommerceHierarchy() {
  const hierarchy = ref<CommerceHierarchy | null>(null)
  const loading   = ref(false)
  const error     = ref<string | null>(null)

  async function loadHierarchy() {
    loading.value = true
    error.value   = null
    try {
      const { data } = await axios.get('/superadmin/commerce-hierarchy')
      hierarchy.value = data
    } catch {
      error.value = 'No se pudo cargar la jerarquía de comercios.'
    } finally {
      loading.value = false
    }
  }

  return { hierarchy, loading, error, loadHierarchy }
}
