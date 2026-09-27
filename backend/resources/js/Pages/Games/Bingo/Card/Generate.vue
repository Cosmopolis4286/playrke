<script setup lang="ts">
  import { computed } from 'vue'
  import CardHeader from '@js/Components/Common/Card/CardHeader.vue'
  import BingoCardGenerateForm from '@/js/Components/games/bingo/Card/Generate/BingoCardGenerateForm.vue'
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
   * Opciones para tipo de conexión.
   * Nota: `props.gameModes` es la key expuesta por Inertia (el backend aún no
   * la renombró) — se mapea aquí a vocabulario interno "Tipo de Conexión".
   */
  const connectionTypeOptions = computed(() => {
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

    <div v-if="!props.canSell" class="card-body">
      <div class="alert alert-info text-center mb-0">
        {{ props.blockedMessage ?? '¡No puedes vender cartones, contacta a tu gestor!' }}
      </div>
    </div>

    <div v-else class="card-body">
      <BingoCardGenerateForm
        :room-type-options="roomTypeOptions"
        :connection-type-options="connectionTypeOptions"
        :prize-pool-type-options="prizePoolTypeOptions"
      />
    </div>
  </div>
</template>
