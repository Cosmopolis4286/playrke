<script setup lang="ts" name="BingoConfigForm">
  import { computed } from 'vue'
  import { useBingoConfigForm } from '@/js/composables/exchange-rate/useBingoConfigForm'
  import type { BingoConfig } from '@/js/types/games/bingo/config/bingo-config';

  /**
   * Props
   */
  const props = defineProps<{
    modelValue: Partial<BingoConfig>
  }>()

  /**
   * Emits
   */
  const emit = defineEmits<{
    (e: 'submit'): void
    (e: 'update:modelValue', value: any): void
  }>()

  /**
   * Composable de formulario
   */
  const { form, errors, maskOptions, isEditMode, isValid, validate } = useBingoConfigForm(
    props.modelValue,
    emit
  )

  /* =======================
   * Computed
   * ======================= */


  /* =======================
   * Methods
   * ======================= */

  /**
   * Submit interno del formulario
   */
  const submit = (): boolean => {
    validate()
    if (!isValid.value) return false

    emit('submit')
    return true
  }

  /**
   * API pública para el modal
   */
  defineExpose({
    submit,
    isValid
  })
</script>
<template>
  <form @submit.prevent="submit" novalidate>
    <div class="row">
       <div class="form-group">
                            <label for="image">Imagen</label>
                            <input type="file" id="image" class="form-control-file" name="image"
                                accept="image/png, image/jpeg">
                        </div>
                        <div class="mr-2">
                            @if ($imageBingoRules)
                                <img style="max-height: 68px" src="{{ asset("storage/game/bingo/$imageBingoRules") }}"
                                    alt="Game Image">
                            @else
                                <div class="alert alert-primary p-3">
                                    <h6 class="alert-heading m-0"><strong>¡Atención, no hay una imagen cargada!</strong>
                                    </h6>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="bingo-rules">Reglamento:</label>
                        <textarea class="form-control" id="bingo-rules" name="rules" rows="10">
                            {{ $gameRules ?? view('includes.info.game.bingo')->render() }}
                        </textarea>
                    </div>
                    <div class="d-flex justify-content-center">
                        <button type="button" id="btn-form-bingo-rules" class="btn btn-primary">
                            <i class="fa-solid fa-floppy-disk"></i> Guardar
                            <span id="loader-btn-ctn" class="spinner-border spinner-border-sm" role="status"
                                aria-hidden="true" style="display: none"></span>
                        </button>
                    </div>
    </div>
  </form>
</template>
