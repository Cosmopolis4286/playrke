<template>
  <ol class="breadcrumb float-sm-right">
    <li class="breadcrumb-item">
      <a href="{{ route('dashboard') }}">
        <i class="fas fa-home mr-1"></i>
        Dashboard
      </a>
    </li>
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
  color: #6c757d;
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
  color: #6c757d;
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
