<template>
  <div class="row">
    <!-- Estadísticas Principales -->
    <div class="col-md-8">
      <div class="row">
        <!-- Juegos Jugados -->
        <div class="col-md-4 mb-3">
          <div class="card bg-primary text-white">
            <div class="card-body text-center">
              <i class="fas fa-gamepad fa-2x mb-2"></i>
              <h3 class="mb-1">{{ stats.games_played || 0 }}</h3>
              <p class="mb-0">Juegos Jugados</p>
            </div>
          </div>
        </div>

        <!-- Juegos Ganados -->
        <div class="col-md-4 mb-3">
          <div class="card bg-success text-white">
            <div class="card-body text-center">
              <i class="fas fa-trophy fa-2x mb-2"></i>
              <h3 class="mb-1">{{ stats.games_won || 0 }}</h3>
              <p class="mb-0">Juegos Ganados</p>
            </div>
          </div>
        </div>

        <!-- Total Ganado -->
        <div class="col-md-4 mb-3">
          <div class="card bg-warning text-white">
            <div class="card-body text-center">
              <i class="fas fa-coins fa-2x mb-2"></i>
              <h3 class="mb-1">{{ formatCurrency(stats.total_winnings) }}</h3>
              <p class="mb-0">Total Ganado</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Racha y Nivel -->
      <div class="row">
        <!-- Racha Actual -->
        <div class="col-md-6 mb-3">
          <div class="card bg-info text-white">
            <div class="card-body text-center">
              <i class="fas fa-fire fa-2x mb-2"></i>
              <h3 class="mb-1">{{ stats.current_streak || 0 }}</h3>
              <p class="mb-0">Racha Actual</p>
            </div>
          </div>
        </div>

        <!-- Mejor Racha -->
        <div class="col-md-6 mb-3">
          <div class="card bg-danger text-white">
            <div class="card-body text-center">
              <i class="fas fa-medal fa-2x mb-2"></i>
              <h3 class="mb-1">{{ stats.best_streak || 0 }}</h3>
              <p class="mb-0">Mejor Racha</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Progreso de Nivel -->
      <div class="card mb-3">
        <div class="card-header bg-light">
          <h6 class="mb-0">
            <i class="fas fa-level-up-alt me-2"></i>Progreso de Nivel
          </h6>
        </div>
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="fw-bold">Nivel {{ stats.level || 1 }}</span>
            <span class="text-muted">{{ stats.experience_points || 0 }} XP</span>
          </div>
          <div class="progress" style="height: 25px;">
            <div 
              class="progress-bar bg-success" 
              role="progressbar" 
              :style="{ width: levelProgress + '%' }"
            >
              {{ Math.round(levelProgress) }}%
            </div>
          </div>
          <small class="text-muted">
            {{ experienceToNextLevel }} XP para el siguiente nivel
          </small>
        </div>
      </div>

      <!-- Logros -->
      <div class="card">
        <div class="card-header bg-light">
          <h6 class="mb-0">
            <i class="fas fa-award me-2"></i>Logros Desbloqueados
          </h6>
        </div>
        <div class="card-body">
          <div v-if="stats.achievements_count > 0" class="row">
            <div class="col-md-2 col-sm-4 col-6 text-center mb-3" v-for="i in Math.min(stats.achievements_count, 6)" :key="i">
              <div class="achievement-badge">
                <i class="fas fa-star fa-2x text-warning mb-2"></i>
                <p class="mb-0 small">Logro {{ i }}</p>
              </div>
            </div>
            <div v-if="stats.achievements_count > 6" class="col-12 text-center">
              <small class="text-muted">+{{ stats.achievements_count - 6 }} logros más</small>
            </div>
          </div>
          <div v-else class="text-center text-muted">
            <i class="fas fa-lock fa-3x mb-3"></i>
            <p>No tienes logros desbloqueados aún</p>
            <small>Sigue jugando para desbloquear logros</small>
          </div>
        </div>
      </div>
    </div>

    <!-- Información Adicional -->
    <div class="col-md-4">
      <!-- Estadísticas de Tiempo -->
      <div class="card bg-light mb-3">
        <div class="card-body">
          <h6 class="card-title mb-3">
            <i class="fas fa-clock me-2"></i>Tiempo de Juego
          </h6>
          
          <div class="mb-2">
            <small class="text-muted">Fecha de Registro:</small>
            <div>{{ formatDate(stats.join_date) }}</div>
          </div>
          
          <div class="mb-2">
            <small class="text-muted">Última Actividad:</small>
            <div>{{ formatDate(stats.last_active) }}</div>
          </div>
          
          <div class="mb-2">
            <small class="text-muted">Días Activo:</small>
            <div>{{ activeDays }} días</div>
          </div>
        </div>
      </div>

      <!-- Tasa de Victoria -->
      <div class="card bg-light mb-3">
        <div class="card-body">
          <h6 class="card-title mb-3">
            <i class="fas fa-percentage me-2"></i>Tasa de Victoria
          </h6>
          
          <div class="text-center mb-3">
            <div class="circular-progress" :style="{ '--progress': winRate + '%' }">
              <span class="progress-text">{{ Math.round(winRate) }}%</span>
            </div>
          </div>
          
          <div class="d-flex justify-content-between text-sm">
            <span>Victorias: {{ stats.games_won || 0 }}</span>
            <span>Derrotas: {{ (stats.games_played || 0) - (stats.games_won || 0) }}</span>
          </div>
        </div>
      </div>

      <!-- Promedios -->
      <div class="card bg-light">
        <div class="card-body">
          <h6 class="card-title mb-3">
            <i class="fas fa-chart-line me-2"></i>Promedios
          </h6>
          
          <div class="mb-2">
            <small class="text-muted">Promedio por Juego:</small>
            <div>{{ formatCurrency(averagePerGame) }}</div>
          </div>
          
          <div class="mb-2">
            <small class="text-muted">Promedio Diario:</small>
            <div>{{ formatCurrency(averagePerDay) }}</div>
          </div>
          
          <div class="mb-2">
            <small class="text-muted">Juegos por Día:</small>
            <div>{{ gamesPerDay.toFixed(1) }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({})
    }
})

// Computadas
const winRate = computed(() => {
    if (!props.stats.games_played || props.stats.games_played === 0) return 0
    return ((props.stats.games_won || 0) / props.stats.games_played) * 100
})

const levelProgress = computed(() => {
    // Simulación: cada nivel necesita 1000 XP
    const currentLevel = props.stats.level || 1
    const experienceNeeded = currentLevel * 1000
    const experienceForCurrentLevel = (currentLevel - 1) * 1000
    const currentXP = props.stats.experience_points || 0
    
    return ((currentXP - experienceForCurrentLevel) / (experienceNeeded - experienceForCurrentLevel)) * 100
})

const experienceToNextLevel = computed(() => {
    const currentLevel = props.stats.level || 1
    const experienceNeeded = currentLevel * 1000
    const currentXP = props.stats.experience_points || 0
    
    return Math.max(0, experienceNeeded - currentXP)
})

const averagePerGame = computed(() => {
    if (!props.stats.games_played || props.stats.games_played === 0) return 0
    return (props.stats.total_winnings || 0) / props.stats.games_played
})

const averagePerDay = computed(() => {
    if (!activeDays.value || activeDays.value === 0) return 0
    return (props.stats.total_winnings || 0) / activeDays.value
})

const gamesPerDay = computed(() => {
    if (!activeDays.value || activeDays.value === 0) return 0
    return (props.stats.games_played || 0) / activeDays.value
})

const activeDays = computed(() => {
    if (!props.stats.join_date) return 0
    const joinDate = new Date(props.stats.join_date)
    const today = new Date()
    const diffTime = Math.abs(today.getTime() - joinDate.getTime())
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24))
})

// Métodos
const formatCurrency = (amount) => {
    if (!amount) return '$0.00'
    return new Intl.NumberFormat('es-VE', {
        style: 'currency',
        currency: 'USD'
    }).format(amount)
}

const formatDate = (dateString) => {
    if (!dateString) return 'No disponible'
    return new Date(dateString).toLocaleDateString('es-VE', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    })
}
</script>

<style scoped>
.card {
    border-radius: 12px;
    border: none;
}

.card.bg-primary, .card.bg-success, .card.bg-warning, .card.bg-info, .card.bg-danger {
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.circular-progress {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: conic-gradient(#28a745 var(--progress), #e9ecef var(--progress));
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    position: relative;
}

.circular-progress::before {
    content: '';
    position: absolute;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: white;
}

.progress-text {
    position: relative;
    font-weight: bold;
    color: #28a745;
}

.achievement-badge {
    padding: 10px;
    border-radius: 8px;
    background: rgba(255, 193, 7, 0.1);
    transition: transform 0.2s;
}

.achievement-badge:hover {
    transform: translateY(-2px);
}

.progress {
    border-radius: 10px;
    overflow: hidden;
}

.progress-bar {
    border-radius: 10px;
    transition: width 0.3s ease;
}

.text-sm {
    font-size: 0.875rem;
}
</style>
