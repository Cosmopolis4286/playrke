<script setup lang="ts" name="PrizePoolTypeForm">
  import { computed } from 'vue'
  import { usePrizePoolTypeForm } from '@/js/composables/prizePoolType/usePrizePoolTypeForm'
  import type { PrizePoolType } from '@/js/types/prizePoolType'
  import type { GameModeGameOption } from '@/js/types/gameMode'
  import FormInput from '@/js/Components/Common/Form/FormInput.vue'
  import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'
  import FormSwitch from '@/js/Components/Common/Form/FormSwitch.vue'

  const props = defineProps<{
    modelValue: Partial<PrizePoolType>
    games: GameModeGameOption[]
  }>()

  const emit = defineEmits<{
    (e: 'update:modelValue', value: Partial<PrizePoolType>): void
  }>()

  const { form, errors, isEditMode, isValid, validate } = usePrizePoolTypeForm(props.modelValue, emit)

  const GAME_STATUS_SUFFIX: Record<GameModeGameOption['status'], string> = {
    ACTIVE: '',
    INACTIVE: ' (Inactivo)',
    MAINTENANCE: ' (Mantenimiento)'
  }

  const gameOptions = computed(() =>
    props.games.map(g => ({
      value: g.id,
      label: `${g.name}${GAME_STATUS_SUFFIX[g.status] ?? ''}`
    }))
  )

  function handleCode(value: string) {
    form.value.code = value.toUpperCase().replace(/[^A-Z0-9_]/g, '').slice(0, 30)
  }

  function submit(): boolean {
    validate()
    return isValid.value
  }

  defineExpose({ submit, isValid })
</script>

<template>
  <form @submit.prevent novalidate>
    <div class="row">
      <!-- Juego -->
      <FormSelect
        label="Juego"
        name="game_id"
        col="col-md-6"
        required
        :disabled="isEditMode"
        placeholder="Selecciona un juego"
        :options="gameOptions"
        optionValue="value"
        optionLabel="label"
        :error="errors.game_id"
        :modelValue="form.game_id ?? null"
        @update:modelValue="form.game_id = $event as string"
      />

      <!-- Nombre -->
      <FormInput
        name="label"
        label="Nombre"
        col="col-md-6"
        :required="true"
        placeholder="Ej: Bote Fijo"
        :maxlength="50"
        :error="errors.label"
        :modelValue="form.label"
        @update:modelValue="form.label = $event"
        @blur="validate"
      />

      <!-- Código -->
      <FormInput
        name="code"
        label="Código"
        col="col-md-6"
        :required="true"
        placeholder="Ej: FIXED"
        :maxlength="30"
        :error="errors.code"
        :modelValue="form.code ?? ''"
        @update:modelValue="handleCode($event)"
        @blur="validate"
      >
        <template #hint>2-30 caracteres: letras, números y guion bajo</template>
      </FormInput>

      <!-- Activo -->
      <FormSwitch
        name="prize-pool-type-status"
        label="Tipo de bote activo"
        col="col-12"
        :modelValue="form.status === 'ACTIVE'"
        @update:modelValue="form.status = $event ? 'ACTIVE' : 'INACTIVE'"
      />
    </div>
  </form>
</template>
