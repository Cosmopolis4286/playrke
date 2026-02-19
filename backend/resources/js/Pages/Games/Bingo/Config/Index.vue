<script setup lang="ts">
  import CardHeader from '@js/Components/Common/Card/CardHeader.vue'
  import ActionButtons from '@/js/Components/Common/Button/ActionButtons.vue';
  import BingoConfigForm from '@/js/Components/Games/Bingo/Config/BingoConfigForm.vue'
import BingoRulesModal from './Rules/BingoRulesModal.vue';

  import { useBingoConfig } from '@/js/composables/games/bingo/config/useBingoConfig'
  import type { BingoConfigPageProps } from '@/js/types/games/bingo/config/bingo-config'

  const props = defineProps<BingoConfigPageProps>()

  const { loadingRules, loadingImage, saveRules, saveImage } = useBingoConfig()
</script>

<template>
  <div class="card">
    <CardHeader title="Configuración Bingo" />
    <ActionButtons
    <button type="button" class="btn btn-sm btn-success mr-2" data-toggle="modal" data-target="#modal-bingo-rules">
                    <i class="fas fa-eye"></i> Ver Reglamento
                </button>
                <a href="{{ route('bingo.bingoPatterns', $userToken) }}" class="btn btn-sm btn-primary">
                    <i class="fa-solid fa-gamepad"></i> Ver Jugadas
                </a>
    />
    <div class="card-body">
      <BingoConfigForm
        :initial-rules="props.bingoRules ?? ''"
        :current-image="props.imageBingoRules"
        :loading-rules="loadingRules"
        :loading-image="loadingImage"
        @save-rules="saveRules"
        @save-image="saveImage"
      />
    </div>
  </div>
  <BingoRulesModal/>
</template>
