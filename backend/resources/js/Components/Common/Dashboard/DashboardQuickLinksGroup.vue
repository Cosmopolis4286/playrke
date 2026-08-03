<script setup lang="ts" name="DashboardQuickLinksGroup">
  import { computed, ref } from 'vue'
  import DashboardQuickLinks from '@/js/Components/Common/Dashboard/DashboardQuickLinks.vue'
  import type { QuickLinkGroup } from '@/js/composables/common/dashboard/useDashboardQuickLinks'

  const props = withDefaults(defineProps<{
    groups: QuickLinkGroup[]
    columns?: 2 | 3 | 4
  }>(), {
    columns: 4,
  })

  const search = ref('')

  const filteredGroups = computed(() => {
    const term = search.value.trim().toLowerCase()
    if (!term) return props.groups

    return props.groups
      .map((group) => ({
        ...group,
        links: group.links.filter((link) =>
          link.label.toLowerCase().includes(term) ||
          link.description?.toLowerCase().includes(term)
        ),
      }))
      .filter((group) => group.links.length > 0)
  })

  const hasResults = computed(() => filteredGroups.value.some((g) => g.links.length > 0))
</script>

<template>
  <div class="qlg">
    <div class="qlg__search">
      <i class="fas fa-search qlg__search-icon"></i>
      <input
        v-model="search"
        type="text"
        class="qlg__search-input"
        placeholder="Buscar acceso rápido…"
      >
    </div>

    <div v-if="hasResults" class="qlg__groups">
      <div v-for="group in filteredGroups" :key="group.key" class="qlg__group">
        <p class="qlg__group-title">{{ group.title }}</p>
        <DashboardQuickLinks :links="group.links" :columns="columns" />
      </div>
    </div>
    <p v-else class="qlg__empty">Sin accesos que coincidan con "{{ search }}".</p>
  </div>
</template>

<style scoped>
.qlg__search {
  position: relative;
  margin-bottom: 1rem;
  max-width: 320px;
}
.qlg__search-icon {
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  font-size: 0.75rem;
  color: var(--ds-text-2);
  pointer-events: none;
}
.qlg__search-input {
  width: 100%;
  padding: 0.45rem 0.75rem 0.45rem 2rem;
  font-size: 0.8rem;
  border-radius: 8px;
  border: 1px solid var(--ds-border);
  background: var(--ds-surface-2);
  color: var(--ds-text);
}
.qlg__search-input::placeholder { color: var(--ds-text-2); }
.qlg__search-input:focus {
  outline: none;
  border-color: #667eea;
}

.qlg__groups {
  display: flex;
  flex-direction: column;
  gap: 1.1rem;
}
.qlg__group-title {
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .05em;
  color: var(--ds-text-2);
  margin-bottom: 0.6rem;
}

.qlg__empty {
  font-size: 0.8rem;
  color: var(--ds-text-2);
  text-align: center;
  padding: 1.5rem 0;
  margin: 0;
}
</style>
