<template>
  <div class="stats-layout">

    <!-- ── Fila superior: 5 KPIs ─────────────────────────────────────── -->
    <div class="kpi-grid">

      <div class="kpi-card kpi-games">
        <div class="kpi-icon"><i class="fas fa-gamepad"></i></div>
        <div class="kpi-body">
          <span class="kpi-value">{{ stats.games_played || 0 }}</span>
          <span class="kpi-label">Partidas</span>
        </div>
      </div>

      <div class="kpi-card kpi-wins">
        <div class="kpi-icon"><i class="fas fa-trophy"></i></div>
        <div class="kpi-body">
          <span class="kpi-value">{{ stats.games_won || 0 }}</span>
          <span class="kpi-label">Victorias</span>
        </div>
      </div>

      <div class="kpi-card kpi-winrate">
        <div class="kpi-icon"><i class="fas fa-percentage"></i></div>
        <div class="kpi-body">
          <span class="kpi-value">{{ Math.round(winRate) }}%</span>
          <span class="kpi-label">Win rate</span>
        </div>
      </div>

      <div class="kpi-card kpi-streak">
        <div class="kpi-icon"><i class="fas fa-fire"></i></div>
        <div class="kpi-body">
          <span class="kpi-value">{{ stats.current_streak || 0 }}</span>
          <span class="kpi-label">Racha actual</span>
        </div>
      </div>

      <div class="kpi-card kpi-earnings">
        <div class="kpi-icon"><i class="fas fa-coins"></i></div>
        <div class="kpi-body">
          <span class="kpi-value">{{ formatCurrency(stats.total_winnings) }}</span>
          <span class="kpi-label">Total ganado</span>
        </div>
      </div>

    </div>

    <!-- ── Cuerpo: columna principal + aside ────────────────────────── -->
    <div class="stats-body">

      <!-- Columna principal -->
      <div class="stats-main">

        <!-- Progreso de nivel -->
        <div class="section-card">
          <div class="section-header">
            <span class="section-icon level-icon"><i class="fas fa-level-up-alt"></i></span>
            <h6>Progreso de Nivel</h6>
          </div>
          <div class="level-row">
            <div class="level-badge">
              <span class="level-num">{{ stats.level || 1 }}</span>
              <span class="level-lbl">Nivel</span>
            </div>
            <div class="level-progress-wrap">
              <div class="level-labels">
                <span>{{ stats.experience_points || 0 }} XP</span>
                <span class="text-muted">{{ xpToNextLevel }} XP para nivel {{ (stats.level || 1) + 1 }}</span>
              </div>
              <div class="xp-bar">
                <div class="xp-fill" :style="{ width: Math.min(levelProgress, 100) + '%' }">
                  <span v-if="levelProgress > 12">{{ Math.round(levelProgress) }}%</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Rachas -->
        <div class="section-card">
          <div class="section-header">
            <span class="section-icon streak-icon"><i class="fas fa-fire"></i></span>
            <h6>Rachas</h6>
          </div>
          <div class="streak-grid">
            <div class="streak-item">
              <i class="fas fa-fire streak-active-icon"></i>
              <div>
                <div class="streak-val">{{ stats.current_streak || 0 }}</div>
                <div class="streak-lbl">Racha actual</div>
              </div>
            </div>
            <div class="streak-divider"></div>
            <div class="streak-item">
              <i class="fas fa-medal streak-best-icon"></i>
              <div>
                <div class="streak-val">{{ stats.best_streak || 0 }}</div>
                <div class="streak-lbl">Mejor racha</div>
              </div>
            </div>
          </div>
          <p v-if="(stats.current_streak || 0) > 0" class="streak-hint">
            🔥 Llevas {{ stats.current_streak }} día{{ stats.current_streak === 1 ? '' : 's' }} seguido{{ stats.current_streak === 1 ? '' : 's' }}. ¡Entra mañana para no perderla!
          </p>
          <p v-else class="streak-hint streak-hint--muted">
            Entra hoy para empezar una racha.
          </p>
        </div>

        <!-- Logros -->
        <div class="section-card">
          <div class="section-header">
            <span class="section-icon award-icon"><i class="fas fa-award"></i></span>
            <h6>Logros <span class="badge-count">{{ stats.achievements_count || 0 }}</span></h6>
          </div>

          <div v-if="achievementsLoading" class="achievements-loading">
            <i class="fas fa-spinner fa-spin"></i> Cargando logros...
          </div>

          <div v-else-if="achievements.length" class="achievements-grid">
            <div
              v-for="a in achievements.slice(0, 9)"
              :key="a.id"
              class="achievement-item"
              :title="a.description ?? a.name"
            >
              <div class="achievement-icon">
                <img v-if="a.icon" :src="a.icon" :alt="a.name" />
                <i v-else class="fas fa-star"></i>
              </div>
              <span>{{ a.name }}</span>
            </div>
            <div v-if="achievements.length > 9" class="achievement-more">
              +{{ achievements.length - 9 }} más
            </div>
          </div>

          <div v-else class="empty-state">
            <div class="empty-icon"><i class="fas fa-lock"></i></div>
            <p>Sin logros desbloqueados aún</p>
            <small>Sigue jugando para ganar insignias</small>
          </div>
        </div>

      </div>

      <!-- Aside -->
      <aside class="stats-aside">

        <!-- Win rate circular -->
        <div class="section-card aside-card">
          <div class="section-header">
            <span class="section-icon wr-icon"><i class="fas fa-chart-pie"></i></span>
            <h6>Tasa de victoria</h6>
          </div>
          <div class="wr-wrap">
            <div class="wr-ring" :style="{ '--wr': winRate }">
              <span class="wr-text">{{ Math.round(winRate) }}%</span>
            </div>
            <div class="wr-legend">
              <div class="wr-row wr-won">
                <span class="wr-dot"></span>
                <span>Victorias</span>
                <strong>{{ stats.games_won || 0 }}</strong>
              </div>
              <div class="wr-row wr-lost">
                <span class="wr-dot"></span>
                <span>Derrotas</span>
                <strong>{{ (stats.games_played || 0) - (stats.games_won || 0) }}</strong>
              </div>
            </div>
          </div>
        </div>

        <!-- Tiempo -->
        <div class="section-card aside-card">
          <div class="section-header">
            <span class="section-icon time-icon"><i class="fas fa-calendar-alt"></i></span>
            <h6>Actividad</h6>
          </div>
          <ul class="info-list">
            <li>
              <span class="info-lbl">Miembro desde</span>
              <span class="info-val">{{ formatDate(stats.join_date) }}</span>
            </li>
            <li>
              <span class="info-lbl">Última actividad</span>
              <span class="info-val">{{ formatDate(stats.last_active) }}</span>
            </li>
            <li>
              <span class="info-lbl">Días activo</span>
              <span class="info-val">{{ activeDays }} días</span>
            </li>
            <li>
              <span class="info-lbl">Partidas por día</span>
              <span class="info-val">{{ gamesPerDay.toFixed(1) }}</span>
            </li>
          </ul>
        </div>

        <!-- Promedios -->
        <div class="section-card aside-card">
          <div class="section-header">
            <span class="section-icon avg-icon"><i class="fas fa-chart-line"></i></span>
            <h6>Promedios</h6>
          </div>
          <ul class="info-list">
            <li>
              <span class="info-lbl">Por partida</span>
              <span class="info-val accent">{{ formatCurrency(averagePerGame) }}</span>
            </li>
            <li>
              <span class="info-lbl">Por día</span>
              <span class="info-val accent">{{ formatCurrency(averagePerDay) }}</span>
            </li>
          </ul>
        </div>

      </aside>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { ProfileAchievement } from '@js/composables/useProfile'

interface GameStats {
    games_played?: number
    games_won?: number
    total_winnings?: number
    current_streak?: number
    best_streak?: number
    level?: number
    experience_points?: number
    xp_to_next_level?: number
    level_progress?: number
    achievements_count?: number
    join_date?: string
    last_active?: string
}

const props = defineProps<{
    stats: GameStats
    achievements?: ProfileAchievement[]
    achievementsLoading?: boolean
}>()

const achievements = computed(() => props.achievements ?? [])

// ── Computed ────────────────────────────────────────────────────────────────

const winRate = computed(() => {
    if (!props.stats.games_played) return 0
    return ((props.stats.games_won || 0) / props.stats.games_played) * 100
})

// El backend ya calcula el progreso con la fórmula real de nivel (exponencial,
// ver UserLevel::calculateXpForNextLevel) — no se recalcula aquí para evitar
// que el frontend y el backend diverjan en la fórmula.
const levelProgress = computed(() => props.stats.level_progress ?? 0)
const xpToNextLevel = computed(() => props.stats.xp_to_next_level ?? 1000)

const activeDays = computed(() => {
    if (!props.stats.join_date) return 0
    const diff = Date.now() - new Date(props.stats.join_date).getTime()
    return Math.max(1, Math.ceil(diff / 86_400_000))
})

const averagePerGame = computed(() => {
    if (!props.stats.games_played) return 0
    return (props.stats.total_winnings || 0) / props.stats.games_played
})

const averagePerDay = computed(() => {
    if (!activeDays.value) return 0
    return (props.stats.total_winnings || 0) / activeDays.value
})

const gamesPerDay = computed(() => {
    if (!activeDays.value) return 0
    return (props.stats.games_played || 0) / activeDays.value
})

// ── Formatters ───────────────────────────────────────────────────────────────

function formatCurrency(amount: number | undefined): string {
    if (!amount) return '$0.00'
    return new Intl.NumberFormat('es-VE', { style: 'currency', currency: 'USD' }).format(amount)
}

function formatDate(dateString: string | undefined): string {
    if (!dateString) return 'No disponible'
    return new Date(dateString).toLocaleDateString('es-VE', { year: 'numeric', month: 'short', day: 'numeric' })
}
</script>

<style scoped>
/* ── Layout ─────────────────────────────────────────────────────────────── */
.stats-layout { display: flex; flex-direction: column; gap: 1.25rem; }

.stats-body {
  display: grid;
  grid-template-columns: 1fr 280px;
  gap: 1.25rem;
}

/* ── KPI grid ────────────────────────────────────────────────────────────── */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: .875rem;
}

.kpi-card {
  border-radius: 14px;
  padding: 1rem 1rem .875rem;
  display: flex;
  align-items: center;
  gap: .875rem;
  color: #fff;
  box-shadow: 0 4px 14px rgba(0,0,0,.12);
}

.kpi-icon {
  width: 44px; height: 44px;
  border-radius: 12px;
  background: rgba(255,255,255,.2);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.1rem;
  flex-shrink: 0;
}

.kpi-body { display: flex; flex-direction: column; min-width: 0; }
.kpi-value { font-size: 1.35rem; font-weight: 700; line-height: 1.1; }
.kpi-label { font-size: .72rem; opacity: .85; margin-top: 2px; white-space: nowrap; }

.kpi-games   { background: linear-gradient(135deg, #667eea, #764ba2); }
.kpi-wins    { background: linear-gradient(135deg, #11998e, #38ef7d); }
.kpi-winrate { background: linear-gradient(135deg, #f093fb, #f5576c); }
.kpi-streak  { background: linear-gradient(135deg, #f7971e, #ffd200); }
.kpi-earnings{ background: linear-gradient(135deg, #4facfe, #00f2fe); }

/* ── Section cards ───────────────────────────────────────────────────────── */
.section-card {
  background: var(--ds-surface);
  border-radius: 14px;
  border: 1px solid var(--ds-border);
  padding: 1.1rem 1.25rem;
  margin-bottom: 1rem;
}
.section-card:last-child { margin-bottom: 0; }

.section-header {
  display: flex;
  align-items: center;
  gap: .6rem;
  margin-bottom: 1rem;
}
.section-header h6 {
  margin: 0;
  font-size: .88rem;
  font-weight: 600;
  color: var(--ds-text);
  display: flex;
  align-items: center;
  gap: .4rem;
}

.section-icon {
  width: 30px; height: 30px;
  border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  font-size: .8rem;
  flex-shrink: 0;
}
.level-icon  { background: #eef2ff; color: #667eea; }
.streak-icon { background: #fff4e5; color: #f7971e; }
.award-icon  { background: #fffbe6; color: #f5a623; }
.wr-icon     { background: #fce4ec; color: #f5576c; }
.time-icon   { background: #e3f2fd; color: #4facfe; }
.avg-icon    { background: #e8f5e9; color: #11998e; }

/* ── Level ───────────────────────────────────────────────────────────────── */
.level-row {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.level-badge {
  width: 56px; height: 56px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: #fff;
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 4px 12px rgba(102,126,234,.35);
}
.level-num { font-size: 1.3rem; font-weight: 700; line-height: 1; }
.level-lbl { font-size: .6rem; opacity: .85; }

.level-progress-wrap { flex: 1; }
.level-labels {
  display: flex;
  justify-content: space-between;
  font-size: .76rem;
  margin-bottom: .4rem;
  color: var(--ds-text-2);
}

.xp-bar {
  height: 10px;
  background: var(--ds-border);
  border-radius: 99px;
  overflow: hidden;
}
.xp-fill {
  height: 100%;
  border-radius: 99px;
  background: linear-gradient(90deg, #667eea, #764ba2);
  transition: width .6s ease;
  display: flex; align-items: center; justify-content: flex-end;
  padding-right: 6px;
  font-size: .6rem; color: #fff; font-weight: 600;
  min-width: 0;
}

/* ── Streaks ─────────────────────────────────────────────────────────────── */
.streak-grid {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}
.streak-divider {
  width: 1px; height: 48px;
  background: var(--ds-border);
}
.streak-item {
  display: flex;
  align-items: center;
  gap: .75rem;
  flex: 1;
}
.streak-active-icon { font-size: 1.8rem; color: #f7971e; }
.streak-best-icon   { font-size: 1.8rem; color: #f5a623; }
.streak-val { font-size: 1.5rem; font-weight: 700; color: var(--ds-text); line-height: 1; }
.streak-lbl { font-size: .76rem; color: var(--ds-text-2); margin-top: 2px; }
.streak-hint {
  margin: .85rem 0 0;
  padding: .5rem .75rem;
  border-radius: 8px;
  background: rgba(247,151,30,.1);
  color: #f7971e;
  font-size: .78rem;
  font-weight: 500;
}
.streak-hint--muted { background: var(--ds-surface-2); color: var(--ds-text-2); }

/* ── Achievements ────────────────────────────────────────────────────────── */
.badge-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #667eea;
  color: #fff;
  font-size: .65rem;
  font-weight: 600;
  border-radius: 99px;
  padding: 1px 7px;
  margin-left: 4px;
}

.achievements-loading {
  text-align: center;
  padding: 1.5rem 0;
  font-size: .82rem;
  color: var(--ds-text-2);
}

.achievements-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(72px, 1fr));
  gap: .6rem;
}
.achievement-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: .3rem;
  font-size: .7rem;
  color: var(--ds-text-2);
  cursor: default;
}
.achievement-icon {
  width: 46px; height: 46px;
  border-radius: 12px;
  background: linear-gradient(135deg, #fff8e1, #fffde7);
  border: 1.5px solid #fcd34d;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.1rem; color: #f59e0b;
  transition: transform .2s, box-shadow .2s;
  overflow: hidden;
}
.achievement-icon img { width: 100%; height: 100%; object-fit: cover; }
.achievement-item span {
  max-width: 76px;
  text-align: center;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.achievement-item:hover .achievement-icon {
  transform: translateY(-3px);
  box-shadow: 0 6px 16px rgba(245,158,11,.25);
}
.achievement-more {
  grid-column: 1 / -1;
  text-align: center;
  font-size: .78rem;
  color: #667eea;
  font-weight: 500;
  padding-top: .25rem;
}

.empty-state {
  text-align: center;
  padding: 1.5rem 0;
  color: var(--ds-text-muted);
}
.empty-icon {
  width: 56px; height: 56px;
  border-radius: 50%;
  background: var(--ds-surface-2);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.4rem;
  margin: 0 auto .75rem;
}
.empty-state p { font-size: .88rem; margin: 0 0 .2rem; color: var(--ds-text-2); }
.empty-state small { font-size: .76rem; }

/* ── Win rate ring ───────────────────────────────────────────────────────── */
.wr-wrap {
  display: flex;
  align-items: center;
  gap: 1rem;
}
.wr-ring {
  width: 76px; height: 76px;
  border-radius: 50%;
  background: conic-gradient(
    #f5576c calc(var(--wr) * 1%),
    var(--ds-border) calc(var(--wr) * 1%)
  );
  display: flex; align-items: center; justify-content: center;
  position: relative;
  flex-shrink: 0;
}
.wr-ring::before {
  content: '';
  position: absolute;
  width: 56px; height: 56px;
  border-radius: 50%;
  background: var(--ds-surface);
}
.wr-text {
  position: relative;
  font-size: .85rem;
  font-weight: 700;
  color: #f5576c;
}
.wr-legend { display: flex; flex-direction: column; gap: .4rem; flex: 1; }
.wr-row {
  display: flex;
  align-items: center;
  gap: .4rem;
  font-size: .78rem;
  color: var(--ds-text-2);
}
.wr-row strong { margin-left: auto; font-weight: 600; color: var(--ds-text); }
.wr-dot {
  width: 8px; height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
}
.wr-won .wr-dot  { background: #f5576c; }
.wr-lost .wr-dot { background: var(--ds-border); border: 1px solid var(--ds-border-2); }

/* ── Info list ───────────────────────────────────────────────────────────── */
.info-list {
  list-style: none;
  padding: 0; margin: 0;
  display: flex;
  flex-direction: column;
  gap: .55rem;
}
.info-list li {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  font-size: .8rem;
}
.info-lbl { color: var(--ds-text-2); }
.info-val  { font-weight: 600; color: var(--ds-text); }
.info-val.accent { color: #11998e; }

/* ── Aside ───────────────────────────────────────────────────────────────── */
.aside-card { margin-bottom: .875rem; }
.aside-card:last-child { margin-bottom: 0; }

/* ── Responsive ──────────────────────────────────────────────────────────── */
@media (max-width: 900px) {
  .kpi-grid { grid-template-columns: repeat(3, 1fr); }
  .stats-body { grid-template-columns: 1fr; }
  .stats-aside { display: grid; grid-template-columns: repeat(2, 1fr); gap: .875rem; }
  .aside-card { margin-bottom: 0; }
}
@media (max-width: 560px) {
  .kpi-grid { grid-template-columns: repeat(2, 1fr); }
  .stats-aside { grid-template-columns: 1fr; }
}
</style>
