<template>
  <div class="min-h-screen bg-gradient-to-br from-purple-900 via-pink-900 to-red-900">
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
      <h1 class="text-4xl font-bold text-white mb-8">Torneos</h1>

      <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-8 border border-white/20 text-center">
        <div class="mb-6">
          <Icon name="lucide:trophy" class="w-24 h-24 text-yellow-400 mx-auto mb-4" />
        </div>
        <h2 class="text-2xl font-semibold text-white mb-4">
          Próximamente
        </h2>
        <p class="text-gray-300 text-lg mb-6">
          La sección de torneos estará disponible muy pronto.
          Podrás competir contra otros jugadores y ganar premios increíbles.
        </p>
        <NuxtLink
          to="/player/lobby"
          class="inline-flex items-center px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg transition"
        >
          <Icon name="lucide:arrow-left" class="w-5 h-5 mr-2" />
          Volver al Lobby
        </NuxtLink>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * Página de torneos - En construcción
 */

definePageMeta({
  middleware: ['auth'],
  layout: 'player'
})
</script>
