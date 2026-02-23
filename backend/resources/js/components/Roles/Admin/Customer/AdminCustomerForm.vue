<script setup lang="ts" name="AdminCustomerForm">
  import { computed, watch, nextTick } from 'vue'

  // Components
  import FormInput from '@/js/Components/Common/Form/FormInput.vue'
  import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'

  // Composables
  import { useAdminCustomerForm } from '@/js/composables/roles/admin/customer/useAdminCustomerForm'

  // Types
  import type { Currency } from '@/js/types/currency'
  import type { Country } from '@/js/types/country'
  import type { TimeZone } from '@/js/types/time-zone'
  import type { Game } from '@/js/types/game'
  import type { Customer } from '@/js/types/roles/customer/customer'

  /* ==========================================================================
   * Props
   * ========================================================================== */

  const props = defineProps<{
    countries: Country[]
    currencies: Currency[]
    timeZones: TimeZone[]
    games: Game[]
    modelValue: Partial<Customer>
  }>()

  /* ==========================================================================
   * Emits
   * ========================================================================== */

  const emit = defineEmits<{
    (e: 'submit'): void
    (e: 'update:modelValue', value: Partial<Customer>): void
  }>()

  /* ==========================================================================
   * Form logic (PRO: computed v-model)
   * ========================================================================== */

  const { form, errors, isValid } = useAdminCustomerForm(() => props.modelValue, emit)

  /* ==========================================================================
   * Máscaras
   * ========================================================================== */

  const commissionMask = {
    mask: Number,
    scale: 2,
    min: 0,
    max: 100,
    radix: '.',
    mapToRadix: [','],
    padFractionalZeros: true,
    normalizeZeros: true,
    thousandsSeparator: ''
  }

  /* ==========================================================================
   * Filtered currencies by country
   * ========================================================================== */

  const filteredCurrencies = computed(() => {
    if (!form.value.country_id) return []
    // eslint-disable-next-line eqeqeq
    return props.currencies.filter(c => c.country_id == form.value.country_id)
  })

  const filteredTimeZones = computed(() => {
    if (!form.value.country_id) return []
    // eslint-disable-next-line eqeqeq
    return props.timeZones.filter(tz => tz.country_id == form.value.country_id)
  })

  // Computed para saber si hay una sola opción (no mostrar placeholder)
  const hasSingleCurrency = computed(() => filteredCurrencies.value.length === 1)
  const hasSingleTimeZone = computed(() => filteredTimeZones.value.length === 1)

  // Otras monedas: todas las monedas excepto la principal seleccionada
  const otherCurrenciesOptions = computed(() => {
    if (!form.value.currency_id) return props.currencies
    // eslint-disable-next-line eqeqeq
    return props.currencies.filter(c => c.id != form.value.currency_id)
  })

  // Auto-select currency and timezone when country changes
  watch(
    () => form.value.country_id,
    async (newCountryId, oldCountryId) => {
      // Solo resetear si cambió el país (no en la carga inicial con valor)
      if (oldCountryId !== undefined) {
        form.value = { ...form.value, currency_id: null, time_zone_id: null, other_currencies: [] }
      }

      if (!newCountryId) return

      // Wait for DOM to update (v-if to show selects)
      await nextTick()

      // eslint-disable-next-line eqeqeq
      const currencies = props.currencies.filter(c => c.country_id == newCountryId)
      // eslint-disable-next-line eqeqeq
      const timeZones = props.timeZones.filter(tz => tz.country_id == newCountryId)

      // Auto-select si hay una sola opción
      form.value = {
        ...form.value,
        currency_id: currencies.length === 1 ? currencies[0].id : form.value.currency_id,
        time_zone_id: timeZones.length === 1 ? timeZones[0].id : form.value.time_zone_id
      }
    },
    { immediate: true }
  )

  // Limpiar otras monedas cuando cambia la moneda principal
  watch(
    () => form.value.currency_id,
    (newCurrencyId, oldCurrencyId) => {
      if (oldCurrencyId !== undefined && newCurrencyId !== oldCurrencyId) {
        form.value = { ...form.value, other_currencies: [] }
      }
    }
  )

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
      <FormInput label="Razon Social" name="name" required col="col-sm-6" v-model="form.name" />

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
        col="col-md-8"
        v-model="form.email"
      />

      <FormInput
        label="Comisión %"
        name="commission"
        type="text"
        required
        col="col-md-4"
        placeholder="0.00"
        :mask="commissionMask"
        v-model="form.commission"
      />

      <FormInput label="Ruta API" name="api_route" required col="col-12" v-model="form.api_route" />

      <FormInput
        label="Llave de Conexión"
        name="customer_key"
        :error="errors.customer_key"
        required
        col="col-12"
        inputGroup
        v-model="form.customer_key"
      >
        <template #append>
          <button class="btn btn-outline-secondary" type="button" id="btn-change-customer-key">
            <i class="fa-solid fa-rotate"></i>
          </button>
        </template>
      </FormInput>

      <FormInput
        label="Logo"
        type="file"
        name="logo"
        accept="image/png, image/jpeg"
        col="col-12"
        v-model="form.logo"
      />

      <FormSelect
        label="País"
        name="country_id"
        required
        placeholder="Seleccionar país"
        :options="props.countries"
        optionValue="id"
        optionLabel="name"
        col="col-sm-6"
        v-model="form.country_id"
      />

      <FormSelect
        v-if="form.country_id"
        label="Zona Horaria"
        name="time_zone_id"
        required
        :placeholder="hasSingleTimeZone ? undefined : 'Seleccionar zona horaria'"
        :options="filteredTimeZones"
        optionValue="id"
        optionLabel="name"
        col="col-sm-6"
        v-model="form.time_zone_id"
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
        v-if="form.country_id"
        label="Moneda Principal"
        name="currency_id"
        required
        :placeholder="hasSingleCurrency ? undefined : 'Seleccionar moneda principal'"
        :options="filteredCurrencies"
        optionValue="id"
        optionLabel="name"
        col="col-sm-6"
        v-model="form.currency_id"
      />

      <FormSelect
        v-if="form.currency_id"
        label="Otras Monedas"
        name="other_currencies"
        multiple
        select2
        placeholder="Seleccionar otras monedas"
        :options="otherCurrenciesOptions"
        optionValue="id"
        optionLabel="name"
        col="col-sm-6"
        v-model="form.other_currencies"
      />

      <FormSelect
        label="Juegos Permitidos"
        name="allowed_games"
        required
        multiple
        select2
        placeholder="Seleccionar juegos"
        :options="props.games"
        optionValue="id"
        optionLabel="name"
        col="col-sm-6"
        v-model="form.allowed_games"
      />
    </div>
  </form>
</template>
