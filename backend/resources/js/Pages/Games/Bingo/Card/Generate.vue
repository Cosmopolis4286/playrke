<script setup lang="ts">
  import { computed } from 'vue'
  import CardHeader from '@js/Components/Common/Card/CardHeader.vue'
  import BingoCardPos from '@/js/Components/games/bingo/Card/Pos/BingoCardPos.vue'
  import type { BingoCardGeneratePageProps } from '@/js/types/games/bingo/card/bingo-card-generate-page'

  // ==========================================================================
  // Props (Inertia)
  // ==========================================================================
  const props = defineProps<BingoCardGeneratePageProps>()

  const toOptions = (items: { id?: string | number; value?: string | number; label: string }[] | undefined) =>
    (items ?? []).map(i => ({ value: String(i.id ?? i.value), label: i.label }))

  const roomTypeOptions = computed(() => toOptions(props.roomTypes))

  /**
   * Nota: `props.gameModes` es la key expuesta por Inertia (el backend aún no
   * la renombró) — se mapea aquí a vocabulario interno "Tipo de Conexión".
   */
  const connectionTypeOptions = computed(() => toOptions(props.gameModes))

  const prizePoolTypeOptions = computed(() => toOptions(props.prizePoolTypes))
</script>

<template>
  <div v-if="!props.canSell" class="card">
    <CardHeader :title="props.title" />
    <div class="card-body">
      <div class="alert alert-info text-center mb-0">
        {{ props.blockedMessage ?? '¡No puedes vender cartones, contacta a tu gestor!' }}
      </div>
    </div>
  </div>

  <template v-else>
    <header class="bcg-head">
      <div class="bcg-head__icon"><i class="fas fa-cash-register"></i></div>
      <div>
        <h1 class="bcg-head__title">{{ props.title }}</h1>
        <p class="bcg-head__sub">Vende cartones a tus clientes en 3 pasos: sala, comprador y pago.</p>
      </div>
    </header>

    <BingoCardPos
      :room-type-options="roomTypeOptions"
      :connection-type-options="connectionTypeOptions"
      :prize-pool-type-options="prizePoolTypeOptions"
    />
  </template>
</template>

<style scoped>
.bcg-head { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
.bcg-head__icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: grid;
  place-items: center;
  font-size: 18px;
  color: #fff;
  background: linear-gradient(135deg, #0ea5e9, #0369a1);
  flex-shrink: 0;
}
.bcg-head__title { font-size: 20px; font-weight: 800; margin: 0; color: var(--ds-text); }
.bcg-head__sub { font-size: 13px; margin: 0; color: var(--ds-text-2); }
</style>
