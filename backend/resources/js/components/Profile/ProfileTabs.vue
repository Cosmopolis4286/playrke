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
      <span v-if="tab.badge" class="tab-badge">{{ tab.badge }}</span>
    </button>
  </nav>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { ProfileUser } from '@js/composables/useProfile'

const props = defineProps<{
    modelValue: string
    user: ProfileUser
}>()
defineEmits<{ 'update:modelValue': [value: string] }>()

const ADMIN_ROLES = ['superadmin', 'admin', 'treasurer', 'seller', 'customer']

const isAdmin = computed(() =>
    props.user.roles?.some(r => ADMIN_ROLES.includes(r.name)) ?? false
)

const needsAttention = computed(() =>
    !props.user.tos_accepted_at || !props.user.is_verified || props.user.kyc_status === 'REJECTED'
)

const ALL_TABS = [
    { id: 'personal',    icon: 'fa-user',        label: 'Información Personal', short: 'Perfil',  roles: null },
    { id: 'stats',       icon: 'fa-chart-bar',   label: 'Estadísticas',         short: 'Stats',   roles: ['player', 'vip_player'] },
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
    background: #fff;
    border-bottom: 1px solid #eaecf0;
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
    color: #8892a4;
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    transition: color 0.15s, border-color 0.15s, background 0.15s;
    white-space: nowrap;
}

.tab-btn:hover {
    color: #667eea;
    background: #f6f7ff;
}

.tab-btn--active {
    color: #667eea;
    border-bottom-color: #667eea;
    background: #f6f7ff;
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

@media (max-width: 600px) {
    .tab-label       { display: none; }
    .tab-label-short { display: inline; }
    .tab-btn { padding: 0.875rem 0.9rem; }
}
</style>
