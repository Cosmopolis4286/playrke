<script setup lang="ts" name="BingoCardGenerateFormPlayerSection">
  import { computed } from 'vue'

  import FormInput from '@/js/Components/Common/Form/FormInput.vue'
  import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'

  import { Country } from '@/js/types/country'

  import { Player, Room } from '@/js/types/games/bingo/card/bingo-card-generate'

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

  function setPlayerField(field: keyof Player, value: string) {
    emit('update:player', { ...props.player, [field]: value })
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
      <small
        >Genera los cartones <strong>solo después de confirmar el pago</strong> del jugador.</small
      >
    </div>

    <div class="row">
      <!-- País -->
      <div class="form-group col-12 col-md-6">
        <label class="font-weight-bold">País del Jugador *</label>
        <select
          :value="selectedCountry ?? ''"
          class="form-control"
          :disabled="loadingCountries || countries.length === 0"
          @change="
            emit(
              'update:selectedCountry',
              Number(($event.target as HTMLSelectElement).value) || null
            )
          "
        >
          <option value="">
            {{ loadingCountries ? 'Cargando países…' : '— Seleccione un país —' }}
          </option>
          <option v-for="country in countries" :key="country.id" :value="country.id">
            {{ country.name }}
          </option>
        </select>
      </div>

      <!-- Teléfono -->
      <div class="form-group col-12 col-md-6">
        <label class="font-weight-bold">Teléfono *</label>
        <input
          :value="player.phone"
          type="tel"
          class="form-control"
          placeholder="Ej. 04121234567"
          @input="setPlayerField('phone', ($event.target as HTMLInputElement).value)"
        />
      </div>

      <!-- Nombre -->
      <div class="form-group col-12 col-md-6">
        <label class="font-weight-bold">Nombre *</label>
        <input
          :value="player.name"
          type="text"
          class="form-control"
          placeholder="Nombre del jugador"
          @input="setPlayerField('name', ($event.target as HTMLInputElement).value)"
        />
      </div>

      <!-- Apellido -->
      <div class="form-group col-12 col-md-6">
        <label class="font-weight-bold">Apellido *</label>
        <input
          :value="player.lastName"
          type="text"
          class="form-control"
          placeholder="Apellido del jugador"
          @input="setPlayerField('lastName', ($event.target as HTMLInputElement).value)"
        />
      </div>

      <!-- Cantidad -->
      <FormInput
        label="Cartones"
        name="num_ctns"
        type="number"
        min="1"
        max="40"
        col="col-6 col-md-3"
        :disabled="!selectedRoom"
        :modelValue="numCtns"
        @update:modelValue="handleNumCtns"
      />
    </div>
  </div>
</template>
