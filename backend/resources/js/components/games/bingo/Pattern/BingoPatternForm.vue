<script setup lang="ts" name="BingoPatternForm">
  import { computed } from 'vue'

  import FormInput         from '@/js/Components/Common/Form/FormInput.vue'
  import FormSelect        from '@/js/Components/Common/Form/FormSelect.vue'
  import FormSwitch        from '@/js/Components/Common/Form/FormSwitch.vue'
  import BingoCardSelector from '@/js/Components/Games/Bingo/Pattern/BingoCardSelector.vue'

  import type { BingoPattern } from '@/js/types/games/bingo/pattern/bingo-pattern'

  /* ==========================================================================
   * Props / Emits
   * ========================================================================== */

  const props = defineProps<{
    modelValue: Partial<BingoPattern>
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

  /* ==========================================================================
   * Validación reactiva
   * ========================================================================== */

  const errors = computed<Record<string, string>>(() => {
    const e: Record<string, string> = {}

    if (!form.value.name?.trim()) {
      e.name = 'El nombre es requerido.'
    } else if (form.value.name.length > 30) {
      e.name = 'Máx. 30 caracteres.'
    }

    if (!form.value.type_pattern) {
      e.type_pattern = 'El tipo de patrón es requerido.'
    }

    if (!form.value.boxes?.trim()) {
      e.boxes = 'Las casillas son requeridas.'
    } else {
      const parts = form.value.boxes.split(';').filter(b => b.trim())
      const invalid = parts.find(b => {
        const n = parseInt(b.trim(), 10)
        return isNaN(n) || n < 1 || n > 75
      })
      if (invalid) {
        e.boxes = `Casilla inválida: "${invalid.trim()}". Rango 1-75.`
      }
    }

    return e
  })

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
  <form id="bingo-pattern-form" novalidate @submit.prevent="submit">
    <div class="row">

      <FormInput
        label="Nombre del patrón"
        name="name"
        required
        maxlength="30"
        placeholder="Ej: Línea horizontal"
        col="col-sm-8"
        :error="errors.name"
        v-model="form.name"
      />

      <FormSelect
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

      <div class="col-sm-12 mb-3 text-center">
        <label class="form-label d-block">Casillas del patrón <span class="text-danger">*</span></label>
        <BingoCardSelector
          v-model="form.boxes"
          :free-center="true"
        />
        <div v-if="errors.boxes" class="invalid-feedback d-block text-start mt-1">{{ errors.boxes }}</div>
      </div>

      <FormSwitch
        label="Patrón por defecto"
        name="default_pattern"
        col="col-sm-6"
        v-model="defaultPatternBool"
      />

    </div>
  </form>
</template>
