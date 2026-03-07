<script setup lang="ts" name="BingoCardGenerateFormPlayerSection">
  import { computed, ref, watch } from 'vue'
  import FormInput from '@/js/Components/Common/Form/FormInput.vue'
  import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'
  import { usePhoneField } from '@/js/composables/form/usePhoneField'

  import type { Country } from '@/js/types/country'
  import type { Player, Room } from '@/js/types/games/bingo/card/bingo-card-generate'

  const props = defineProps<{
    selectedRoom: Room | null
    countries: Country[]
    loadingCountries: boolean
    selectedCountry: number | null
    player: Player
  }>()

  const emit = defineEmits<{
    (e: 'update:selectedCountry', value: number | null): void
    (e: 'update:player', value: Player): void
  }>()

  function setPlayerField(field: keyof Player, value: string | number) {
    emit('update:player', {
      ...props.player,
      [field]: String(value)
    })
  }

  /* ───────── Phone field with IMask ───────── */

  /** ISO-2 del país actualmente seleccionado (ej: 'VE', 'BR', 'CO'). */
  const selectedIso2 = computed<string | null>(() => {
    if (!props.selectedCountry) return null
    return props.countries.find(c => c.id === props.selectedCountry)?.iso_code_2 ?? null
  })

  /**
   * Ref local sincronizado con props.player.phone.
   * usePhoneField lo observa para validar y limpiar al cambiar de país.
   */
  const phoneRef = ref<string | undefined>(props.player.phone)

  // Sincroniza hacia abajo cuando el padre resetea el player (ej: tras generate())
  watch(() => props.player.phone, (val) => { phoneRef.value = val })

  const {
    phoneMask,
    phonePlaceholder,
    phoneHint,
    phoneError,
    isPhoneValid
  } = usePhoneField(
    selectedIso2,
    phoneRef,
    (val) => {
      phoneRef.value = val
      setPlayerField('phone', val)
    }
  )
</script>

<template>
  <div style="display: contents">
    <hr />

    <div class="alert alert-info mb-3 py-2">
      <small>
        Genera los cartones <strong>solo después de confirmar el pago</strong> del jugador.
      </small>
    </div>

    <div class="row">
      <!-- País -->
      <FormSelect
        label="País"
        name="country_id"
        required
        col="col-12 col-md-6"
        :options="countries.map(c => ({ value: c.id, label: c.name }))"
        optionValue="value"
        optionLabel="label"
        :modelValue="selectedCountry"
        :disabled="loadingCountries"
        :placeholder="loadingCountries ? 'Cargando países…' : countries.length === 0 ? 'Sin países disponibles' : 'Seleccione un país'"
        @update:modelValue="emit('update:selectedCountry', $event)"
      />

      <!-- Teléfono -->
      <FormInput
        label="Teléfono"
        name="phone"
        type="tel"
        required
        col="col-12 col-md-6"
        :mask="phoneMask ?? undefined"
        :maskEmitFormatted="true"
        :placeholder="phonePlaceholder"
        :hint="!phoneError ? phoneHint : undefined"
        :error="phoneError ?? undefined"
        :inputValidState="player.phone && isPhoneValid ? 'valid' : null"
        :disabled="!selectedCountry"
        :modelValue="player.phone"
        @update:modelValue="value => { phoneRef = String(value); setPlayerField('phone', value) }"
      />

      <!-- Nombre -->
      <FormInput
        label="Nombre"
        name="name"
        type="text"
        required
        col="col-12 col-md-6"
        placeholder="Nombre del jugador"
        :modelValue="player.name"
        @update:modelValue="value => setPlayerField('name', value)"
      />

      <!-- Apellido -->
      <FormInput
        label="Apellido"
        name="last_name"
        type="text"
        required
        col="col-12 col-md-6"
        placeholder="Apellido del jugador"
        :modelValue="player.lastName"
        @update:modelValue="value => setPlayerField('lastName', value)"
      />

    </div>
  </div>
</template>
