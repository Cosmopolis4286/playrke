<script setup lang="ts" name="GameBingoCardGenerateForm">
  import { computed, watch, ref } from 'vue'

  // Components
  import FormInput from '@/js/Components/Common/Form/FormInput.vue'
  import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'

  // Composables
  import { useBingoCardGenerateForm } from '@/js/composables/games/bingo/card/useBingoCardGenerateForm'

  // Types
  import type { BingoCardGenerateFormData } from '@/js/types/games/bingo/card/bingo-card-generate'

  /* ==========================================================================
   * Opción genérica para selects
   * ========================================================================== */

  interface SelectOption {
    value: string | number
    code?: string
    label: string
  }

  /* ==========================================================================
   * Props
   * ========================================================================== */

  const props = defineProps<{
    // automaticRoom?: boolean
    // roomTypeOptions: SelectOption[]
    // gameModeOptions: SelectOption[]
    // prizePoolTypeOptions: SelectOption[]
    // allowedCurrenciesOptions: SelectOption[]
    // bingoPatternOptions: SelectOption[]
    modelValue: BingoCardGenerateFormData
  }>()

  /* ==========================================================================
   * Emits
   * ========================================================================== */

  const emit = defineEmits<{
    (e: 'submit'): void
    (e: 'update:modelValue', value: BingoCardGenerateFormData): void
  }>()

  /* ==========================================================================
   * Form logic (computed v-model)
   * ========================================================================== */

  const { form, errors, isValid, roomCommn, jackpotPct, roundsPct, serialsPct, percentageLimits } =
    useBingoCardGenerateForm(() => props.modelValue)

  /* ==========================================================================
   * Computed
   * ========================================================================== */

  /* ==========================================================================
   * Watchers
   * ========================================================================== */

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
  <form id="bingo-card-generate-form" novalidate @submit.prevent="submit">
    <div class="row">
      <!-- Información del Bingo -->
      <!-- <div class="form-group col-12 col-md-5">
          <label>Próximos Bingos</label>
          <select v-model="selectedRoom" class="form-control">
            <option :value="null">Seleccione</option>
            <option v-for="room in rooms" :key="room.id" :value="room">
              {{ room.name }}
            </option>
          </select>

          <div v-if="selectedRoom" class="mt-2">
            <small>
              <b>Premio actual:</b>
              {{ currencySymbol }} {{ prizePool }}
            </small>
          </div>
        </div>

        <div class="form-group col-6 col-md-4">
          <label>Precio Cartón</label>
          <div class="input-group">
            <div class="input-group-prepend">
              <span class="input-group-text">{{ currencySymbol }}</span>
            </div>
            <input class="form-control bg-light" :value="roomCtnPrice" disabled />
          </div>
        </div>

        <div class="form-group col-6 col-md-3">
          <label>#CTNs</label>
          <input
            v-model="numCtns"
            type="number"
            min="1"
            max="40"
            class="form-control"
            :disabled="!selectedRoom"
          />
        </div>
      </div> -->

      <!-- Información de venta -->
      <!-- <div v-if="selectedRoom" class="row mt-3">
        <div class="col-4">
          <label>Monto Total</label>
          <div>{{ currencySymbol }} {{ totalAmount.toFixed(2) }}</div>
          <small>Costo de los cartones</small>
        </div>

        <div class="col-4">
          <label>Mi Comisión</label>
          <div>{{ currencySymbol }} {{ sellerCommission.toFixed(2) }}</div>
          <small>Ganancia por venta</small>
        </div>

        <div class="col-4">
          <label>Total Banca</label>
          <div>{{ currencySymbol }} {{ commerceAmount.toFixed(2) }}</div>
          <small>Enviar al comercio</small>
        </div>
      </div> -->

      <!-- Información del jugador -->
      <!-- <div v-if="selectedRoom" class="mt-4">
        <hr />

        <div class="alert alert-primary">
          Genera los cartones solo después de confirmar el pago.
        </div>

        <div class="row">
          <div class="form-group col-6">
            <label>Venta*</label>
            <select v-model="typeSale" class="form-control">
              <option value="currency">Moneda</option>
              <option value="transfer">Transferencia</option>
            </select>
          </div>

          <div class="form-group col-6">
            <label>País*</label>
            <input v-model="selectedCountry" type="text" class="form-control" />
          </div>

          <div class="form-group col-6">
            <label>Teléfono*</label>
            <input v-model="player.phone" type="text" class="form-control" />
          </div>

          <div class="form-group col-6">
            <label>Nombre*</label>
            <input v-model="player.name" type="text" class="form-control" />
          </div>

          <div class="form-group col-6">
            <label>Apellido*</label>
            <input v-model="player.lastName" type="text" class="form-control" />
          </div>
        </div>
      </div> -->
    </div>
    <!-- <div v-if="status === 1" class="card-footer text-center">
      <button class="btn btn-primary" :disabled="!selectedRoom || loading" @click="generateCarton">
        Generar Cartón
        <span v-if="loading" class="spinner-border spinner-border-sm ml-2"></span>
      </button>
    </div> -->
  </form>
</template>
