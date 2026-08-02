<script setup lang="ts" name="RoomTypeForm">
  import { useRoomTypeForm } from '@/js/composables/roomType/useRoomTypeForm'
  import type { RoomType } from '@/js/types/roomType'
  import FormInput from '@/js/Components/Common/Form/FormInput.vue'
  import FormSwitch from '@/js/Components/Common/Form/FormSwitch.vue'

  const props = defineProps<{
    modelValue: Partial<RoomType>
  }>()

  const emit = defineEmits<{
    (e: 'update:modelValue', value: Partial<RoomType>): void
  }>()

  const { form, errors, isValid, validate } = useRoomTypeForm(props.modelValue, emit)

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
      <!-- Nombre -->
      <FormInput
        name="label"
        label="Nombre"
        col="col-md-6"
        :required="true"
        placeholder="Ej: VIP"
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
        placeholder="Ej: VIP"
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
        name="room-type-status"
        label="Tipo de sala activo"
        col="col-12"
        :modelValue="form.status === 'ACTIVE'"
        @update:modelValue="form.status = $event ? 'ACTIVE' : 'INACTIVE'"
      />
    </div>
  </form>
</template>
