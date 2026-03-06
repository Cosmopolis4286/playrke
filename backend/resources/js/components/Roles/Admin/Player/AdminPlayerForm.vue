<script setup lang="ts" name="AdminPlayerForm">
  import { computed, ref, watch, nextTick } from 'vue'

  // Components
  import FormInput from '@/js/Components/Common/Form/FormInput.vue'
  import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'

  // Composables
  import { useAdminPlayerForm } from '@/js/composables/roles/admin/player/useAdminPlayerForm'
  import { usePhoneField } from '@/js/composables/form/usePhoneField'

  // Types
  import type { Country } from '@/js/types/country'
  import type { TimeZone } from '@/js/types/time-zone'
  import type { Player } from '@/js/types/roles/player/player'

  /* ==========================================================================
   * Props
   * ========================================================================== */

  const props = defineProps<{
    countries: Country[]
    timeZones: TimeZone[]
    modelValue: Partial<Player>
  }>()

  /* ==========================================================================
   * Emits
   * ========================================================================== */

  const emit = defineEmits<{
    (e: 'submit'): void
    (e: 'update:modelValue', value: Partial<Player>): void
  }>()

  /* ==========================================================================
   * Form logic
   * ========================================================================== */

  const { form, errors, isValid } = useAdminPlayerForm(() => props.modelValue, emit)

  /* ==========================================================================
   * Teléfono — usePhoneField
   * ========================================================================== */

  const selectedIso2 = computed<string | null>(() => {
    if (!form.value.country_id) return null
    const match = props.countries.find(
      // eslint-disable-next-line eqeqeq
      c => c.id == form.value.country_id
    )
    return match?.iso_code_2 ?? null
  })

  const phoneRef = ref<string>(form.value.phone ?? '')

  watch(
    () => form.value.phone,
    v => { if (v !== phoneRef.value) phoneRef.value = v ?? '' }
  )

  watch(phoneRef, v => {
    if (v !== form.value.phone) form.value = { ...form.value, phone: v }
  })

  const { phoneMask, phonePlaceholder, phoneHint, phoneDialPrefix, phoneError, isPhoneValid } =
    usePhoneField(selectedIso2, phoneRef, v => { phoneRef.value = v })

  watch(selectedIso2, (iso, prevIso) => {
    if (iso && iso !== prevIso) {
      nextTick(() => {
        if (!phoneRef.value?.trim()) phoneRef.value = phoneDialPrefix.value
      })
    }
  })

  const phoneErrorDisplay = computed(() => phoneError.value ?? errors.value.phone ?? null)

  const phoneValidState = computed<'valid' | 'invalid' | null>(() => {
    if (!phoneRef.value?.trim()) return null
    return isPhoneValid.value ? 'valid' : 'invalid'
  })

  /* ==========================================================================
   * Time zones filtradas por país
   * ========================================================================== */

  const filteredTimeZones = computed(() => {
    if (!form.value.country_id) return []
    // eslint-disable-next-line eqeqeq
    return props.timeZones.filter(tz => tz.country_id == form.value.country_id)
  })

  const hasSingleTimeZone = computed(() => filteredTimeZones.value.length === 1)

  watch(
    () => form.value.country_id,
    async (newVal, oldVal) => {
      if (oldVal !== undefined) {
        form.value = { ...form.value, time_zone_id: null }
      }
      if (!newVal) return
      await nextTick()

      // eslint-disable-next-line eqeqeq
      const timeZones = props.timeZones.filter(tz => tz.country_id == newVal)
      form.value = {
        ...form.value,
        time_zone_id: timeZones.length === 1 ? timeZones[0].id : form.value.time_zone_id,
      }
    },
    { immediate: true }
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
  <form id="player-form" novalidate @submit.prevent="submit">
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
        col="col-sm-6"
        :error="errors.email"
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
    </div>
  </form>
</template>
