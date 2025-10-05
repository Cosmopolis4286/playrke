<template>
  <ul class="nav nav-tabs mb-3" id="currencyTab" role="tablist">
    <li v-for="curr in currencies" :key="curr.id" class="nav-item" role="presentation">
      <a
        :class="['nav-link', { active: curr.id === currency }]"
        :id="`${curr.id}-tab`"
        data-bs-toggle="tab"
        :href="`#${curr.id}`"
        role="tab"
        :aria-controls="curr.id"
        :aria-selected="curr.id === currency"
        @click="handleCurrencyChange(curr.id)"
      >
        <i :class="getCurrencyIcon(curr.id)" class="me-1"></i>
        {{ curr.name }}
      </a>
    </li>
  </ul>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'

interface Currency {
  id: string
  name: string
  symbol: string
  encrypted: string
}

interface RouteWithCurrency {
  route: string
  parameters?: Record<string, any>
}

interface Props {
  currencies: Currency[]
  currency: string
  routeWithCurrency?: RouteWithCurrency
}

const props = defineProps<Props>()
const router = useRouter()

/**
 * Obtener icono según la moneda
 */
function getCurrencyIcon(currencyId: string): string {
  const icons: Record<string, string> = {
    USD: 'fa-solid fa-dollar-sign',
    EUR: 'fa-solid fa-euro-sign',
    COP: 'fa-solid fa-peso-sign',
    BTC: 'fa-brands fa-bitcoin',
    ETH: 'fa-brands fa-ethereum'
  }
  return icons[currencyId] || 'fa-solid fa-coins'
}

/**
 * Manejar cambio de moneda
 */
function handleCurrencyChange(currencyId: string) {
  if (props.routeWithCurrency) {
    const { route, parameters = {} } = props.routeWithCurrency
    router.push({
      name: route,
      params: {
        ...parameters,
        currency: currencyId
      }
    })
  }
}
</script>

<style scoped>
.nav-link {
  cursor: pointer;
  transition: all 0.3s ease;
}

.nav-link:hover {
  background-color: rgba(0, 0, 0, 0.05);
}

.nav-link.active {
  color: var(--bs-primary);
}
</style>
