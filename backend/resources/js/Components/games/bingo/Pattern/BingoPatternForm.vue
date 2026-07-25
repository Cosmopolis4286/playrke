<script setup lang="ts" name="BingoPatternForm">
  import { computed } from 'vue'

  import FormInput         from '@/js/Components/Common/Form/FormInput.vue'
  import FormSelect        from '@/js/Components/Common/Form/FormSelect.vue'
  import FormSwitch        from '@/js/Components/Common/Form/FormSwitch.vue'
  import BingoCardSelector from '@/js/Components/games/bingo/Pattern/BingoCardSelector.vue'
  import { validateBingoPatternForm } from '@/js/composables/games/bingo/pattern/bingoPatternFormSchema'

  import type { BingoPattern } from '@/js/types/games/bingo/pattern/bingo-patterns'

  /* ==========================================================================
   * Props / Emits
   * ========================================================================== */

  const props = defineProps<{
    modelValue: Partial<BingoPattern>
    readonly?:  boolean
    isAdmin?:   boolean
  }>()

  const emit = defineEmits<{
    (e: 'submit'): void
    (e: 'update:modelValue', value: Partial<BingoPattern>): void
  }>()

  /* ==========================================================================
   * v-model computado
   * ========================================================================== */

  const form = computed<Partial<BingoPattern>>({
    get: () => props.modelValue,
    set: v => emit('update:modelValue', v)
  })

  /* ==========================================================================
   * Opciones estáticas
   * ========================================================================== */

  const typePatternOptions = [
    { value: 'UNIQUE',   label: 'Único' },
    { value: 'MULTIPLE', label: 'Múltiple' },
  ]

  /** boolean ↔ 'Y'/'N' para FormSwitch */
  const defaultPatternBool = computed<boolean>({
    get: () => form.value.default_pattern === 'Y',
    set: v => { form.value = { ...form.value, default_pattern: v ? 'Y' : 'N' } },
  })

  // Los patrones MULTIPLE referencian num_id de otros patrones, no celdas del cartón
  const isMultiple = computed(() => form.value.type_pattern === 'MULTIPLE')

  /* ==========================================================================
   * Validación reactiva
   * ========================================================================== */

  const errors = computed<Record<string, string>>(() => validateBingoPatternForm(form.value))

  const isValid = computed(() => Object.keys(errors.value).length === 0)

  /* ==========================================================================
   * Submit / Expose
   * ========================================================================== */

  const submit = (): boolean => {
    if (!isValid.value) return false
    emit('submit')
    return true
  }

  defineExpose({ submit, isValid, reset: () => {} })
</script>

<template>
  <form id="bingo-patterns-form" novalidate @submit.prevent="submit">
    <div class="row">

      <FormInput
        label="Nombre del patrón"
        name="name"
        required
        maxlength="30"
        placeholder="Ej: Línea horizontal"
        col="col-sm-8"
        :error="readonly ? undefined : errors.name"
        :disabled="readonly"
        v-model="form.name"
      />

      <!-- No-admin: texto plano con la descripción legible del tipo -->
      <FormInput
        v-if="readonly"
        label="Tipo"
        name="type_pattern"
        col="col-sm-4"
        :model-value="typePatternOptions.find(o => o.value === form.type_pattern)?.label ?? form.type_pattern ?? ''"
        disabled
      />
      <FormSelect
        v-else
        label="Tipo"
        name="type_pattern"
        required
        placeholder="Seleccionar..."
        :options="typePatternOptions"
        optionValue="value"
        optionLabel="label"
        col="col-sm-4"
        :error="errors.type_pattern"
        v-model="form.type_pattern"
      />

      <div class="col-sm-12 text-center">
        <label class="form-label d-block">
          Casillas del patrón
          <span v-if="!readonly" class="text-danger">*</span>
        </label>

        <!-- Patrones MULTIPLE: muestran boxes como texto (son num_id de otros patrones) -->
        <div v-if="isMultiple" class="alert alert-info py-2 px-3 text-start small">
          <i class="fa-solid fa-circle-info me-1"></i>
          Este patrón agrupa otros patrones: <strong>{{ form.boxes }}</strong>
        </div>

        <!-- Patrones UNIQUE: selector visual del cartón -->
        <BingoCardSelector
          v-else
          v-model="form.boxes"
          :free-center="true"
          :disabled="readonly"
        />

        <div v-if="!readonly && !isMultiple && errors.boxes" class="invalid-feedback d-block text-start mt-1">
          {{ errors.boxes }}
        </div>
      </div>

      <!-- Switch "Patrón por defecto": solo visible para admin -->
      <FormSwitch
        v-if="isAdmin"
        label="Patrón por defecto"
        name="default_pattern"
        col="mt-3 col-sm-6"
        :disabled="readonly"
        v-model="defaultPatternBool"
      />

    </div>
  </form>
</template>
