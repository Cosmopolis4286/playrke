import { ref, computed, readonly } from 'vue'
import { backendClient as axios } from '../plugins/axios'

export interface Achievement {
  id: string
  code: string
  name: string
  description: string
  icon?: string
  badge_color: string
  badge_style: string
  xp_reward: number
  category: string
  is_hidden: boolean
  requirements: Record<string, any>
  progress?: {
    is_unlocked: boolean
    current: number
    total: number
    percentage: number
    unlocked_at?: string
  }
  is_unlocked?: boolean
  unlocked_at?: string
}

export interface AchievementStats {
  total_achievements: number
  unlocked_count: number
  unseen_count: number
  completion_percentage: number
  total_xp_from_achievements: number
  categories: Record<string, {
    total: number
    unlocked: number
    percentage: number
  }>
}

export function useAchievements() {
  const achievements = ref<Achievement[]>([])
  const stats = ref<AchievementStats | null>(null)
  const unseenAchievements = ref<Achievement[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  const fetchUserAchievements = async () => {
    loading.value = true
    error.value = null
    
    try {
      const response = await axios.get('/profile/achievements')
      achievements.value = response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch achievements'
      console.error('Error fetching achievements:', err)
    } finally {
      loading.value = false
    }
  }

  const fetchAllAchievements = async () => {
    loading.value = true
    error.value = null
    
    try {
      const response = await axios.get('/profile/achievements/all')
      achievements.value = response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch all achievements'
      console.error('Error fetching all achievements:', err)
    } finally {
      loading.value = false
    }
  }

  const fetchUnlockedAchievements = async () => {
    loading.value = true
    error.value = null
    
    try {
      const response = await axios.get('/profile/achievements/unlocked')
      achievements.value = response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch unlocked achievements'
      console.error('Error fetching unlocked achievements:', err)
    } finally {
      loading.value = false
    }
  }

  const fetchUnseenAchievements = async () => {
    loading.value = true
    error.value = null
    
    try {
      const response = await axios.get('/profile/achievements/unseen')
      unseenAchievements.value = response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch unseen achievements'
      console.error('Error fetching unseen achievements:', err)
    } finally {
      loading.value = false
    }
  }

  const fetchStats = async () => {
    loading.value = true
    error.value = null
    
    try {
      const response = await axios.get('/profile/achievements/stats')
      stats.value = response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch achievement stats'
      console.error('Error fetching achievement stats:', err)
    } finally {
      loading.value = false
    }
  }

  const markAsSeen = async (achievementIds?: string[]) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await axios.post('/profile/achievements/mark-seen', {
        achievement_ids: achievementIds
      })
      
      if (response.data.success) {
        await fetchUnseenAchievements()
        return { success: true }
      } else {
        error.value = response.data.message
        return { success: false }
      }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to mark achievements as seen'
      console.error('Error marking achievements as seen:', err)
      return { success: false }
    } finally {
      loading.value = false
    }
  }

  const markAllAchievementsAsSeen = async () => {
    loading.value = true
    error.value = null
    
    try {
      const response = await axios.post('/profile/achievements/mark-all-seen')
      
      if (response.data.success) {
        await fetchUnseenAchievements()
        return { success: true }
      } else {
        error.value = response.data.message
        return { success: false }
      }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to mark all achievements as seen'
      console.error('Error marking all achievements as seen:', err)
      return { success: false }
    } finally {
      loading.value = false
    }
  }

  const checkAndUnlock = async (context: Record<string, any> = {}) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await axios.post('/profile/achievements/check', {
        context
      })
      
      if (response.data.success) {
        const unlockedCount = response.data.data.unlocked_count
        if (unlockedCount > 0) {
          await fetchUserAchievements()
          await fetchStats()
          await fetchUnseenAchievements()
        }
        
        return {
          success: true,
          unlocked_count: unlockedCount,
          unlocked_achievements: response.data.data.unlocked_achievements
        }
      } else {
        error.value = response.data.message
        return { success: false }
      }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to check and unlock achievements'
      console.error('Error checking achievements:', err)
      return { success: false }
    } finally {
      loading.value = false
    }
  }

  const getAchievementsByCategory = async (category: string) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await axios.get(`/profile/achievements/category/${category}`)
      achievements.value = response.data.data
      return { success: true }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch achievements by category'
      console.error('Error fetching achievements by category:', err)
      return { success: false }
    } finally {
      loading.value = false
    }
  }

  // Computed properties
  const unlockedCount = computed(() => {
    return achievements.value.filter(a => a.is_unlocked).length
  })

  const lockedCount = computed(() => {
    return achievements.value.filter(a => !a.is_unlocked).length
  })

  const completionPercentage = computed(() => {
    if (achievements.value.length === 0) return 0
    return Math.round((unlockedCount.value / achievements.value.length) * 100)
  })

  const totalXpFromAchievements = computed(() => {
    return achievements.value
      .filter(a => a.is_unlocked)
      .reduce((total, achievement) => total + achievement.xp_reward, 0)
  })

  const achievementsByCategory = computed(() => {
    const categories: Record<string, Achievement[]> = {}
    achievements.value.forEach(achievement => {
      if (!categories[achievement.category]) {
        categories[achievement.category] = []
      }
      categories[achievement.category].push(achievement)
    })
    return categories
  })

  const hasUnseenAchievements = computed(() => {
    return unseenAchievements.value.length > 0
  })

  return {
    // State
    achievements: readonly(achievements),
    stats: readonly(stats),
    unseenAchievements: readonly(unseenAchievements),
    loading: readonly(loading),
    error: readonly(error),
    
    // Methods
    fetchUserAchievements,
    fetchAllAchievements,
    fetchUnlockedAchievements,
    fetchUnseenAchievements,
    fetchStats,
    markAsSeen,
    markAllAchievementsAsSeen,
    checkAndUnlock,
    getAchievementsByCategory,
    
    // Computed
    unlockedCount,
    lockedCount,
    completionPercentage,
    totalXpFromAchievements,
    achievementsByCategory,
    hasUnseenAchievements
  }
}
