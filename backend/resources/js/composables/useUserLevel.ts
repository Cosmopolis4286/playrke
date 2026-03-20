import { ref, computed, readonly } from 'vue'
import { backendClient as axios } from '../../plugins/axios'

export interface UserLevel {
  level: number
  current_xp: number
  total_xp: number
  xp_to_next_level: number
  xp_needed_for_next_level: number
  progress_percentage: number
  leveled_up_at?: string
}

export interface LeaderboardEntry {
  user: {
    id: string
    username: string
    full_name: string
    profile_photo_url?: string
  }
  level: number
  current_xp: number
  total_xp: number
  progress_percentage: number
  rank?: number
}

export function useUserLevel() {
  const userLevel = ref<UserLevel | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const leaderboard = ref<LeaderboardEntry[]>([])

  const fetchUserLevel = async () => {
    loading.value = true
    error.value = null
    
    try {
      const response = await axios.get('/profile/level')
      userLevel.value = response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch user level'
      console.error('Error fetching user level:', err)
    } finally {
      loading.value = false
    }
  }

  const addXp = async (amount: number, reason?: string) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await axios.post('/profile/level/add-xp', {
        amount,
        reason
      })
      
      if (response.data.success) {
        await fetchUserLevel()
        return {
          success: true,
          leveled_up: response.data.leveled_up,
          new_level: response.data.new_level
        }
      } else {
        error.value = response.data.message
        return { success: false }
      }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to add XP'
      console.error('Error adding XP:', err)
      return { success: false }
    } finally {
      loading.value = false
    }
  }

  const removeXp = async (amount: number, reason?: string) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await axios.post('/profile/level/remove-xp', {
        amount,
        reason
      })
      
      if (response.data.success) {
        await fetchUserLevel()
        return { success: true }
      } else {
        error.value = response.data.message
        return { success: false }
      }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to remove XP'
      console.error('Error removing XP:', err)
      return { success: false }
    } finally {
      loading.value = false
    }
  }

  const resetLevel = async () => {
    loading.value = true
    error.value = null
    
    try {
      const response = await axios.post('/profile/level/reset')
      
      if (response.data.success) {
        await fetchUserLevel()
        return { success: true }
      } else {
        error.value = response.data.message
        return { success: false }
      }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to reset level'
      console.error('Error resetting level:', err)
      return { success: false }
    } finally {
      loading.value = false
    }
  }

  const fetchLeaderboard = async (limit = 10, sortBy = 'total_xp') => {
    loading.value = true
    error.value = null
    
    try {
      const response = await axios.get('/api/leaderboard', {
        params: { limit, sort_by: sortBy }
      })
      leaderboard.value = response.data.data.leaderboard
      return { success: true }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch leaderboard'
      console.error('Error fetching leaderboard:', err)
      return { success: false }
    } finally {
      loading.value = false
    }
  }

  const calculateXpReward = async (action: string, context: any = {}) => {
    try {
      const response = await axios.post('/api/calculate-reward', {
        action,
        context
      })
      
      return response.data.data.xp_reward || 0
    } catch (err: any) {
      console.error('Error calculating XP reward:', err)
      return 0
    }
  }

  const progressPercentage = computed(() => {
    return userLevel.value?.progress_percentage || 0
  })

  const currentLevel = computed(() => {
    return userLevel.value?.level || 1
  })

  const currentXp = computed(() => {
    return userLevel.value?.current_xp || 0
  })

  const xpNeeded = computed(() => {
    return userLevel.value?.xp_needed_for_next_level || 1000
  })

  const nextLevel = computed(() => {
    return currentLevel.value + 1
  })

  return {
    userLevel: readonly(userLevel),
    leaderboard: readonly(leaderboard),
    loading: readonly(loading),
    error: readonly(error),
    
    fetchUserLevel,
    addXp,
    removeXp,
    resetLevel,
    fetchLeaderboard,
    calculateXpReward,
    
    progressPercentage,
    currentLevel,
    currentXp,
    xpNeeded,
    nextLevel
  }
}
