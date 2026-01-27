<script setup lang="ts">
  import { defineProps, defineEmits } from 'vue'

  interface Item {
    id: number | string
    iso_code_2: string
  }

  const props = defineProps<{
    items: Item[]
    activeCode: string
  }>()

  const emit = defineEmits<{
    (e: 'update:activeCode', code: string): void
    (e: 'tabSelected', code: string): void
  }>()

  const isActive = (item: Item) => {
    return props.activeCode.toLowerCase() === item.iso_code_2.toLowerCase()
  }

  const selectTab = (item: Item) => {
    emit('update:activeCode', item.iso_code_2)
    emit('tabSelected', item.iso_code_2)
  }
</script>

<template>
  <ul class="nav nav-tabs" role="tablist">
    <li v-for="item in items" :key="item.id" class="nav-item">
      <a
        href="#"
        class="nav-link"
        :class="{ active: isActive(item) }"
        :id="`${item.id}-tab`"
        role="tab"
        :aria-controls="`${item.id}`"
        :aria-selected="isActive(item) ? 'true' : 'false'"
        @click.prevent="selectTab(item)"
      >
        {{ item.iso_code_2 }}
      </a>
    </li>
  </ul>
</template>
