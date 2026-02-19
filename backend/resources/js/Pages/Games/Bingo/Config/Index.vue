<script setup lang="ts">
  import { ref } from 'vue'

  import CardHeader from '@js/Components/Common/Card/CardHeader.vue'
  import ActionButtons from '@/js/Components/Common/Button/ActionButtons.vue'
  import BingoConfigForm from '@/js/Components/Games/Bingo/Config/BingoConfigForm.vue'
  import BingoRulesModal from './Rules/BingoRulesModal.vue'

  import { useBingoConfig } from '@/js/composables/games/bingo/config/useBingoConfig'
  import type { BingoConfigPageProps } from '@/js/types/games/bingo/config/bingo-config'

  const props = defineProps<BingoConfigPageProps>()

  const { loadingRules, loadingImage, saveRules, saveImage } = useBingoConfig()

  const showRulesModal = ref(false)
</script>

<template>
  <div class="card">
    <CardHeader title="Configuración Bingo" />

    <div class="card-body">
      <!-- Acciones de cabecera -->
      <ActionButtons
        :selected-ids="[]"
        :show-create="false"
        :show-edit="false"
        :show-delete="false"
      >
        <template #actions>
          <button type="button" class="btn btn-sm btn-success" @click="showRulesModal = true">
            <i class="fas fa-eye me-1" />
            Ver Reglamento
          </button>

          <a href="/bingo-patterns" class="btn btn-sm btn-primary">
            <i class="fa-solid fa-gamepad me-1" />
            Patrones
          </a>
        </template>
      </ActionButtons>

      <!-- Formulario de edición (imagen + reglamento) -->
      <div class="mt-4">
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
  </div>

  <!-- Modal de solo lectura: imagen + reglamento como HTML -->
  <BingoRulesModal
    v-model="showRulesModal"
    :rules="props.bingoRules"
    :image="props.imageBingoRules"
  />
</template>
