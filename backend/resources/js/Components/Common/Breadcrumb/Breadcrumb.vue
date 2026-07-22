<template>
  <ol class="breadcrumb float-sm-right">
    <li
      v-for="(item, index) in items"
      :key="index"
      :class="['breadcrumb-item', { active: index === items.length - 1 }]"
    >
      <a
        v-if="item.route && index < items.length - 1"
        :href="item.route"
      >
        <i
          v-if="item.icon"
          :class="item.icon"
          class="mr-1"
        ></i>
        {{ item.label }}
      </a>
      <span v-else>
        <i
          v-if="item.icon"
          :class="item.icon"
          class="mr-1"
        ></i>
        {{ item.label }}
      </span>
    </li>
  </ol>
</template>

<script setup lang="ts">
interface BreadcrumbItem {
  label: string
  route?: string
  icon?: string
}

interface Props {
  items: BreadcrumbItem[]
}

defineProps<Props>()
</script>

<style scoped>
.breadcrumb {
  background-color: transparent;
  padding: 0.75rem 0;
  margin-bottom: 0;
  font-size: 0.875rem;
}

.breadcrumb-item + .breadcrumb-item::before {
  content: ">";
  color: var(--ds-text-2);
  padding: 0 0.5rem;
}

.breadcrumb-item a {
  color: #007bff;
  text-decoration: none;
  transition: color 0.2s;
}

.breadcrumb-item a:hover {
  color: #0056b3;
  text-decoration: underline;
}

.breadcrumb-item.active {
  color: var(--ds-text-2);
  font-weight: 500;
}

.breadcrumb-item.active span {
  color: inherit;
}

@media (max-width: 768px) {
  .breadcrumb {
    font-size: 0.8rem;
  }
  
  .breadcrumb-item + .breadcrumb-item::before {
    padding: 0 0.25rem;
  }
}
</style>
