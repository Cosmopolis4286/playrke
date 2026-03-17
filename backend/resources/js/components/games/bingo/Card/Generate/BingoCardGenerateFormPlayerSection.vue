<script setup lang="ts" name="BingoCardGenerateFormPlayerSection">
import { computed, ref } from 'vue'
import FormInput from '@/js/Components/Common/Form/FormInput.vue'
import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'
import backendClient from '@/js/plugins/axios'
import { usePhoneField } from '@/js/composables/form/usePhoneField'
import { showToast } from '@/js/utils/toastNotify'

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
const imagePreview = ref<string | null>(null)
const uploadingProof = ref(false)

// Show proof of payment when all player fields have data
const showProofOfPayment = computed(() => {
  return !!(
    props.player.firstName &&
    props.player.lastName &&
    props.player.phone &&
    props.selectedCountry
  )
})

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
    `/external-players/check?phone=${encodeURIComponent(phone)}&country_id=${countryId}&game_id=bingo&room_id=${props.selectedRoom?.id}`
  )
  return response.data
}

/* ───────── Proof of Payment ───────── */

function onImageChange(event: Event) {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]

  if (!file) return

  // Validar tamaño (2 MB)
  if (file.size > 2 * 1024 * 1024) {
    showToast('El archivo no debe superar los 2 MB', 'error')
    return
  }

  // Validar tipo
  if (!file.type.match(/^image\/(jpeg|jpg|png)$/)) {
    showToast('Solo se permiten archivos JPG o PNG', 'error')
    return
  }

  // Crear preview
  const reader = new FileReader()
  reader.onload = (e) => {
    imagePreview.value = e.target?.result as string
  }
  reader.readAsDataURL(file)
}

function clearImage() {
  imagePreview.value = null
  // Limpiar el input file
  const input = document.getElementById('image') as HTMLInputElement
  if (input) input.value = ''
}

async function uploadProof() {
  if (!imagePreview.value) {
    showToast('Por favor selecciona un comprobante', 'error')
    return
  }

  uploadingProof.value = true

  try {
    // Aquí iría la lógica para subir el comprobante
    // Por ahora solo mostramos un mensaje de éxito
    showToast('Comprobante cargado correctamente', 'success')
  } catch (error) {
    showToast('Error al cargar el comprobante', 'error')
  } finally {
    uploadingProof.value = false
  }
}

/* ───────── Check player ───────── */

const checkExternalPlayer = async () => {
  if (!props.player.phone || !props.selectedCountry || !isPhoneValid.value) {
    return
  }

  checkingPlayer.value = true
  let data: any

  try {
    data = await checkPhone(props.player.phone, props.selectedCountry)

    if (data.exists) {
      playerExists.value = true

      emit('update:player', {
        ...props.player,
        firstName: data.player?.first_name ?? '',
        lastName: data.player?.last_name ?? '',
        cardCount: data.player?.card_count || 0
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

defineExpose({
  onImageChange,
  clearImage,
  uploadProof
})
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
        label="Nombre"
        name="first_name"
        type="text"
        required
        col="col-sm-6 col-md-3"
        placeholder="Nombre del jugador"
        :readonly="playerExists"
        :modelValue="player.firstName"
        @update:modelValue="value => setPlayerField('firstName', value)"
      />

      <!-- Apellido -->
      <FormInput
        label="Apellido"
        name="last_name"
        type="text"
        required
        col="col-sm-6 col-md-3"
        placeholder="Apellido del jugador"
        :readonly="playerExists"
        :modelValue="player.lastName"
        @update:modelValue="value => setPlayerField('lastName', value)"
      />

      <!-- aviso -->
      <div v-if="playerExists" class="alert alert-warning mt-3">
        <i class="fas fa-exclamation-triangle me-2"></i>
        El jugador ya está registrado. Ya ha comprado {{ data.player?.card_count || 0 }} cartones.
      </div>

       <!-- ── Comprobante ────────────────────────────────────────────────────────── -->
     <template v-if="showProofOfPayment">
       <div class="col-12">
        <small class="text-muted text-uppercase font-weight-bold" style="letter-spacing:.05em;">
          <i class="fa-solid fa-image fa-fw mr-1"></i>Comprobante
        </small>
        <hr class="mt-1 mb-2" />
      </div>

      <div class="col-12">
        <div class="form-group">
          <label for="image">
            Comprobante <small class="text-muted">(PNG o JPG, máx. 2 MB) <span class="text-danger">*</span></small>
          </label>
          <div class="d-flex align-items-start" style="gap:1rem;">
            <!-- Preview / placeholder -->
            <div
              class="border rounded d-flex align-items-center justify-content-center overflow-hidden flex-shrink-0"
              style="width:80px;height:80px;background:#f8f9fa;"
            >
              <img
                v-if="imagePreview"
                :src="imagePreview"
                alt="Preview image"
                style="max-width:100%;max-height:100%;object-fit:contain;"
              />
              <i v-else class="fa-solid fa-image fa-2x text-muted" style="opacity:.35;"></i>
            </div>
            <!-- Input file + botón quitar -->
            <div class="flex-grow-1">
              <input
                id="image"
                name="image"
                type="file"
                class="form-control-file"
                accept="image/png, image/jpeg"
                required
                @change="onImageChange"
              />
              <button
                v-if="imagePreview"
                type="button"
                class="btn btn-sm btn-outline-danger mt-1"
                @click.prevent="clearImage"
              >
                <i class="fa-solid fa-trash fa-fw"></i> Quitar imagen
              </button>
            </div>
          </div>
        </div>
      </div>
     </template>

    </div>
  </div>
</template>
