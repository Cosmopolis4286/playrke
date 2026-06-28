<script setup lang="ts" name="MovementTypeForm">
  import { useMovementTypeForm } from '@/js/composables/movementType/useMovementTypeForm'
  import type { MovementType } from '@/js/types/movementType'
  import FormInput from '@/js/Components/Common/Form/FormInput.vue'
  import FormSwitch from '@/js/Components/Common/Form/FormSwitch.vue'

  /**
   * Props
   */
  const props = defineProps<{
    modelValue: Partial<MovementType>
  }>()

  /**
   * Emits
   */
  const emit = defineEmits<{
    (e: 'update:modelValue', value: Partial<MovementType>): void
  }>()

  /**
   * Composable de formulario
   */
  const { form, errors, isCodeLocked, isValid, validate } = useMovementTypeForm(props.modelValue, emit)

  /**
   * Formatea el code en mayúsculas al escribir
   */
  function handleCode(value: string) {
    form.value.code = value.toUpperCase().replace(/[^A-Z_]/g, '').slice(0, 30)
  }

  /**
   * API pública para el modal
   */
  defineExpose({ isValid })
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
        placeholder="Ej: Bono de bienvenida"
        :maxlength="100"
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
        :required="!isCodeLocked"
        :disabled="isCodeLocked"
        placeholder="Ej: BONUS"
        :maxlength="30"
        :error="errors.code"
        :modelValue="form.code ?? ''"
        @update:modelValue="handleCode($event)"
        @blur="validate"
      >
        <template #hint>
          {{ isCodeLocked ? 'No se puede modificar: es un tipo del sistema o tiene movimientos asociados' : '2-30 caracteres en mayúsculas y guion bajo' }}
        </template>
      </FormInput>

      <!-- Icono -->
      <FormInput
        name="icon"
        label="Icono"
        col="col-md-6"
        placeholder="Ej: fas fa-gift"
        :maxlength="60"
        :modelValue="form.icon ?? ''"
        @update:modelValue="form.icon = $event || 'fas fa-circle'"
      >
        <template #prepend>
          <span class="input-group-text"><i :class="form.icon || 'fas fa-circle'"></i></span>
        </template>
        <template #hint>Clase FontAwesome (fas fa-...)</template>
      </FormInput>

      <!-- Color -->
      <FormInput
        name="color"
        label="Color"
        col="col-md-6"
        type="color"
        :error="errors.color"
        :modelValue="form.color ?? '#718096'"
        @update:modelValue="form.color = $event"
      >
        <template #hint>Color usado para distinguir el tipo en la UI</template>
      </FormInput>

      <!-- Descripción -->
      <FormInput
        name="description"
        label="Descripción"
        col="col-12"
        placeholder="Descripción visible para el usuario"
        :maxlength="255"
        :modelValue="form.description ?? ''"
        @update:modelValue="form.description = $event || null"
      />

      <!-- Suma saldo -->
      <FormSwitch
        name="is_credit"
        label="Suma saldo a la billetera (crédito)"
        col="col-md-6"
        :modelValue="form.is_credit ?? true"
        @update:modelValue="form.is_credit = $event"
      />

      <!-- Requiere aprobación -->
      <FormSwitch
        name="requires_approval"
        label="Requiere aprobación de admin"
        col="col-md-6"
        :modelValue="form.requires_approval ?? false"
        @update:modelValue="form.requires_approval = $event"
      />

      <!-- Hint de convención de timing -->
      <div v-if="form.requires_approval" class="col-12">
        <small class="text-muted d-block mb-2">
          <i class="fa-solid fa-circle-info fa-fw"></i>
          Convención: si <b>suma saldo</b>, el movimiento se crea sin tocar el saldo y se acredita al aprobar (igual que Depósito).
          Si <b>no suma saldo</b>, el movimiento descuenta el saldo de inmediato al crearse, y se reintegra si se rechaza (igual que Retiro).
        </small>
      </div>

      <!-- Activo -->
      <FormSwitch
        name="movement-type-status"
        label="Tipo de movimiento activo"
        col="col-12"
        :modelValue="form.status === 'ACTIVE'"
        @update:modelValue="form.status = $event ? 'ACTIVE' : 'INACTIVE'"
      />
    </div>
  </form>
</template>
