<script setup lang="ts" name="ManagerSellerForm">
  import { computed, ref, watch, nextTick } from 'vue'

  // Components
  import FormInput from '@/js/Components/Common/Form/FormInput.vue'
  import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'

  // Composables
  import { useManagerSellerForm } from '@/js/composables/roles/seller/manager/useManagerSellerForm'
  import { usePhoneField } from '@/js/composables/form/usePhoneField'

  // Types
  import type { Currency } from '@/js/types/currency'
  import type { Country } from '@/js/types/country'
  import type { TimeZone } from '@/js/types/time-zone'
  import type { Game } from '@/js/types/game'
  import type { Seller } from '@/js/types/roles/seller/seller'

  /* ==========================================================================
   * Props
   * ========================================================================== */

  const props = defineProps<{
    countries: Country[]
    currencies: Currency[]
    timeZones: TimeZone[]
    games: Game[]
    modelValue: Partial<Seller>
  }>()

  /* ==========================================================================
   * Emits
   * ========================================================================== */

  const emit = defineEmits<{
    (e: 'submit'): void
    (e: 'update:modelValue', value: Partial<Seller>): void
  }>()

  /* ==========================================================================
   * Form logic
   * ========================================================================== */

  const { form, errors, isValid } = useManagerSellerForm(() => props.modelValue, emit)

  /* ==========================================================================
   * Máscara comisión
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
   * Teléfono — usePhoneField
   * ========================================================================== */

  /** ISO-2 del país seleccionado */
  const selectedIso2 = computed<string | null>(() => {
    if (!form.value.country_id) return null
    const match = props.countries.find(
      // eslint-disable-next-line eqeqeq
      c => c.id == form.value.country_id
    )
    return match?.iso_code_2 ?? null
  })

  /** Ref local del teléfono — fuente de verdad para usePhoneField */
  const phoneRef = ref<string>(form.value.phone ?? '')

  // form → phoneRef  (ej: al cargar en modo edición)
  watch(
    () => form.value.phone,
    v => {
      if (v !== phoneRef.value) phoneRef.value = v ?? ''
    }
  )

  // phoneRef → form
  watch(phoneRef, v => {
    if (v !== form.value.phone) {
      form.value = { ...form.value, phone: v }
    }
  })

  const { phoneMask, phonePlaceholder, phoneHint, phoneDialPrefix, phoneError, isPhoneValid } =
    usePhoneField(selectedIso2, phoneRef, v => {
      phoneRef.value = v
    })

  // Al seleccionar un país, pre-rellenar el campo con el prefijo (+58, +55, +57)
  watch(selectedIso2, (iso, prevIso) => {
    if (iso && iso !== prevIso) {
      nextTick(() => {
        if (!phoneRef.value?.trim()) {
          phoneRef.value = phoneDialPrefix.value
        }
      })
    }
  })

  /** Error visible: validación de formato primero, luego campo requerido */
  const phoneErrorDisplay = computed(() => phoneError.value ?? errors.value.phone ?? null)

  /** Estado visual del campo teléfono */
  const phoneValidState = computed<'valid' | 'invalid' | null>(() => {
    if (!phoneRef.value?.trim()) return null
    return isPhoneValid.value ? 'valid' : 'invalid'
  })

  /* ==========================================================================
   * Filtered currencies / timezones by country
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

  const hasSingleCurrency = computed(() => filteredCurrencies.value.length === 1)
  const hasSingleTimeZone = computed(() => filteredTimeZones.value.length === 1)

  const otherCurrenciesOptions = computed(() => {
    if (!form.value.currency_id) return props.currencies
    // eslint-disable-next-line eqeqeq
    return props.currencies.filter(c => c.id != form.value.currency_id)
  })

  /* ==========================================================================
   * Watchers
   * ========================================================================== */

  watch(
    () => form.value.country_id,
    async (newVal, oldVal) => {
      if (oldVal !== undefined) {
        form.value = { ...form.value, currency_id: null, time_zone_id: null, other_currencies: [] }
      }
      if (!newVal) return
      await nextTick()

      // eslint-disable-next-line eqeqeq
      const currencies = props.currencies.filter(c => c.country_id == newVal)
      // eslint-disable-next-line eqeqeq
      const timeZones = props.timeZones.filter(tz => tz.country_id == newVal)

      form.value = {
        ...form.value,
        currency_id: currencies.length === 1 ? currencies[0].id : form.value.currency_id,
        time_zone_id: timeZones.length === 1 ? timeZones[0].id : form.value.time_zone_id
      }
    },
    { immediate: true }
  )

  watch(
    () => form.value.currency_id,
    (newVal, oldVal) => {
      if (oldVal !== undefined && newVal !== oldVal) {
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

  defineExpose({ submit, isValid })
</script>

<template>
  <form id="seller-form" novalidate @submit.prevent="submit">
    <div class="row">
      <FormInput
        label="Razon Social"
        name="name"
        required
        col="col-sm-6"
        :error="errors.name"
        v-model="form.name"
      />

      <FormInput
        label="Username"
        name="username"
        required
        minlength="5"
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
        col="col-md-8"
        :error="errors.email"
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
        :error="errors.commission"
        v-model="form.commission"
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
        :error="errors.country_id"
        v-model="form.country_id"
      />

      <FormInput
        label="Teléfono"
        name="phone"
        required
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
        :error="errors.currency_id"
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
        :error="errors.allowed_games"
        v-model="form.allowed_games"
      />
    </div>
  </form>
</template>
