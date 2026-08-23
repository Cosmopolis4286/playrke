import { ref } from 'vue'
import axios from 'axios'

export interface UserLevelInfo {
  level:                    number
  current_xp:               number
  total_xp:                 number
  xp_to_next_level:         number
  xp_needed_for_next_level: number
  progress_percentage:      number
  leveled_up_at:            string | null
}

export interface AchievementStats {
  total_achievements?:         number
  unlocked_count?:             number
  unseen_count?:                number
  completion_percentage?:      number
  total_xp_from_achievements?: number
  categories?: Record<string, { total: number; unlocked: number; percentage: number }>
}

/**
 * Nivel/XP y logros del jugador — el backend ya expone la API completa
 * (/profile/level, /profile/achievements/stats) desde antes; este
 * dashboard nunca la consumía pese a que el sistema de gamificación ya
 * existe completo.
 */
export function useGamification() {
  const levelInfo = ref<UserLevelInfo | null>(null)
  const achievementStats = ref<AchievementStats | null>(null)
  const loading = ref(false)
  const error   = ref<string | null>(null)

  async function loadGamification() {
    loading.value = true
    error.value   = null
    try {
      const [levelRes, statsRes] = await Promise.all([
        axios.get('/profile/level'),
        axios.get('/profile/achievements/stats'),
      ])
      levelInfo.value = levelRes.data?.data ?? null
      achievementStats.value = statsRes.data?.data ?? null
    } catch {
      error.value = 'No se pudo cargar tu progreso.'
    } finally {
      loading.value = false
    }
  }

  return { levelInfo, achievementStats, loading, error, loadGamification }
}
