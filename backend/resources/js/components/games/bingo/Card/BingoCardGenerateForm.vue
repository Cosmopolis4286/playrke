<script setup lang="ts" name="GameBingoCardGenerateForm">
import type { Room, Country, Player } from '@/js/composables/games/bingo/card/useBingoCardGenerate'

/* ==========================================================================
 * Props / Emits
 * ========================================================================== */

const props = defineProps<{
  rooms:            Room[]
  countries:        Country[]
  loadingCountries: boolean
  roomCtnPrice:     number
  totalAmount:      number
  sellerCommission: number
  commerceAmount:   number
  isFormValid:      boolean
  loading:          boolean
  selectedRoom:     Room | null
  numCtns:          number
  selectedCountry:  number | null
  player:           Player
}>()

const emit = defineEmits<{
  (e: 'update:selectedRoom',    value: Room | null):   void
  (e: 'update:numCtns',         value: number):        void
  (e: 'update:selectedCountry', value: number | null): void
  (e: 'update:player',          value: Player):        void
  (e: 'generate'):                                     void
}>()

/* ==========================================================================
 * Helpers v-model
 * ========================================================================== */

function setPlayerField(field: keyof Player, value: string) {
  emit('update:player', { ...props.player, [field]: value })
}
</script>

<template>
  <form novalidate @submit.prevent="emit('generate')">
    <div class="row">

      <!-- ── Sala ──────────────────────────────────────────────────── -->
      <div class="form-group col-12 col-md-6">
        <label class="font-weight-bold">Sala de Bingo</label>
        <select
          :value="selectedRoom?.id ?? ''"
          class="form-control"
          @change="emit('update:selectedRoom', rooms.find(r => r.id === ($event.target as HTMLSelectElement).value) ?? null)"
        >
          <option value="">— Seleccione una sala —</option>
          <option v-for="room in rooms" :key="room.id" :value="room.id">
            {{ room.uuid_code }}
          </option>
        </select>
      </div>

      <!-- ── Precio & Cantidad ─────────────────────────────────────── -->
      <div class="form-group col-6 col-md-3">
        <label class="font-weight-bold">Precio Cartón (USD)</label>
        <input class="form-control bg-light" :value="roomCtnPrice.toFixed(2)" disabled />
      </div>

      <div class="form-group col-6 col-md-3">
        <label class="font-weight-bold"># Cartones</label>
        <input
          :value="numCtns"
          type="number"
          min="1"
          max="40"
          class="form-control"
          :disabled="!selectedRoom"
          @input="emit('update:numCtns', Math.max(1, Math.min(40, Number(($event.target as HTMLInputElement).value))))"
        />
      </div>

    </div>

    <!-- ── Resumen de venta ──────────────────────────────────────────── -->
    <div v-if="selectedRoom" class="row mt-2 py-2 bg-light rounded">
      <div class="col-4 text-center border-right">
        <div class="text-muted small">Monto Total</div>
        <div class="h5 font-weight-bold mb-0">$ {{ totalAmount.toFixed(2) }}</div>
        <small class="text-muted">Cobrar al jugador</small>
      </div>
      <div class="col-4 text-center border-right">
        <div class="text-muted small">Mi Comisión</div>
        <div class="h5 text-success font-weight-bold mb-0">$ {{ sellerCommission.toFixed(2) }}</div>
        <small class="text-muted">Tu ganancia</small>
      </div>
      <div class="col-4 text-center">
        <div class="text-muted small">Total Banca</div>
        <div class="h5 font-weight-bold mb-0">$ {{ commerceAmount.toFixed(2) }}</div>
        <small class="text-muted">Enviar al comercio</small>
      </div>
    </div>

    <!-- ── Datos del jugador ─────────────────────────────────────────── -->
    <div v-if="selectedRoom" class="mt-4">
      <hr />
      <div class="alert alert-info py-2 mb-3">
        <small>Genera los cartones <strong>solo después de confirmar el pago</strong> del jugador.</small>
      </div>

      <div class="row">

        <!-- País -->
        <div class="form-group col-12 col-md-6">
          <label class="font-weight-bold">País del Jugador *</label>
          <select
            :value="selectedCountry ?? ''"
            class="form-control"
            :disabled="loadingCountries || countries.length === 0"
            @change="emit('update:selectedCountry', Number(($event.target as HTMLSelectElement).value) || null)"
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

      </div>
    </div>

    <!-- ── Botón ─────────────────────────────────────────────────────── -->
    <div v-if="selectedRoom" class="text-center mt-3">
      <button
        type="submit"
        class="btn btn-primary btn-lg px-5"
        :disabled="!isFormValid || loading"
      >
        <span v-if="loading" class="spinner-border spinner-border-sm mr-2" role="status" />
        {{ loading ? 'Generando…' : 'Generar Cartones' }}
      </button>
    </div>

  </form>
</template>
