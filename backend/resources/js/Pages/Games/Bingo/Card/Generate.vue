<script setup lang="ts">
  import { computed } from 'vue'
  import CardHeader from '@js/Components/Common/Card/CardHeader.vue'
  import BingoCardGenerateForm from '@/js/Components/Games/Bingo/Card/Generate/BingoCardGenerateForm.vue'
  import type { BingoCardGeneratePageProps } from '@/js/types/games/bingo/card/bingo-card-generate-page'

  // ==========================================================================
  // Props (Inertia)
  // ==========================================================================
  const props = defineProps<BingoCardGeneratePageProps>()

  /**
   * Opciones para tipos de sala.
   */
  const roomTypeOptions = computed(() => {
    return (props.roomTypes ?? []).map(r => ({
      value: String(r.id),
      label: r.label
    }))
  })

  /**
   * Opciones para modos de juego.
   */
  const gameModeOptions = computed(() => {
    return (props.gameModes ?? []).map(g => ({
      value: String(g.id),
      label: g.label
    }))
  })

  /**
   * Opciones para tipos de bote.
   */
  const prizePoolTypeOptions = computed(() => {
    return (props.prizePoolTypes ?? []).map(p => ({
      value: String(p.id),
      label: p.label
    }))
  })
</script>

<template>
  <div class="card">
    <CardHeader :title="props.title" />

    <div class="card-body">
      <BingoCardGenerateForm
        :room-type-options="roomTypeOptions"
        :game-mode-options="gameModeOptions"
        :prize-pool-type-options="prizePoolTypeOptions"
      />
    </div>
  </div>
</template>
