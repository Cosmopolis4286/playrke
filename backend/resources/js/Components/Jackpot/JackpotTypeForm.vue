<script setup lang="ts" name="JackpotTypeForm">
  import { computed } from 'vue'
  import { useJackpotTypeForm } from '@/js/composables/jackpotType/useJackpotTypeForm'
  import type { JackpotType } from '@/js/types/jackpotType'
  import type { GameModeGameOption } from '@/js/types/gameMode'
  import type { GameCurrencyOption } from '@/js/types/game'
  import FormInput from '@/js/Components/Common/Form/FormInput.vue'
  import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'
  import FormSwitch from '@/js/Components/Common/Form/FormSwitch.vue'

  const props = defineProps<{
    modelValue: Partial<JackpotType>
    games: GameModeGameOption[]
    currencies: GameCurrencyOption[]
  }>()

  const emit = defineEmits<{
    (e: 'update:modelValue', value: Partial<JackpotType>): void
  }>()

  const { form, errors, isEditMode, isValid, validate } = useJackpotTypeForm(props.modelValue, emit)

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

  const currencyOptions = computed(() =>
    props.currencies.map(c => ({ value: c.id, label: `${c.iso_code} — ${c.name}` }))
  )

  const periodicityOptions = [
    { value: 'PER_ROOM', label: 'Por sala (no compartido)' },
    { value: 'WEEKLY', label: 'Semanal' },
    { value: 'BIWEEKLY', label: 'Quincenal' },
    { value: 'MONTHLY', label: 'Mensual' }
  ]

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

      <!-- Moneda -->
      <FormSelect
        label="Moneda"
        name="currency_id"
        col="col-md-6"
        required
        :disabled="isEditMode"
        placeholder="Selecciona una moneda"
        :options="currencyOptions"
        optionValue="value"
        optionLabel="label"
        :error="errors.currency_id"
        :modelValue="form.currency_id ?? null"
        @update:modelValue="form.currency_id = $event as string"
      >
        <template #hint>Un jackpot semanal en USD y otro en Bs son pozos distintos</template>
      </FormSelect>

      <!-- Nombre -->
      <FormInput
        name="label"
        label="Nombre"
        col="col-md-6"
        :required="true"
        placeholder="Ej: Jackpot Semanal USD"
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
        placeholder="Ej: WEEKLY_USD"
        :maxlength="30"
        :error="errors.code"
        :modelValue="form.code ?? ''"
        @update:modelValue="handleCode($event)"
        @blur="validate"
      >
        <template #hint>2-30 caracteres: letras, números y guion bajo</template>
      </FormInput>

      <!-- Periodicidad -->
      <FormSelect
        label="Periodicidad"
        name="periodicity"
        col="col-md-6"
        placeholder="Selecciona periodicidad"
        :options="periodicityOptions"
        optionValue="value"
        optionLabel="label"
        :error="errors.periodicity"
        :modelValue="form.periodicity ?? 'PER_ROOM'"
        @update:modelValue="form.periodicity = $event as JackpotType['periodicity']"
      >
        <template #hint>PER_ROOM se acumula y aplica dentro de una sola sala; las demás son pozos compartidos entre salas</template>
      </FormSelect>

      <!-- Activo -->
      <FormSwitch
        name="jackpot-type-status"
        label="Jackpot activo"
        col="col-12"
        :modelValue="form.status === 'ACTIVE'"
        @update:modelValue="form.status = $event ? 'ACTIVE' : 'INACTIVE'"
      />
    </div>
  </form>
</template>
