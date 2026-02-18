<script setup lang="ts">
  import { ref, computed, watch } from 'vue'

  import CardHeader from '@js/Components/Common/Card/CardHeader.vue'
  import BingoConfigForm from '@/js/Components/Games/Bingo/Config/BingoConfigForm.vue'
  import BingoRulesModal from './Rules/BingoRulesModal.vue'

  import { useBingoConfig } from '@/js/composables/games/bingo/config/useBingoConfig'

  import type { BingoConfigPageProps } from '@/js/types/games/bingo/config/bingo-config'
  /**
   * Props Inertia
   */
  const props = defineProps<BingoConfigPageProps>()

  /**
   * Composable de negocio para manejo de la configuración
   */
  const {} = useBingoConfig()

  /**
   * Navega a la vista de Ver Jugadas
   */
</script>
<template>
  <div class="card">
    <CardHeader title="Configuración Bingo" />
    <div class="card-body">
      <BingoConfigForm v-model="exchangeRateData" />
    </div>
  </div>
  <BingoRulesModal />
</template>
