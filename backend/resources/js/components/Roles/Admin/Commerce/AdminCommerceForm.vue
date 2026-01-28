<script setup lang="ts" name="AdminCommerceForm">
  import { computed, watch, nextTick } from 'vue'

  // Components
  import FormInput from '@/js/Components/Common/Form/FormInput.vue'
  import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'

  // Composables
  import { useAdminCommerceForm } from '@/js/composables/roles/admin/commerce/useAdminCommerceForm'

  // Types
  import type { Currency } from '@/js/types/currency'
  import type { Country } from '@/js/types/country'
  import type { TimeZone } from '@/js/types/time-zone'
  import type { Game } from '@/js/types/game'
  import type { Commerce } from '@/js/types/roles/commerce/commerce'

  /* ==========================================================================
   * Props
   * ========================================================================== */

  const props = defineProps<{
    countries: Country[]
    currencies: Currency[]
    timeZones: TimeZone[]
    games: Game[]
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

  // Auto-select or reset currency and timezone when country changes
  watch(
    () => form.value.country_id,
    (newCountryId) => {
      if (!newCountryId) {
        form.value = { ...form.value, currency_id: null, time_zone_id: null }
        return
      }

      // eslint-disable-next-line eqeqeq
      const currencies = props.currencies.filter(c => c.country_id == newCountryId)
      // eslint-disable-next-line eqeqeq
      const timeZones = props.timeZones.filter(tz => tz.country_id == newCountryId)

      form.value = {
        ...form.value,
        currency_id: currencies.length === 1 ? currencies[0].id : null,
        time_zone_id: timeZones.length === 1 ? timeZones[0].id : null
      }
    },
    { flush: 'post' }
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
        col="col-sm-12"
        v-model="form.email"
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
        placeholder="Seleccionar zona horaria"
        :options="filteredTimeZones"
        optionValue="id"
        optionLabel="name"
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
        v-if="form.country_id"
        label="Moneda Principal"
        name="currency_id"
        required
        placeholder="Seleccionar moneda principal"
        :options="filteredCurrencies"
        optionValue="id"
        optionLabel="name"
        col="col-sm-6"
        v-model="form.currency_id"
      />

      <FormSelect
        v-if="form.country_id"
        label="Otras Monedas"
        name="other_currencies"
        multiple
        select2
        placeholder="Seleccionar otras monedas"
        :options="filteredCurrencies"
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
        :options="props.games"
        optionValue="id"
        optionLabel="name"
        col="col-sm-6"
        v-model="form.allowed_games"
      />
    </div>
  </form>
</template>
