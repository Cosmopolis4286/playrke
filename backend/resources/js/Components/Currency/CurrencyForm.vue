<script setup lang="ts" name="CurrencyForm">
  import { computed } from 'vue'
  import { useCurrencyForm } from '@/js/composables/currency/useCurrencyForm'
  import type { Currency } from '@/js/types/currency'
  import type { Country } from '@/js/types/country'
  import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'
  import FormInput from '@/js/Components/Common/Form/FormInput.vue'
  import FormSwitch from '@/js/Components/Common/Form/FormSwitch.vue'

  /**
   * Props
   */
  const props = defineProps<{
    modelValue: Partial<Currency>
    currencies: Currency[]
    countries: Country[]
  }>()

  /**
   * Emits
   */
  const emit = defineEmits<{
    (e: 'update:modelValue', value: Partial<Currency>): void
  }>()

  /**
   * Composable de formulario
   */
  const { form, errors, isValid, validate } = useCurrencyForm(props.modelValue, emit)

  /* =======================
   * Computed
   * ======================= */

  const currencyTypes = [
    { value: 'FIAT',   label: 'Fiat' },
    { value: 'CRYPTO', label: 'Criptomoneda' },
    { value: 'TOKEN',  label: 'Token' }
  ]

  /**
   * Formatea el ISO code en mayúsculas al escribir
   */
  function handleIsoCode(value: string) {
    form.value.iso_code = value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 10)
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
        name="name"
        label="Nombre"
        col="col-md-6"
        :required="true"
        placeholder="Ej: Dólar Americano"
        :maxlength="50"
        :error="errors.name"
        :modelValue="form.name"
        @update:modelValue="form.name = $event"
        @blur="validate"
      />

      <!-- Código ISO -->
      <FormInput
        name="iso_code"
        label="Código ISO"
        col="col-md-6"
        :required="true"
        placeholder="Ej: USD"
        :maxlength="10"
        :error="errors.iso_code"
        :modelValue="form.iso_code"
        @update:modelValue="handleIsoCode($event)"
        @blur="validate"
      >
        <template #hint>3–10 caracteres alfanuméricos</template>
      </FormInput>

      <!-- Tipo -->
      <FormSelect
        name="type"
        label="Tipo"
        col="col-md-4"
        :required="true"
        :options="currencyTypes"
        option-value="value"
        option-label="label"
        placeholder="Seleccionar tipo"
        :error="errors.type"
        :modelValue="form.type"
        @update:modelValue="form.type = $event"
      />

      <!-- Símbolo -->
      <FormInput
        name="symbol"
        label="Símbolo"
        col="col-md-4"
        placeholder="Ej: $"
        :maxlength="12"
        :error="errors.symbol"
        :modelValue="form.symbol ?? ''"
        @update:modelValue="form.symbol = $event || null"
      />

      <!-- Decimales -->
      <FormInput
        name="decimal_places"
        label="Decimales"
        col="col-md-4"
        type="number"
        :required="true"
        :min="0"
        :max="10"
        :error="errors.decimal_places"
        :modelValue="form.decimal_places"
        @update:modelValue="form.decimal_places = Number($event)"
        @blur="validate"
      >
        <template #hint>Cantidad de decimales (0–10)</template>
      </FormInput>

      <!-- País -->
      <FormSelect
        name="country_id"
        label="País"
        col="col-md-6"
        :options="countries"
        option-value="id"
        option-label="name"
        placeholder="Seleccionar país"
        :error="errors.country_id"
        :modelValue="form.country_id"
        @update:modelValue="form.country_id = $event ?? null"
      />

      <!-- Descripción -->
      <FormInput
        name="description"
        label="Descripción"
        col="col-md-6"
        placeholder="Descripción detallada de la moneda"
        :maxlength="255"
        :error="errors.description"
        :modelValue="form.description ?? ''"
        @update:modelValue="form.description = $event || null"
      />

      <!-- Activar moneda -->
      <FormSwitch
        name="currency-status"
        label="Moneda activa"
        col="col-12"
        :modelValue="form.status === 'ACTIVE'"
        @update:modelValue="form.status = $event ? 'ACTIVE' : 'INACTIVE'"
      />
    </div>
  </form>
</template>
