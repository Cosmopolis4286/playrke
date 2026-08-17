<script setup lang="ts" name="AdministratorEditForm">
  import { computed, ref, watch } from 'vue'

  // Components
  import FormInput from '@/js/Components/Common/Form/FormInput.vue'
  import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'

  // Composables
  import { useAdministratorEditForm } from '@/js/composables/roles/admin/administrator/useAdministratorEditForm'
  import { usePhoneField } from '@/js/composables/form/usePhoneField'

  // Types
  import type { Country } from '@/js/types/country'
  import type { AdministratorEditData } from '@/js/types/roles/admin/administrator'

  const props = defineProps<{
    modelValue: Partial<AdministratorEditData>
    countries: Country[]
    /** true si el administrador editado es el propio actor autenticado — el status nunca es alterable sobre uno mismo. */
    isSelf: boolean
    /** true mientras se procesa el toggle de status (deshabilita el switch). */
    togglingStatus?: boolean
  }>()

  const emit = defineEmits<{
    (e: 'submit'): void
    (e: 'update:modelValue', value: Partial<AdministratorEditData>): void
    (e: 'reset-password'): void
    (e: 'toggle-status'): void
  }>()

  const { form, errors, isValid: isFormDataValid } = useAdministratorEditForm(() => props.modelValue, emit)

  const selectedIso2 = computed<string | null>(() => {
    if (!form.value.country_id) return null
    return props.countries.find(c => c.id === form.value.country_id)?.iso_code_2 ?? null
  })

  const phoneRef = ref<string>(form.value.phone_number ?? '')

  watch(() => form.value.phone_number, v => {
    if (v !== phoneRef.value) phoneRef.value = v ?? ''
  })

  watch(phoneRef, v => {
    if (v !== form.value.phone_number) {
      form.value = { ...form.value, phone_number: v }
    }
  })

  const { phoneMask, phonePlaceholder, phoneHint, phoneError, isPhoneValid } =
    usePhoneField(selectedIso2, phoneRef, v => {
      phoneRef.value = v
    })

  const phoneErrorDisplay = computed(() => phoneError.value ?? errors.value.phone_number ?? null)
  const phoneValidState = computed<'valid' | 'invalid' | null>(() => {
    if (!phoneRef.value?.trim()) return null
    return isPhoneValid.value ? 'valid' : 'invalid'
  })
  const isPhoneFieldValid = computed(() => !phoneRef.value?.trim() || isPhoneValid.value)

  const isValid = computed(() => isFormDataValid.value && isPhoneFieldValid.value)

  const submit = (): boolean => {
    if (!isValid.value) return false
    emit('submit')
    return true
  }

  defineExpose({ submit, isValid })
</script>

<template>
  <form id="administrator-edit-form" novalidate @submit.prevent="submit">
    <div class="administrator-status-row">
      <div>
        <span class="administrator-status-label">Estado de la cuenta</span>
        <p v-if="isSelf" class="administrator-status-hint">
          <i class="fas fa-lock mr-1"></i>No puedes cambiar el estado de tu propia cuenta.
        </p>
      </div>
      <label class="administrator-status-switch" :class="{ 'is-busy': togglingStatus, 'is-disabled': isSelf }">
        <input
          type="checkbox"
          :checked="modelValue.status === 'ACTIVE'"
          :disabled="isSelf || togglingStatus"
          @change="emit('toggle-status')"
        />
        <span class="administrator-status-track"><span class="administrator-status-thumb"></span></span>
        <span class="administrator-status-text">{{ modelValue.status === 'ACTIVE' ? 'Activo' : 'Inactivo' }}</span>
      </label>
    </div>

    <div class="row">
      <FormInput
        label="Nombre"
        name="first_name"
        required
        col="col-sm-6"
        :error="errors.first_name"
        v-model="form.first_name"
      />

      <FormInput
        label="Apellido"
        name="last_name"
        required
        col="col-sm-6"
        :error="errors.last_name"
        v-model="form.last_name"
      />

      <FormInput
        label="Username"
        name="username"
        required
        minlength="3"
        maxlength="20"
        col="col-sm-6"
        :error="errors.username"
        v-model="form.username"
      />

      <FormInput
        label="Email"
        name="email"
        type="email"
        required
        col="col-sm-6"
        :error="errors.email"
        v-model="form.email"
      />

      <FormInput
        label="Teléfono"
        name="phone_number"
        col="col-sm-6"
        :placeholder="phonePlaceholder"
        :mask="phoneMask"
        :maskEmitFormatted="true"
        :disabled="!form.country_id"
        :inputValidState="phoneValidState"
        :error="phoneErrorDisplay"
        :hint="
          !phoneErrorDisplay && form.country_id
            ? phoneHint
            : !form.country_id
              ? 'Selecciona un país primero'
              : undefined
        "
        v-model="phoneRef"
      >
        <template #prepend>
          <span class="input-group-text px-2">
            <i class="fa-solid fa-phone fa-fw text-muted"></i>
          </span>
        </template>
      </FormInput>

      <FormSelect
        label="País"
        name="country_id"
        required
        placeholder="Seleccionar país"
        :options="props.countries"
        optionValue="id"
        optionLabel="name"
        col="col-sm-6"
        :error="errors.country_id"
        v-model="form.country_id"
      />
    </div>

    <div class="administrator-edit-form-actions mt-2">
      <button type="button" class="btn btn-outline-dark btn-sm" :disabled="isSelf" @click="emit('reset-password')">
        <i class="fas fa-key mr-2"></i>
        Resetear contraseña
      </button>
      <small v-if="isSelf" class="text-muted d-block reset-locked-hint">
        <i class="fas fa-lock mr-1"></i>No puedes resetear tu propia contraseña desde aquí.
      </small>
    </div>
  </form>
</template>

<style scoped>
.administrator-status-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.75rem 0.9rem;
  margin-bottom: 1.25rem;
  border: 1px solid var(--ds-border);
  border-radius: 10px;
  background: var(--ds-surface-2);
}

.administrator-status-label {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--ds-text);
}
.administrator-status-hint {
  margin: 0.2rem 0 0;
  font-size: 0.72rem;
  color: var(--ds-text-muted);
}

.administrator-status-switch {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  user-select: none;
  flex-shrink: 0;
}
.administrator-status-switch.is-busy,
.administrator-status-switch.is-disabled { opacity: 0.6; cursor: not-allowed; }
.administrator-status-switch input { position: absolute; opacity: 0; width: 0; height: 0; }

.administrator-status-track {
  position: relative;
  width: 34px;
  height: 20px;
  border-radius: 999px;
  background: var(--ds-border-2);
  flex-shrink: 0;
  transition: background 0.15s;
}
.administrator-status-thumb {
  position: absolute;
  top: 2px;
  left: 2px;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: #fff;
  box-shadow: 0 1px 2px rgba(0,0,0,0.25);
  transition: transform 0.15s;
}
.administrator-status-switch input:checked + .administrator-status-track { background: #34d399; }
.administrator-status-switch input:checked + .administrator-status-track .administrator-status-thumb { transform: translateX(14px); }

.administrator-status-text { font-size: 0.8rem; font-weight: 600; color: var(--ds-text-2); min-width: 3.2rem; }

.btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 9px 18px;
  border-radius: 10px;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
  border: none;
  transition: all 0.15s;
}

.btn-outline-dark {
  background: transparent;
  border: 1.5px solid var(--ds-border-2);
  color: var(--ds-text);
}
.btn-outline-dark:hover { background: var(--ds-surface-2); }
.btn:disabled { opacity: 0.55; cursor: not-allowed; }

.reset-locked-hint { margin-top: 0.35rem; font-size: 0.75rem; width: 100%; }

.administrator-edit-form-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}
</style>
