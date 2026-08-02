<script setup lang="ts" name="GameModeForm">
  import { computed } from 'vue'
  import { useGameModeForm } from '@/js/composables/gameMode/useGameModeForm'
  import type { GameMode, GameModeGameOption } from '@/js/types/gameMode'
  import FormInput from '@/js/Components/Common/Form/FormInput.vue'
  import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'
  import FormSwitch from '@/js/Components/Common/Form/FormSwitch.vue'

  /**
   * Props
   */
  const props = defineProps<{
    modelValue: Partial<GameMode>
    games: GameModeGameOption[]
  }>()

  /**
   * Emits
   */
  const emit = defineEmits<{
    (e: 'update:modelValue', value: Partial<GameMode>): void
  }>()

  /**
   * Composable de formulario
   */
  const { form, errors, isEditMode, isValid, validate } = useGameModeForm(props.modelValue, emit)

  const GAME_STATUS_SUFFIX: Record<GameModeGameOption['status'], string> = {
    ACTIVE: '',
    INACTIVE: ' (Inactivo)',
    MAINTENANCE: ' (Mantenimiento)'
  }

  /**
   * Opciones de juego para el select — el nombre incluye el estado cuando
   * no está activo, para que el admin sepa que está creando/editando un
   * modo de un juego que aún no está operativo (no se bloquea, solo se
   * informa: puede ser un juego en preparación previo a su lanzamiento).
   */
  const gameOptions = computed(() =>
    props.games.map(g => ({
      value: g.id,
      label: `${g.name}${GAME_STATUS_SUFFIX[g.status] ?? ''}`
    }))
  )

  /**
   * Formatea el code en mayúsculas al escribir
   */
  function handleCode(value: string) {
    form.value.code = value.toUpperCase().replace(/[^A-Z0-9_]/g, '').slice(0, 30)
  }

  /**
   * Submit
   */
  function submit(): boolean {
    validate()
    return isValid.value
  }

  /**
   * API pública para el modal
   */
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
        placeholder="Ej: Múltiple"
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
        placeholder="Ej: MULTI"
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
        name="game-mode-status"
        label="Modo de juego activo"
        col="col-12"
        :modelValue="form.status === 'ACTIVE'"
        @update:modelValue="form.status = $event ? 'ACTIVE' : 'INACTIVE'"
      />
    </div>
  </form>
</template>
