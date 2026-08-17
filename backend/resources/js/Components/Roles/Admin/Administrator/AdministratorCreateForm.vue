<script setup lang="ts" name="AdministratorCreateForm">
  import { computed, ref, watch } from 'vue'

  // Components
  import FormInput from '@/js/Components/Common/Form/FormInput.vue'
  import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'

  // Composables
  import { useAdministratorCreateForm } from '@/js/composables/roles/admin/administrator/useAdministratorCreateForm'
  import { usePhoneField } from '@/js/composables/form/usePhoneField'

  // Types
  import type { Country } from '@/js/types/country'
  import type { AdministratorCreateData } from '@/js/types/roles/admin/administrator'

  const props = defineProps<{
    modelValue: Partial<AdministratorCreateData>
    countries: Country[]
  }>()

  const emit = defineEmits<{
    (e: 'submit'): void
    (e: 'update:modelValue', value: Partial<AdministratorCreateData>): void
  }>()

  const { form, errors, isValid: isFormDataValid } = useAdministratorCreateForm(() => props.modelValue, emit)

  /* Teléfono opcional — mismo patrón que UserEditForm.vue. */
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

  const phoneErrorDisplay = computed(() => phoneError.value ?? null)
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
  <form id="administrator-create-form" novalidate @submit.prevent="submit">
    <div class="alert alert-warning small mb-3">
      <i class="fas fa-shield-alt mr-1"></i>
      Esta cuenta tendrá acceso amplio al panel de administración.
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

      <FormInput
        label="Contraseña"
        name="password"
        type="password"
        required
        col="col-sm-6"
        hint="Mínimo 8 caracteres, con letras y números."
        :error="errors.password"
        v-model="form.password"
      />

      <FormInput
        label="Confirmar contraseña"
        name="password_confirmation"
        type="password"
        required
        col="col-sm-6"
        :error="errors.password_confirmation"
        v-model="form.password_confirmation"
      />
    </div>
  </form>
</template>
