<script setup lang="ts" name="CommerceForm">
  import { computed, reactive } from 'vue'
  // Components
  import FormInput from '@/js/Components/Common/Form/FormInput.vue'
  import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'
  // Composables
  import { useAdminCommerceForm } from '@/js/composables/roles/admin/commerce/useAdminCommerceForm'
  // Types
  import type { Currency } from '@/js/types/currency'
  import type { ExchangeRate } from '@/js/types/exchange-rate'

  /**
   * Props
   */
  const props = defineProps<{
    currencies: Currency[]
    modelValue: Partial<ExchangeRate> // Cambia a Partial<Commerce> si aplica
  }>()

  /**
   * Emits
   */
  const emit = defineEmits<{
    (e: 'submit'): void
    (e: 'update:modelValue', value: any): void
  }>()

  // Composable o estado reactivo para formulario
  // Si usas useAdminCommerceForm, úsalo; sino, crea estado local
  const { form, errors, maskOptions, isEditMode, isValid, validate } = useAdminCommerceForm
    ? useAdminCommerceForm(props.modelValue, emit)
    : {
        form: reactive({
          name: '',
          username: '',
          email: '',
          country: null as number | null,
          time_zone: null as number | null,
          phone: '',
          currency: null as number | null,
          other_currencies: [] as number[],
          allowed_games: [] as number[]
        }),
        errors: {},
        maskOptions: {},
        isEditMode: computed(() => false),
        isValid: computed(() => true),
        validate: () => {}
      }

  // Opciones ejemplo para selects (deberían venir de props o API)
  const countries = reactive([
    { id: 1, name: 'Argentina' },
    { id: 2, name: 'Brasil' }
  ])

  const timeZones = reactive([
    { id: 1, label: 'UTC -3' },
    { id: 2, label: 'UTC -4' }
  ])

  const currencies = reactive([
    { id: 1, name: 'USD' },
    { id: 2, name: 'EUR' }
  ])

  const games = reactive([
    { id: 1, name: 'Poker' },
    { id: 2, name: 'Blackjack' }
  ])

  /**
   * Submit interno del formulario
   */
  const submit = (): boolean => {
    validate()
    if (!isValid.value) return false

    emit('submit')
    return true
  }

  /**
   * Exponer API pública
   */
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
        name="country"
        required
        placeholder="Seleccionar país"
        :options="countries"
        optionValue="id"
        optionLabel="name"
        col="col-sm-6"
        v-model="form.country"
      />

      <FormSelect
        container
        label="Zona Horaria"
        id="time-zone"
        name="time_zone"
        required
        placeholder="Seleccionar zona horaria"
        col="col-sm-6"
        class="d-none"
        :options="timeZones"
        optionValue="id"
        optionLabel="label"
        v-model="form.time_zone"
      />

      <FormInput
        label="Teléfono"
        name="phone"
        required
        placeholder="Ingresa el número"
        maxlength="20"
        col="col-sm-6"
        v-model="form.phone"
      />

      <FormSelect
        container
        label="Moneda Principal"
        id="currency"
        name="currency"
        required
        placeholder="Seleccionar moneda principal"
        col="col-sm-6"
        :options="availableCurrencies"
        optionValue="id"
        optionLabel="name"
        v-model="form.currency"
      />

      <FormSelect
        container
        label="Otras Monedas"
        id="other-currencies"
        name="other_currencies"
        multiple
        select2
        placeholder="Seleccionar otras monedas"
        col="col-sm-6"
        class="d-none"
        :options="currencies"
        optionValue="id"
        optionLabel="name"
        v-model="form.other_currencies"
      />

      <FormSelect
        label="Juegos Permitidos"
        id="allowed-games"
        name="allowed_games"
        multiple
        select2
        placeholder="Seleccionar juegos"
        col="col-sm-6"
        :options="games"
        optionValue="id"
        optionLabel="name"
        v-model="form.allowed_games"
      />
    </div>
  </form>
</template>
