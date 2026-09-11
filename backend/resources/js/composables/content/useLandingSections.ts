import { ref } from 'vue'
import backendClient from '@js/plugins/axios'
import { showToast } from '@js/utils/toastNotify'
import type { LandingSectionAdmin, LandingSectionKey } from '@js/types/content/landing-section'

/**
 * CRUD del admin de secciones de landing — endpoints JSON planos
 * (backendClient.put directo), mismo patrón que useAdminUser.ts, no
 * router.put de Inertia (pensado para respuestas Inertia, no JSON puro).
 */
export function useLandingSections(initialSections: LandingSectionAdmin[]) {
  const sections = ref<LandingSectionAdmin[]>(initialSections)
  const savingKey = ref<LandingSectionKey | null>(null)
  const reordering = ref(false)

  async function updateContent(key: LandingSectionKey, content: Record<string, unknown>): Promise<boolean> {
    savingKey.value = key
    try {
      const { data } = await backendClient.put(`/admin/landing-sections/${key}/update`, { content })
      const index = sections.value.findIndex((s) => s.section_key === key)
      if (index !== -1) sections.value[index] = data.data
      showToast('Sección actualizada con éxito', 'success')
      return true
    } catch (error: any) {
      const message = error?.response?.data?.message ?? 'No se pudo guardar la sección.'
      showToast(message, 'error')
      return false
    } finally {
      savingKey.value = null
    }
  }

  async function toggleStatus(key: LandingSectionKey): Promise<boolean> {
    savingKey.value = key
    try {
      const { data } = await backendClient.put(`/admin/landing-sections/${key}/status`)
      const index = sections.value.findIndex((s) => s.section_key === key)
      if (index !== -1) sections.value[index] = data.data
      showToast('Estado actualizado con éxito', 'success')
      return true
    } catch {
      showToast('No se pudo cambiar el estado de la sección.', 'error')
      return false
    } finally {
      savingKey.value = null
    }
  }

  async function persistOrder(orderedKeys: LandingSectionKey[]): Promise<boolean> {
    reordering.value = true
    try {
      const { data } = await backendClient.put('/admin/landing-sections/reorder', { ordered_keys: orderedKeys })
      sections.value = data.data
      return true
    } catch {
      showToast('No se pudo guardar el nuevo orden.', 'error')
      return false
    } finally {
      reordering.value = false
    }
  }

  return { sections, savingKey, reordering, updateContent, toggleStatus, persistOrder }
}
