<template>
  <header class="profile-header">
    <div class="header-inner">
      <!-- Left: avatar + identity -->
      <div class="header-identity">
        <div class="avatar-wrap">
          <img
            :src="user.avatar || '/img/fav.ico'"
            :alt="user.name"
            class="avatar-img"
          />
          <button class="avatar-btn" @click="$emit('open-avatar')" title="Cambiar foto">
            <i class="fas fa-camera"></i>
          </button>
        </div>

        <div class="identity-info">
          <h2 class="identity-name">{{ user.name }}</h2>
          <p class="identity-username">@{{ user.username }}</p>
          <div class="identity-badges">
            <span v-if="user.is_verified" class="badge-pill badge-verified">
              <i class="fas fa-check-circle me-1"></i>Verificado
            </span>
            <span v-for="role in user.roles" :key="role.id" class="badge-pill badge-role">
              <i class="fas fa-user-tag me-1"></i>{{ role.name }}
            </span>
          </div>
        </div>
      </div>

      <!-- Center: quick stats -->
      <div class="header-stats">
        <div v-for="stat in quickStats" :key="stat.label" class="stat-item">
          <span class="stat-value">{{ stat.value }}</span>
          <span class="stat-label">{{ stat.label }}</span>
        </div>
      </div>

      <!-- Right: actions + wallet -->
      <div class="header-actions">
        <div v-if="user.wallet" class="wallet-badge">
          <i class="fas fa-wallet me-1 opacity-75"></i>
          <span class="wallet-amount">{{ formatCurrency(user.wallet.balance, user.wallet.currency) }}</span>
        </div>
        <button
          :class="['action-btn', editMode ? 'action-btn--save' : 'action-btn--edit']"
          @click="$emit('toggle-edit')"
        >
          <i :class="['fas', editMode ? 'fa-save' : 'fa-pen']"></i>
          {{ editMode ? 'Guardar' : 'Editar perfil' }}
        </button>
        <button class="action-btn action-btn--ghost" @click="$emit('open-password')">
          <i class="fas fa-lock"></i>
          Contraseña
        </button>
      </div>
    </div>
  </header>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { ProfileUser } from '@js/composables/useProfile'

const props = defineProps<{
    user: ProfileUser
    stats: Record<string, any>
    editMode: boolean
    formatCurrency: (amount: number | null, currency: unknown) => string
}>()

defineEmits<{
    'toggle-edit': []
    'open-password': []
    'open-avatar': []
}>()

const quickStats = computed(() => [
    { label: 'Partidas',  value: props.stats.games_played ?? 0 },
    { label: 'Victorias', value: props.stats.games_won    ?? 0 },
    { label: 'Nivel',     value: props.stats.level        ?? 1 },
    { label: 'Logros',    value: props.stats.achievements_count ?? 0 },
])
</script>

<style scoped>
/* ── Shell ── */
.profile-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 2rem 0;
    position: relative;
}

.header-inner {
    max-width: 1100px;
    margin: 0 auto;
    padding: 0 1.5rem;
    display: flex;
    align-items: center;
    gap: 2rem;
    flex-wrap: wrap;
}

/* ── Identity ── */
.header-identity {
    display: flex;
    align-items: center;
    gap: 1.25rem;
    flex: 0 0 auto;
}

.avatar-wrap {
    position: relative;
    flex-shrink: 0;
}

.avatar-img {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    border: 3px solid rgba(255,255,255,0.6);
    object-fit: cover;
    box-shadow: 0 4px 16px rgba(0,0,0,0.25);
    transition: transform 0.2s;
}
.avatar-img:hover { transform: scale(1.04); }

.avatar-btn {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    border: none;
    background: rgba(255,255,255,0.95);
    color: #667eea;
    font-size: 0.7rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
    transition: background 0.15s;
}
.avatar-btn:hover { background: #fff; }

.identity-name {
    color: #fff;
    font-size: 1.35rem;
    font-weight: 700;
    margin: 0 0 2px;
    letter-spacing: -0.02em;
}

.identity-username {
    color: rgba(255,255,255,0.7);
    font-size: 0.875rem;
    margin: 0 0 8px;
}

.identity-badges { display: flex; gap: 6px; flex-wrap: wrap; }

.badge-pill {
    font-size: 0.72rem;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 20px;
    letter-spacing: 0.01em;
}
.badge-verified { background: rgba(40,167,69,0.9); color: #fff; }
.badge-role     { background: rgba(255,255,255,0.2); color: #fff; }

/* ── Stats ── */
.header-stats {
    display: flex;
    gap: 1.5rem;
    flex: 1;
    justify-content: center;
    flex-wrap: wrap;
}

.stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
}

.stat-value {
    color: #fff;
    font-size: 1.6rem;
    font-weight: 800;
    line-height: 1;
    text-shadow: 0 2px 6px rgba(0,0,0,0.2);
}

.stat-label {
    color: rgba(255,255,255,0.65);
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}

/* ── Actions ── */
.header-actions {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 8px;
    flex: 0 0 auto;
}

.wallet-badge {
    color: rgba(255,255,255,0.9);
    font-size: 0.875rem;
    font-weight: 600;
    background: rgba(255,255,255,0.15);
    padding: 4px 12px;
    border-radius: 20px;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 18px;
    border-radius: 10px;
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: all 0.15s ease;
    white-space: nowrap;
}

.action-btn--edit {
    background: rgba(255,255,255,0.95);
    color: #667eea;
}
.action-btn--edit:hover { background: #fff; transform: translateY(-1px); }

.action-btn--save {
    background: #28a745;
    color: #fff;
    box-shadow: 0 2px 8px rgba(40,167,69,0.4);
}
.action-btn--save:hover { background: #218838; transform: translateY(-1px); }

.action-btn--ghost {
    background: rgba(255,255,255,0.15);
    color: #fff;
    border: 1px solid rgba(255,255,255,0.3);
}
.action-btn--ghost:hover { background: rgba(255,255,255,0.25); }

/* ── Responsive ── */
@media (max-width: 768px) {
    .header-inner { flex-direction: column; align-items: flex-start; gap: 1.25rem; }
    .header-stats  { justify-content: flex-start; }
    .header-actions { align-items: flex-start; flex-direction: row; flex-wrap: wrap; }
}
</style>
