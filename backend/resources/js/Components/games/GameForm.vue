<script setup lang="ts" name="GameForm">
  import { useGameForm } from '@/js/composables/games/useGameForm'
  import type { Game } from '@/js/types/game'
  import FormInput  from '@/js/Components/Common/Form/FormInput.vue'
  import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'

  const props = defineProps<{
    modelValue: Partial<Game>
  }>()

  const emit = defineEmits<{
    (e: 'update:modelValue', value: Partial<Game>): void
  }>()

  const { form, errors, isValid, validate } = useGameForm(props.modelValue, emit)

  const statusOptions = [
    { value: 'ACTIVE',      label: 'Activo' },
    { value: 'INACTIVE',    label: 'Inactivo' },
    { value: 'MAINTENANCE', label: 'Mantenimiento' },
  ]

  function submit(): boolean {
    validate()
    return isValid.value
  }

  defineExpose({ submit, isValid })
</script>

<template>
  <form @submit.prevent novalidate>
    <div class="row">
      <!-- Nombre -->
      <FormInput
        name="name"
        label="Nombre del juego"
        col="col-md-8"
        :required="true"
        placeholder="Ej: BINGO"
        :maxlength="50"
        :error="errors.name"
        :modelValue="form.name"
        @update:modelValue="form.name = ($event as string).toUpperCase()"
        @blur="validate"
      >
        <template #hint>En mayúsculas, máximo 50 caracteres</template>
      </FormInput>

      <!-- Estado -->
      <FormSelect
        name="status"
        label="Estado"
        col="col-md-4"
        :required="true"
        :options="statusOptions"
        option-value="value"
        option-label="label"
        placeholder="Seleccionar estado"
        :error="errors.status"
        :modelValue="form.status"
        @update:modelValue="form.status = $event"
      />
    </div>
  </form>
</template>
