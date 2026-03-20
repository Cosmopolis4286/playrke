<template>
  <div class="achievements-container">
    <!-- Header con estadísticas -->
    <div class="achievements-header mb-6">
      <h2 class="text-2xl font-bold text-gray-800 mb-4">Logros Desbloqueados</h2>
      
      <!-- Estadísticas -->
      <div class="stats-grid grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="stat-card bg-white rounded-lg shadow p-4 text-center">
          <div class="text-3xl font-bold text-blue-600">{{ stats.unlocked_count }}</div>
          <div class="text-sm text-gray-600">Desbloqueados</div>
        </div>
        <div class="stat-card bg-white rounded-lg shadow p-4 text-center">
          <div class="text-3xl font-bold text-green-600">{{ stats.completion_percentage }}%</div>
          <div class="text-sm text-gray-600">Completado</div>
        </div>
        <div class="stat-card bg-white rounded-lg shadow p-4 text-center">
          <div class="text-3xl font-bold text-purple-600">{{ stats.total_xp_from_achievements }}</div>
          <div class="text-sm text-gray-600">XP Ganado</div>
        </div>
        <div class="stat-card bg-white rounded-lg shadow p-4 text-center">
          <div class="text-3xl font-bold text-orange-600">{{ stats.unseen_count }}</div>
          <div class="text-sm text-gray-600">Nuevos</div>
        </div>
      </div>

      <!-- Filtro de categorías -->
      <div class="category-filter mb-4">
        <select 
          v-model="selectedCategory" 
          @change="filterAchievements"
          class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        >
          <option value="all">Todas las categorías</option>
          <option value="games">Juegos</option>
          <option value="social">Social</option>
          <option value="milestones">Hitos</option>
          <option value="special">Especiales</option>
        </select>
      </div>
    </div>

    <!-- Logros no vistos -->
    <div v-if="unseenAchievements.length > 0" class="unseen-achievements mb-6">
      <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <div class="text-yellow-600 mr-2">
              <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
              </svg>
            </div>
            <span class="font-medium text-yellow-800">
              ¡Tienes {{ unseenAchievements.length }} logros nuevos!
            </span>
          </div>
          <button 
            @click="markAllAsSeen"
            class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors"
          >
            Marcar todos como vistos
          </button>
        </div>
      </div>
    </div>

    <!-- Estado vacío -->
    <div v-if="filteredAchievements.length === 0 && !loading" class="empty-state text-center py-12">
      <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4">
        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        </svg>
      </div>
      <h3 class="text-lg font-medium text-gray-900 mb-2">No tienes logros desbloqueados aún</h3>
      <p class="text-gray-600">Sigue jugando para desbloquear logros</p>
    </div>

    <!-- Grid de logros -->
    <div v-else class="achievements-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div 
        v-for="achievement in filteredAchievements" 
        :key="achievement.id"
        class="achievement-card"
        :class="{ 'achievement-unlocked': achievement.is_unlocked, 'achievement-locked': !achievement.is_unlocked }"
      >
        <div class="card-content bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
          <!-- Header del logro -->
          <div class="card-header p-4 flex items-center justify-between">
            <div class="flex items-center">
              <!-- Icono del logro -->
              <div class="achievement-icon mr-3">
                <div 
                  :class="achievement.badge_style"
                  class="w-12 h-12 rounded-full flex items-center justify-center text-white"
                >
                  <svg v-if="achievement.icon" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                  </svg>
                  <svg v-else class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                  </svg>
                </div>
              </div>
              
              <!-- Información del logro -->
              <div class="achievement-info flex-1">
                <h3 class="font-semibold text-gray-800">{{ achievement.name }}</h3>
                <p class="text-sm text-gray-600">{{ achievement.category }}</p>
              </div>

              <!-- Estado del logro -->
              <div class="achievement-status">
                <span 
                  v-if="achievement.is_unlocked"
                  class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800"
                >
                  Desbloqueado
                </span>
                <span 
                  v-else
                  class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800"
                >
                  Bloqueado
                </span>
              </div>
            </div>
          </div>

          <!-- Contenido del logro -->
          <div class="card-body px-4 pb-4">
            <p class="text-gray-700 text-sm mb-3">{{ achievement.description }}</p>
            
            <!-- Progreso -->
            <div v-if="!achievement.is_unlocked && achievement.progress" class="progress-section mb-3">
              <div class="flex justify-between text-xs text-gray-600 mb-1">
                <span>Progreso</span>
                <span>{{ achievement.progress.current }} / {{ achievement.progress.total }}</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2">
                <div 
                  class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                  :style="{ width: achievement.progress.percentage + '%' }"
                ></div>
              </div>
            </div>

            <!-- Recompensa -->
            <div v-if="achievement.xp_reward > 0" class="reward-section">
              <div class="flex items-center text-sm text-purple-600">
                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z"/>
                  <path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z"/>
                </svg>
                +{{ achievement.xp_reward }} XP
              </div>
            </div>

            <!-- Fecha de desbloqueo -->
            <div v-if="achievement.is_unlocked && achievement.unlocked_at" class="unlock-date">
              <p class="text-xs text-gray-500 mt-2">
                Desbloqueado el {{ formatDate(achievement.unlocked_at) }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading state -->
    <div v-if="loading" class="loading-state text-center py-12">
      <div class="inline-flex items-center justify-center w-8 h-8 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
      <p class="mt-4 text-gray-600">Cargando logros...</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAchievements } from '../../composables/useAchievements'

interface Achievement {
  id: string
  code: string
  name: string
  description: string
  icon?: string
  badge_color: string
  badge_style: string
  xp_reward: number
  category: string
  is_unlocked: boolean
  unlocked_at?: string
  progress?: {
    current: number
    total: number
    percentage: number
  }
}

const { 
  achievements, 
  stats, 
  unseenAchievements, 
  loading, 
  error,
  fetchUserAchievements, 
  fetchStats,
  markAllAchievementsAsSeen 
} = useAchievements()

const selectedCategory = ref('all')

const filteredAchievements = computed(() => {
  if (selectedCategory.value === 'all') {
    return achievements.value
  }
  return achievements.value.filter(a => a.category === selectedCategory.value)
})

const filterAchievements = () => {
  // La filtración se hace automáticamente con el computed
}

const markAllAsSeen = async () => {
  await markAllAchievementsAsSeen()
  await fetchUserAchievements()
  await fetchStats()
}

const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

onMounted(async () => {
  await Promise.all([
    fetchUserAchievements(),
    fetchStats()
  ])
})
</script>

<style scoped>
.achievements-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 1rem;
}

.achievement-card {
  transition: transform 0.2s ease-in-out;
}

.achievement-card:hover {
  transform: translateY(-2px);
}

.achievement-unlocked .card-content {
  border-left: 4px solid #10b981;
}

.achievement-locked .card-content {
  border-left: 4px solid #6b7280;
  opacity: 0.8;
}

.achievement-icon {
  flex-shrink: 0;
}

.card-header {
  border-bottom: 1px solid #f3f4f6;
}

.progress-section {
  background-color: #f9fafb;
  padding: 0.75rem;
  border-radius: 0.375rem;
}

.reward-section {
  background-color: #f3f4f6;
  padding: 0.5rem;
  border-radius: 0.375rem;
}

@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: 1fr 1fr;
  }
  
  .achievements-grid {
    grid-template-columns: 1fr;
  }
}
</style>
