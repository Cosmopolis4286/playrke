<script setup lang="ts" name="BingoCardGenerateFormPlayerSection">
import { computed, ref } from 'vue'
import FormInput from '@/js/Components/Common/Form/FormInput.vue'
import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'
import backendClient from '@/js/plugins/axios'
import { usePhoneField } from '@/js/composables/form/usePhoneField'

import type { Country } from '@/js/types/country'
import type { Player, Room } from '@/js/types/games/bingo/card/bingo-card-generate'

/* ───────── Props ───────── */

const props = defineProps<{
  selectedRoom: Room | null
  countries: Country[]
  loadingCountries: boolean
  selectedCountry: string | null
  player: Player
}>()

/* ───────── Emits ───────── */

const emit = defineEmits<{
  (e: 'update:selectedCountry', value: string | null): void
  (e: 'update:player', value: Player): void
  (e: 'update:playerExists', value: boolean): void
}>()

/* ───────── State ───────── */

const playerExists = ref(false)
const checkingPlayer = ref(false)

/* ───────── Helpers ───────── */

function setPlayerField(field: keyof Player, value: string | number) {
  emit('update:player', {
    ...props.player,
    [field]: String(value)
  })
}

/* ───────── API ───────── */

async function checkPhone(phone: string, countryId: string) {
  const response = await backendClient.get(
    `/external-players/check?phone=${encodeURIComponent(phone)}&country_id=${countryId}&game_id=BINGO&room_id=${props.selectedRoom?.id}`
  )
  return response.data
}

/* ───────── Check player ───────── */

const checkExternalPlayer = async () => {
  if (!props.player.phone || !props.selectedCountry || !isPhoneValid.value) {
    return
  }

  checkingPlayer.value = true

  try {
    const data = await checkPhone(props.player.phone, props.selectedCountry)

    if (data.exists) {
      playerExists.value = true

      emit('update:player', {
        ...props.player,
        name: data.player?.name ?? '',
        lastName: data.player?.last_name ?? ''
      })

      emit('update:playerExists', true)
    } else {
      playerExists.value = false
      emit('update:playerExists', false)
    }
  } catch (error) {
    console.error('Error checking external player', error)
  } finally {
    checkingPlayer.value = false
  }
}

/* ───────── Phone field with IMask ───────── */

const selectedIso2 = computed<string | null>(() => {
  if (!props.selectedCountry) return null
  return props.countries.find(c => c.id === props.selectedCountry)?.iso_code_2 ?? null
})

const phoneValue = computed<string | undefined>({
  get: () => props.player.phone || undefined,
  set: val => setPlayerField('phone', val ?? '')
})

const { phoneMask, phonePlaceholder, phoneHint, phoneError, isPhoneValid } = usePhoneField(
  selectedIso2,
  phoneValue,
  val => setPlayerField('phone', val)
)
</script>

<template>
  <div style="display: contents">
    <hr />

    <div class="alert alert-info mb-3 pt-3 pb-1">
      <h6>
        Genera los cartones <strong>solo después de confirmar el pago</strong> del jugador.
      </h6>
    </div>

    <div class="row">

      <!-- País -->
      <FormSelect
        label="País"
        name="country_id"
        required
        col="col-sm-6 col-md-3"
        :options="countries.map(c => ({ value: c.id, label: c.name }))"
        optionValue="value"
        optionLabel="label"
        :modelValue="selectedCountry"
        :disabled="loadingCountries"
        :placeholder="
          loadingCountries
            ? 'Cargando países…'
            : countries.length === 0
              ? 'Sin países disponibles'
              : 'Seleccione un país'
        "
        @update:modelValue="emit('update:selectedCountry', $event)"
      />

      <!-- Teléfono -->
      <FormInput
        label="Teléfono"
        name="phone"
        type="tel"
        required
        col="col-sm-6 col-md-3"
        :mask="phoneMask ?? undefined"
        :maskEmitFormatted="true"
        :placeholder="phonePlaceholder"
        :hint="!phoneError ? phoneHint : undefined"
        :error="phoneError ?? undefined"
        :inputValidState="player.phone && isPhoneValid ? 'valid' : null"
        :disabled="!selectedCountry"
        :modelValue="player.phone"
        @update:modelValue="value => setPlayerField('phone', value)"
      >

        <!-- botón buscar -->
        <template #append>
          <button
            type="button"
            class="btn btn-outline-secondary"
            :disabled="!player.phone || !selectedCountry || checkingPlayer"
            title="Buscar jugador externo"
            @click="checkExternalPlayer"
          >
            <i v-if="!checkingPlayer" class="fas fa-search"></i>
            <i v-else class="fas fa-spinner fa-spin"></i>
          </button>
        </template>

      </FormInput>

      <!-- Nombre -->
      <FormInput
        v-if="!playerExists"
        label="Nombre"
        name="name"
        type="text"
        readonly
        col="col-sm-6 col-md-3"
        placeholder="Nombre del jugador"
        :modelValue="player.name"
        @update:modelValue="value => setPlayerField('name', value)"
      />

      <!-- Apellido -->
      <FormInput
        v-if="!playerExists"
        label="Apellido"
        name="last_name"
        type="text"
        readonly
        col="col-sm-6 col-md-3"
        placeholder="Apellido del jugador"
        :modelValue="player.lastName"
        @update:modelValue="value => setPlayerField('lastName', value)"
      />

      <!-- aviso -->
      <div v-if="playerExists" class="alert alert-warning mt-3">
        <i class="fas fa-exclamation-triangle me-2"></i>
        El jugador ya está registrado. Los campos de nombre y apellido se cargaron automáticamente.
      </div>

    </div>
  </div>
</template>