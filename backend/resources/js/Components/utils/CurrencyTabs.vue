<template>
  <div class="currency-tabs">
    <ul class="nav nav-tabs">
      <li v-for="currency in currencies" :key="currency.id" class="nav-item">
        <button
          class="nav-link"
          :class="{ active: currency.id === props.currency }"
          @click="$emit('update:currency', currency.id)"
        >
          {{ currency.symbol || currency.iso_code || currency.name }}
        </button>
      </li>
    </ul>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  currencies: Array<{ id: string | number; name?: string; symbol?: string; iso_code?: string }>
  currency?: string | number | null
  routeWithCurrency?: { route: string; parameters?: Record<string, unknown> }
}>()

defineEmits<{ (e: 'update:currency', id: string | number): void }>()
</script>
