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

  /** Roles disponibles para restringir visibilidad (prefix de Role) */
  const AVAILABLE_ROLES = [
    { value: 'superadmin', label: 'SuperAdmin' },
    { value: 'admin',      label: 'Admin' },
    { value: 'commerce',   label: 'Comercio' },
    { value: 'subsidiary', label: 'Sucursal' },
    { value: 'seller',     label: 'Vendedor' },
    { value: 'provider',   label: 'Proveedor' },
    { value: 'customer',   label: 'Cliente' },
    { value: 'treasurer',  label: 'Tesorero' },
  ]

  function isRoleChecked(role: string): boolean {
    return !!form.value.visible_roles?.includes(role)
  }

  function toggleRole(role: string, checked: boolean) {
    const current = form.value.visible_roles ?? []
    const next = checked ? [...current, role] : current.filter(r => r !== role)
    form.value.visible_roles = next.length ? next : null
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

      <!-- Grupo de juego -->
      <FormInput
        name="game_group"
        label="Grupo de juego"
        col="col-md-6"
        placeholder="Ej: BINGO, TRIVIA (vacío = genérico)"
        :maxlength="40"
        :modelValue="form.game_group ?? ''"
        @update:modelValue="form.game_group = $event ? $event.toUpperCase() : null"
      >
        <template #hint>Agrupa visualmente este tipo bajo un juego en la billetera</template>
      </FormInput>

      <!-- Orden de presentación -->
      <FormInput
        name="sort_order"
        label="Orden"
        type="number"
        col="col-md-6"
        min="0"
        :modelValue="form.sort_order ?? 0"
        @update:modelValue="form.sort_order = Number($event) || 0"
      >
        <template #hint>Menor valor aparece primero en los listados</template>
      </FormInput>

      <!-- Visibilidad por rol -->
      <div class="col-12">
        <label class="form-label d-block">Visible para roles</label>
        <small class="text-muted d-block mb-2">Sin selección = visible para todos los roles</small>
        <div class="d-flex flex-wrap gap-3">
          <div v-for="role in AVAILABLE_ROLES" :key="role.value" class="custom-control custom-checkbox">
            <input
              :id="`visible-role-${role.value}`"
              type="checkbox"
              class="custom-control-input"
              :checked="isRoleChecked(role.value)"
              @change="toggleRole(role.value, ($event.target as HTMLInputElement).checked)"
            />
            <label class="custom-control-label" :for="`visible-role-${role.value}`">
              {{ role.label }}
            </label>
          </div>
        </div>
      </div>

      <!-- Suma saldo (bloqueado en tipos de sistema: cambiarlo invertiría
           el signo de todo el historial de ese tipo) -->
      <FormSwitch
        name="is_credit"
        :label="isCodeLocked ? 'Suma saldo a la billetera (crédito) — bloqueado en tipos de sistema' : 'Suma saldo a la billetera (crédito)'"
        col="col-md-6"
        :modelValue="form.is_credit ?? true"
        :disabled="isCodeLocked"
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
