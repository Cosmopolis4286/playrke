import { ref } from 'vue'
import axios from 'axios'
import type { ProfileDailyMission } from '@js/composables/useProfile'

/**
 * Misiones diarias del jugador para el widget del dashboard — mismo
 * endpoint que ya consume el tab "Misiones" del perfil
 * (GET /profile/daily-missions), reutilizado aquí para visibilidad
 * inmediata sin tener que entrar al perfil.
 */
export function useDailyMissions() {
  const missions = ref<ProfileDailyMission[]>([])
  const loading  = ref(false)
  const error    = ref<string | null>(null)

  async function loadDailyMissions() {
    loading.value = true
    error.value   = null
    try {
      const { data } = await axios.get('/profile/daily-missions')
      if (data.success) missions.value = data.data
    } catch {
      error.value = 'No se pudieron cargar las misiones.'
    } finally {
      loading.value = false
    }
  }

  return { missions, loading, error, loadDailyMissions }
}
