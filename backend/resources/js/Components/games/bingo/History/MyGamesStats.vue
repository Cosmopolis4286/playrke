<script setup lang="ts">
import DashboardStatCard from '@js/Components/Common/Dashboard/DashboardStatCard.vue'

defineProps<{
  totalGames: number
  wonOnPage: number
  totalCards: number
}>()
</script>

<template>
  <div class="row g-2 mb-3">
    <div class="col-6 col-md-4">
      <DashboardStatCard
        label="Partidas totales"
        :value="totalGames"
        icon="fas fa-gamepad"
        variant="primary"
      />
    </div>
    <div class="col-6 col-md-4">
      <DashboardStatCard
        label="Victorias (página)"
        :value="wonOnPage"
        icon="fas fa-trophy"
        variant="success"
      />
    </div>
    <div class="col-6 col-md-4">
      <DashboardStatCard
        label="Cartones (página)"
        :value="totalCards"
        icon="fas fa-th"
        variant="warning"
      />
    </div>
  </div>
</template>
