<template>
  <ul class="nav nav-tabs mt-n2 mb-2" role="tablist">
    <li class="nav-item" v-for="item in currencies" :key="item.id">
      <a class="nav-link" :class="{ active: isActive(item.id) }" :id="tabId(item)" :href="tabLink(item)" role="tab"
        :aria-controls="tabId(item)" :aria-selected="isActive(item.id) ? 'true' : 'false'">
        {{ getCoinLabel(item) }}
      </a>
    </li>
  </ul>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  currencies: { type: Array, required: true }, // cada item debe tener id, encrypted, y label
  currency: { type: [Number, String], required: true },
  routeWithCoin: {
    type: Object,
    required: true,
    validator: val =>
      typeof val.route === 'string' && typeof val.parameters === 'object'
  }
})

// Funciones auxiliares
const isActive = id => String(id) === String(props.currency)

const tabId = item =>
  `${item.id}-${item.iso_code ?? item.currency ?? item.short_name}`

const getCoinLabel = item =>
  item.iso_code ?? item.currency ?? item.short_name

const tabLink = item => {
  return $route(props.routeWithCoin.route, {
    ...props.routeWithCoin.parameters,
    currency: item.encrypted // <- usamos encrypted del backend
  })
}
</script>
