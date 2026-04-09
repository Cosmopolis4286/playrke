<template>
  <nav class="profile-tabs">
    <button
      v-for="tab in tabs"
      :key="tab.id"
      :class="['tab-btn', { 'tab-btn--active': modelValue === tab.id }]"
      @click="$emit('update:modelValue', tab.id)"
      type="button"
    >
      <i :class="['tab-icon fas', tab.icon]"></i>
      <span class="tab-label">{{ tab.label }}</span>
      <span class="tab-label-short">{{ tab.short }}</span>
    </button>
  </nav>
</template>

<script setup lang="ts">
defineProps<{ modelValue: string }>()
defineEmits<{ 'update:modelValue': [value: string] }>()

const tabs = [
    { id: 'personal',     icon: 'fa-user',        label: 'Información Personal', short: 'Perfil'   },
    { id: 'stats',        icon: 'fa-chart-bar',   label: 'Estadísticas',         short: 'Stats'    },
    { id: 'preferences',  icon: 'fa-sliders-h',   label: 'Preferencias',         short: 'Prefs'    },
    { id: 'security',     icon: 'fa-shield-alt',  label: 'Seguridad',            short: 'Seg'      },
]
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

@media (max-width: 600px) {
    .tab-label       { display: none; }
    .tab-label-short { display: inline; }
    .tab-btn { padding: 0.875rem 0.9rem; }
}
</style>
