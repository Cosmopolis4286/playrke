<script setup lang="ts" name="BingoConfigForm">
  import FormTextArea from '@/js/Components/Common/Form/FormTextArea.vue'

  import { useBingoRulesForm } from '@/js/composables/games/bingo/config/useBingoRulesForm'
  import type { BingoRulesFormData } from '@/js/types/games/bingo/config/bingo-config'

  /**
   * Props
   */
  const props = defineProps<{
    /** Texto de reglas actual (viene de Inertia) */
    initialRules?: string
    /** Path de imagen actual (viene de Inertia) */
    currentImage?: string | null
    /** Estado de carga externo (mientras se envía) */
    loading?: boolean
  }>()

  /**
   * Emits
   */
  const emit = defineEmits<{
    (e: 'submit', value: BingoRulesFormData): void
  }>()

  /**
   * Composable de formulario
   */
  const { form, errors, isValid, imagePreview, onImageChange } = useBingoRulesForm(
    props.initialRules ?? ''
  )

  /**
   * Submit: valida y emite al padre
   */
  function submit(): void {
    if (!isValid.value) return
    emit('submit', form.value)
  }

  /**
   * API pública expuesta al padre (Index.vue puede llamar submit desde fuera)
   */
  defineExpose({ submit, isValid })
</script>

<template>
  <form @submit.prevent="submit" novalidate>
    <div class="row">

      <!-- Imagen actual -->
      <div class="col-12 mb-3">
        <template v-if="imagePreview || currentImage">
          <img
            :src="imagePreview ?? `/storage/game/bingo/${currentImage}`"
            alt="Imagen del reglamento"
            class="img-thumbnail"
            style="max-height: 120px"
          />
        </template>
        <div v-else class="alert alert-warning py-2">
          <strong>No hay imagen cargada.</strong>
        </div>
      </div>

      <!-- Input de imagen -->
      <div class="form-group col-md-6">
        <label for="bingo-image">Imagen del reglamento</label>
        <input
          id="bingo-image"
          type="file"
          name="image"
          class="form-control-file"
          accept="image/png, image/jpeg"
          @change="(e) => onImageChange((e.target as HTMLInputElement).files?.[0] ?? null)"
        />
        <small class="text-muted">PNG o JPEG, máximo 2 MB.</small>
      </div>

      <!-- Textarea del reglamento -->
      <FormTextArea
        name="rules"
        label="Reglamento"
        col="col-12"
        :required="true"
        :rows="10"
        :error="errors.rules"
        :modelValue="form.rules"
        @update:modelValue="form.rules = $event"
      />

      <!-- Botón guardar -->
      <div class="col-12 d-flex justify-content-center mt-2">
        <button type="submit" class="btn btn-primary" :disabled="loading">
          <i class="fa-solid fa-floppy-disk me-1" />
          Guardar
          <span
            v-if="loading"
            class="spinner-border spinner-border-sm ms-1"
            role="status"
            aria-hidden="true"
          />
        </button>
      </div>

    </div>
  </form>
</template>
