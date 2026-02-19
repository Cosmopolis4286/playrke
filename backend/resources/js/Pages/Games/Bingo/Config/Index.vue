<script setup lang="ts">
  import CardHeader from '@js/Components/Common/Card/CardHeader.vue'
  import BingoConfigForm from '@/js/Components/Games/Bingo/Config/BingoConfigForm.vue'

  import { useBingoConfig } from '@/js/composables/games/bingo/config/useBingoConfig'

  import type { BingoConfigPageProps, BingoRulesFormData } from '@/js/types/games/bingo/config/bingo-config'

  /**
   * Props de Inertia: imageBingoRules y bingoRules
   */
  const props = defineProps<BingoConfigPageProps>()

  /**
   * Composable con la lógica de guardado
   */
  const { loading, save } = useBingoConfig()

  /**
   * Recibe el form del hijo y lo envía al backend
   */
  async function onSubmit(data: BingoRulesFormData): Promise<void> {
    await save(data)
  }
</script>

<template>
  <div class="card">
    <CardHeader title="Configuración Bingo" />
    <div class="card-body">
      <BingoConfigForm
        :initial-rules="props.bingoRules ?? ''"
        :current-image="props.imageBingoRules"
        :loading="loading"
        @submit="onSubmit"
      />
    </div>
  </div>
</template>
