<script setup lang="ts" name="BingoCardGenerateFormPlayerSection">
  import FormInput from '@/js/Components/Common/Form/FormInput.vue'
  import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'

  import type { Country } from '@/js/types/country'
  import type { Player, Room } from '@/js/types/games/bingo/card/bingo-card-generate'

  const props = defineProps<{
    selectedRoom: Room | null
    countries: Country[]
    loadingCountries: boolean
    selectedCountry: number | null
    player: Player
    numCtns: number
  }>()

  const emit = defineEmits<{
    (e: 'update:selectedCountry', value: number | null): void
    (e: 'update:player', value: Player): void
    (e: 'update:numCtns', value: number): void
  }>()

  function setPlayerField(field: keyof Player, value: string | number) {
    emit('update:player', {
      ...props.player,
      [field]: String(value)
    })
  }

  function handleNumCtns(value: string | number) {
    const raw = Number(value)
    const clamped = Math.max(1, Math.min(40, raw || 1))
    emit('update:numCtns', clamped)
  }
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
        label="País del Jugador"
        name="country_id"
        required
        col="col-12 col-md-6"
        :options="countries.map(c => ({ value: c.id, label: c.name }))"
        optionValue="value"
        optionLabel="label"
        :modelValue="selectedCountry"
        :disabled="loadingCountries || countries.length === 0"
        :placeholder="loadingCountries ? 'Cargando países…' : 'Seleccione un país'"
        @update:modelValue="emit('update:selectedCountry', $event)"
      />

      <!-- Teléfono -->
      <FormInput
        label="Teléfono"
        name="phone"
        type="tel"
        required
        col="col-12 col-md-6"
        placeholder="Ej. 04121234567"
        :modelValue="player.phone"
        @update:modelValue="value => setPlayerField('phone', value)"
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

      <!-- Cantidad -->
      <FormInput
        label="Cartones"
        name="num_ctns"
        type="number"
        min="1"
        max="40"
        required
        col="col-6 col-md-3"
        :disabled="!selectedRoom"
        :modelValue="numCtns"
        @update:modelValue="handleNumCtns"
      />
    </div>
  </div>
</template>
