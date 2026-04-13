<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'

interface Currency {
  id: string | number
  name: string
  symbol: string
}

interface Props {
  coins: Currency[]
  wallet: Record<string, any>
}

const props = defineProps<Props>()

const search = ref('')
const isLoading = ref<string | null>(null)

const filteredCoins = computed(() =>
  props.coins.filter(c =>
    c.name.toLowerCase().includes(search.value.toLowerCase()) ||
    c.symbol.toLowerCase().includes(search.value.toLowerCase())
  )
)

function enterRoom(coin: Currency) {
  isLoading.value = String(coin.id)
  router.get(`/trivia-game/rooms/${coin.symbol}`, {}, {
    onFinish: () => { isLoading.value = null }
  })
}
</script>

<template>
  <Head title="Trivia - Salas disponibles" />

  <div class="min-h-screen bg-gray-100">
    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">

      <!-- Cabecera -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Trivia Multijugador</h1>
        <p class="mt-1 text-sm text-gray-500">Selecciona una moneda para ver las salas disponibles</p>
      </div>

      <!-- Buscador -->
      <div class="mb-6">
        <input
          v-model="search"
          type="text"
          placeholder="Buscar moneda..."
          class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        />
      </div>

      <!-- Sin salas -->
      <div v-if="filteredCoins.length === 0" class="rounded-lg bg-white p-10 text-center shadow-sm">
        <p class="text-gray-500">No hay salas disponibles en este momento.</p>
      </div>

      <!-- Grid de monedas/salas -->
      <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <button
          v-for="coin in filteredCoins"
          :key="coin.id"
          :disabled="isLoading === String(coin.id)"
          class="flex flex-col items-center rounded-xl bg-white p-6 shadow-sm transition hover:shadow-md focus:outline-none focus:ring-2 focus:ring-indigo-400 disabled:opacity-60"
          @click="enterRoom(coin)"
        >
          <div class="mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-indigo-100 text-xl font-bold text-indigo-700">
            {{ coin.symbol.slice(0, 3).toUpperCase() }}
          </div>
          <span class="text-base font-semibold text-gray-800">{{ coin.name }}</span>
          <span class="text-xs text-gray-400">{{ coin.symbol }}</span>
          <span v-if="isLoading === String(coin.id)" class="mt-2 text-xs text-indigo-500">Cargando...</span>
        </button>
      </div>

    </div>
  </div>
</template>
