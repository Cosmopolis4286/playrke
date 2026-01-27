<script setup lang="ts" name="AdminCommerceForm">
  import { computed } from 'vue'

  // Components
  import FormInput from '@/js/Components/Common/Form/FormInput.vue'
  import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'

  // Composables
  import { useAdminCommerceForm } from '@/js/composables/roles/admin/commerce/useAdminCommerceForm'

  // Types
  import type { Currency } from '@/js/types/currency'
  import type { Commerce } from '@/js/types/roles/commerce/commerce'

  /* ==========================================================================
   * Props
   * ========================================================================== */

  const props = defineProps<{
    currencies: Currency[]
    modelValue: Partial<Commerce>
  }>()

  /* ==========================================================================
   * Emits
   * ========================================================================== */

  const emit = defineEmits<{
    (e: 'submit'): void
    (e: 'update:modelValue', value: Partial<Commerce>): void
  }>()

  /* ==========================================================================
   * Form logic (PRO: computed v-model)
   * ========================================================================== */

  const { form, errors, isEditMode, isValid } = useAdminCommerceForm(props.modelValue, emit)

  /* ==========================================================================
   * Mock options (luego vienen por props o API)
   * ========================================================================== */

  const countries = [
    { id: 1, name: 'Argentina' },
    { id: 2, name: 'Brasil' }
  ]

  const timeZones = [
    { id: 1, label: 'UTC -3' },
    { id: 2, label: 'UTC -4' }
  ]

  const games = [
    { id: 1, name: 'Poker' },
    { id: 2, name: 'Blackjack' }
  ]

  /* ==========================================================================
   * Submit
   * ========================================================================== */

  const submit = (): boolean => {
    if (!isValid.value) return false
    emit('submit')
    return true
  }

  /* ==========================================================================
   * Expose
   * ========================================================================== */

  defineExpose({
    submit,
    isValid
  })
</script>
<template>
  <form id="commerce-form" novalidate @submit.prevent="submit">
    <div class="row">
      <FormInput label="Nombre Comercio" name="name" required col="col-sm-6" v-model="form.name" />

      <FormInput
        label="Username"
        name="username"
        required
        minlength="5"
        maxlength="20"
        col="col-sm-6"
        v-model="form.username"
      />

      <FormInput
        label="Email"
        name="email"
        type="email"
        required
        col="col-sm-12"
        v-model="form.email"
      />

      <FormSelect
        label="País"
        name="country_id"
        required
        placeholder="Seleccionar país"
        :options="countries"

        optionValue="id"
        optionLabel="name"
        col="col-sm-6"
        v-model="form.country_id"
      />

      <FormSelect
        label="Zona Horaria"
        name="time_zone_id"
        placeholder="Seleccionar zona horaria"
        :options="timeZones"
        optionValue="id"
        optionLabel="label"
        col="col-sm-6"
        v-model="form.time_zone_id"
      />

      <FormInput
        label="Teléfono"
        name="phone"
        placeholder="Ingresa el número"
        maxlength="20"
        col="col-sm-6"
        v-model="form.phone"
      />

      <FormSelect
        label="Moneda Principal"
        name="currency_id"
        required
        placeholder="Seleccionar moneda principal"
        :options="currencies"
        optionValue="id"
        optionLabel="name"
        col="col-sm-6"
        v-model="form.currency_id"
      />

      <FormSelect
        label="Otras Monedas"
        name="other_currencies"
        multiple
        select2
        placeholder="Seleccionar otras monedas"
        :options="currencies"
        optionValue="id"
        optionLabel="name"
        col="col-sm-6"
        v-model="form.other_currencies"
      />

      <FormSelect
        label="Juegos Permitidos"
        name="allowed_games"
        multiple
        select2
        placeholder="Seleccionar juegos"
        :options="games"
        optionValue="id"
        optionLabel="name"
        col="col-sm-6"
        v-model="form.allowed_games"
      />
    </div>
  </form>
</template>
