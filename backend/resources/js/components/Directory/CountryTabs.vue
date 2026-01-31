<script setup lang="ts">
  import { defineEmits, withDefaults } from 'vue'

  interface Item {
    id: number | string
    iso_code_2: string
  }

  /**
   * Props del componente
   */
  const props = withDefaults(
    defineProps<{
      items: Item[]
      activeCode?: string
    }>(),
    {
      activeCode: ''
    }
  )

  const emit = defineEmits<{
    (e: 'update:activeCode', code: string): void
    (e: 'tabSelected', code: string): void
  }>()

  /**
   * Verifica si el tab está activo
   * @param item - Item del tab
   * @returns true si el tab está activo
   */
  const isActive = (item: Item) => {
    if (!props.activeCode || !item.iso_code_2) return false
    return props.activeCode.toLowerCase() === item.iso_code_2.toLowerCase()
  }

  /**
   * Selecciona un tab y emite los eventos correspondientes
   * @param item - Item del tab seleccionado
   */
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
