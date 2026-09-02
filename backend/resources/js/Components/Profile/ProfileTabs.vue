<template>
  <nav class="profile-tabs">
    <button
      v-for="tab in visibleTabs"
      :key="tab.id"
      :class="['tab-btn', { 'tab-btn--active': modelValue === tab.id }]"
      @click="$emit('update:modelValue', tab.id)"
      type="button"
    >
      <i :class="['tab-icon fas', tab.icon]"></i>
      <span class="tab-label">{{ tab.label }}</span>
      <span class="tab-label-short">{{ tab.short }}</span>
      <span v-if="tab.badge" class="tab-badge" :class="{ 'tab-badge--info': tab.id === 'missions' }">{{ tab.badge }}</span>
    </button>
  </nav>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { ProfileUser } from '@js/composables/useProfile'

const props = defineProps<{
    modelValue: string
    user: ProfileUser
    missionsPending?: number
}>()
defineEmits<{ 'update:modelValue': [value: string] }>()

const needsAttention = computed(() =>
    !props.user.tos_accepted_at || !props.user.is_verified || props.user.kyc_status === 'REJECTED'
)

// Orden por frecuencia de uso real: Personal (identidad) y Misiones (se
// revisa a diario) primero, Estadísticas/Referidos/Preferencias después
// (consulta ocasional), Seguridad al final (solo cuando hace falta actuar).
//
// Misiones/Estadísticas (logros) restringidos a 'player': ambos sistemas
// están construidos exclusivamente sobre datos de jugador (games_played_count,
// games_won_count, total_winnings, wallet de jugador) — un admin/commerce/
// treasurer/subsidiary/seller/customer nunca genera esos datos, así que sin
// este filtro esos roles verían tabs permanentemente vacíos (0 XP, 0/N
// logros) sin ningún sentido de negocio. 'vip_player' no es un rol real del
// sistema (ver RoleSeeder.php) — se quitó, quedaba como código muerto.
const ALL_TABS = [
    { id: 'personal',    icon: 'fa-user',        label: 'Información Personal', short: 'Perfil',  roles: null },
    { id: 'missions',    icon: 'fa-bullseye',    label: 'Misiones',             short: 'Misiones',roles: ['player'], badgeFn: () => (props.missionsPending ?? 0) > 0 ? String(props.missionsPending) : null },
    { id: 'stats',       icon: 'fa-chart-bar',   label: 'Estadísticas',         short: 'Stats',   roles: ['player'] },
    { id: 'referrals',   icon: 'fa-user-friends',label: 'Referidos',            short: 'Refs',    roles: null },
    { id: 'preferences', icon: 'fa-sliders-h',   label: 'Preferencias',         short: 'Prefs',   roles: null },
    { id: 'security',    icon: 'fa-shield-alt',  label: 'Seguridad',            short: 'Seg',     roles: null, badgeFn: (u: ProfileUser) => (!u.tos_accepted_at || !u.is_verified) ? '!' : null },
]

const visibleTabs = computed(() =>
    ALL_TABS
        .filter(tab => {
            if (!tab.roles) return true
            return props.user.roles?.some(r => (tab.roles as string[]).includes(r.name)) ?? false
        })
        .map(tab => ({
            ...tab,
            badge: (tab as any).badgeFn ? (tab as any).badgeFn(props.user) : null,
        }))
)
</script>

<style scoped>
.profile-tabs {
    display: flex;
    background: var(--ds-surface);
    border-bottom: 1px solid var(--ds-border);
    overflow-x: auto;
    overflow-y: hidden;
    scrollbar-width: none;
}

.tab-btn {
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 1rem 1.4rem;
    border: none;
    background: transparent;
    color: var(--ds-text-2);
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    transition: color 0.15s, border-color 0.15s, background 0.15s;
    white-space: nowrap;
}

.tab-btn:hover {
    color: #667eea;
    background: var(--ds-surface-2);
}

.tab-btn--active {
    color: #667eea;
    border-bottom-color: #667eea;
    background: var(--ds-surface-2);
}

.tab-icon { font-size: 0.85rem; }

.tab-label       { display: inline; }
.tab-label-short { display: none; }

/* Badge de atención en tab */
.tab-badge {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 16px; height: 16px; border-radius: 99px;
    background: #ef4444; color: #fff; font-size: 9px; font-weight: 700;
    padding: 0 4px; line-height: 1;
}
.tab-badge--info { background: #f59e0b; }

@media (max-width: 600px) {
    .tab-label       { display: none; }
    .tab-label-short { display: inline; }
    .tab-btn { padding: 0.875rem 0.9rem; }
}
</style>
