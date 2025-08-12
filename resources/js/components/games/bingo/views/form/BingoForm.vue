<template>
  <div class="row">
    <div class="form-group col-sm-6 col-md-4 col-lg-3">
      <label for="time-zone">Zona Horaria</label>
      <input type="text" class="form-control" :value="timeZone.utc" disabled />
    </div>

    <div v-if="!automaticRoom" class="form-group col-sm-6 col-md-4 col-lg-3">
      <label for="date">Fecha*</label>
      <input
        type="date"
        id="date"
        v-model="date"
        class="form-control"
        required
        :min="minDate"
      />
    </div>

    <div class="form-group col-sm-6 col-md-4 col-lg-3">
      <label for="time">Hora*</label>
      <input
        type="time"
        id="time"
        v-model="time"
        class="form-control"
        required
      />
    </div>

    <div
      v-if="automaticRoom && creator.type !== 'customer'"
      class="form-group col-sm-6 col-md-4 col-lg-3"
    >
      <label for="room-type">Tipo de Sala*</label>
      <select
        id="room-type"
        v-model="roomType"
        name="room_type"
        class="form-control"
        aria-label="Seleccionar tipo de sala"
      >
        <option disabled value="">Selecciona tipo</option>
        <!-- Opciones dinámicas -->
        <option
          v-for="option in roomTypeOptions"
          :key="option.value"
          :value="option.value"
        >
          {{ option.label }}
        </option>
      </select>
    </div>
    <input
      v-else
      type="hidden"
      id="room-type"
      name="room_type"
      class="form-control"
      required
      :value="roomType"
    />

    <div class="form-group col-sm-6 col-md-4 col-lg-3">
      <label for="game-mode">Modo de Juego*</label>
      <select
        id="game-mode"
        v-model="gameMode"
        name="game_mode"
        class="form-control"
      >
        <option disabled value="">Selecciona modo</option>
        <option
          v-for="option in gameModeOptions"
          :key="option.value"
          :value="option.value"
        >
          {{ option.label }}
        </option>
      </select>
    </div>

    <div class="form-group col-sm-6 col-md-4 col-lg-3">
      <label for="prize-pool-type">Tipo Bote*</label>
      <select
        id="prize-pool-type"
        v-model="prizePoolType"
        name="prize_pool_type"
        class="form-control"
        required
      >
        <option disabled value="">Selecciona tipo bote</option>
        <option
          v-for="option in prizePoolTypeOptions"
          :key="option.value"
          :value="option.value"
        >
          {{ option.label }}
        </option>
      </select>
    </div>

    <div id="container-allowed-coins" class="form-group col-sm-6">
      <label for="allowed-coins">Monedas Permitidas*</label>
      <select
        multiple
        id="allowed-coins"
        v-model="allowedCoins"
        name="allowed_coins"
        class="form-control select2"
        data-allow-clear="1"
        placeholder="Seleccionar Monedas"
      >
        <option
          v-for="coin in allowedCoinsOptions"
          :key="coin.value"
          :value="coin.value"
        >
          {{ coin.label }}
        </option>
      </select>
    </div>

    <div
      id="container-amount-added-prize-pool"
      class="form-group col-sm-6 col-md-4 col-lg-3"
    >
      <label for="amount-added-prize-pool">Valor Fijo/Extra*</label>
      <div class="input-group">
        <div class="input-group-prepend">
          <span class="input-group-text">$</span>
        </div>
        <input
          type="number"
          id="amount-added-prize-pool"
          v-model.number="amountAddedPrizePool"
          name="amount_added_prize_pool"
          class="form-control"
          step="0.05"
          min="20"
          max="10000"
        />
      </div>
    </div>

    <input type="hidden" name="creator" :value="JSON.stringify(creator)" />

    <div class="form-group col-sm-6 col-md-4 col-lg-3">
      <label for="room-commn">%Sala*</label>
      <input
        type="number"
        id="room-commn"
        v-model.number="roomCommn"
        name="room_commn"
        class="form-control prize-pool-pct"
        step="0.05"
        min="10"
        max="50"
        required
      />
    </div>

    <div class="form-group col-sm-6 col-md-4 col-lg-3">
      <label for="jackpot-pct">%Jackpot</label>
      <input
        type="number"
        id="jackpot-pct"
        v-model.number="jackpotPct"
        name="jackpot_pct"
        class="form-control prize-pool-pct"
        step="0.5"
        min="1"
        max="15"
      />
    </div>

    <div class="form-group col-sm-6 col-md-4 col-lg-3">
      <label for="time-chip">Tiempo Ficha*</label>
      <div class="input-group">
        <input
          type="number"
          id="time-chip"
          v-model.number="timeChip"
          name="time_chip"
          class="form-control"
          step="1"
          min="3"
          max="10"
          required
        />
        <div class="input-group-append">
          <span class="input-group-text">SEG</span>
        </div>
      </div>
    </div>

    <div class="form-group col-sm-6 col-md-4 col-lg-3">
      <label for="rounds">Rondas*</label>
      <div class="input-group">
        <input
          type="number"
          id="rounds"
          v-model.number="rounds"
          name="rounds"
          class="form-control"
          step="1"
          min="1"
          max="15"
          required
        />
        <div class="content-rounds-pct input-group-append">
          <button
            id="btn-rounds-config"
            class="btn btn-outline-secondary"
            type="button"
            :data-active="roundsConfigActive ? 1 : 0"
            @click="toggleRoundsConfig"
            title="Ajustar precio por monedas"
          >
            <i class="fa-solid fa-sliders"></i>
          </button>
        </div>
      </div>
    </div>

    <div
      id="container-rounds-config"
      class="form-group col-12"
      v-show="roundsConfigActive"
      style="
        max-height: 155px;
        overflow-y: auto;
        border: 1px solid #ddd;
        padding: 1rem;
        border-radius: 0.25rem;
      "
    >
      <div class="d-flex">
        <h6 class="mb-3 mr-1">Ajustar valores de las rondas</h6>
        <div
          v-if="loadingRoundsConfig"
          class="spinner-border spinner-border-sm text-primary"
          role="status"
        >
          <span class="sr-only">Cargando...</span>
        </div>
      </div>
      <div id="content-rounds-config" class="row">
        <!-- Aquí puedes poner el contenido dinámico de rounds config -->
      </div>
    </div>

    <div class="content-rounds-pct form-group col-sm-6 col-md-4 col-lg-3">
      <label for="rounds-pct">%Rondas*</label>
      <input
        type="number"
        id="rounds-pct"
        v-model.number="roundsPct"
        name="rounds_pct"
        class="form-control prize-pool-pct"
        step="1"
        min="3"
        max="30"
      />
    </div>

    <div class="form-group col-sm-6 col-md-4 col-lg-3">
      <label for="serials-pct">%Bote Seriales</label>
      <input
        type="number"
        id="serials-pct"
        v-model.number="serialsPct"
        name="serials_pct"
        class="form-control prize-pool-pct"
        step="0.25"
        min="2"
        max="35"
        required
      />
    </div>

    <div class="form-group col-sm-6 col-md-4 col-lg-3">
      <label for="num-serials">#Max. Seriales</label>
      <input
        type="number"
        id="num-serials"
        v-model.number="numSerials"
        name="num_serials"
        class="form-control"
        step="1"
        min="3"
        max="30"
        required
      />
    </div>

    <div class="form-group col-sm-6 col-md-4 col-lg-3">
      <label for="ctn-price">Precio CTN*</label>
      <div class="input-group">
        <div class="input-group-prepend">
          <span class="input-group-text">$</span>
        </div>
        <input
          type="number"
          id="ctn-price"
          v-model.number="ctnPrice"
          name="ctn_price"
          class="form-control"
          placeholder="Precio Cartón"
          min="0.35"
          max="50"
          step="0.05"
          required
          title="Precio Base"
        />
        <div class="input-group-append">
          <button
            id="btn-ctn-price-config"
            class="btn btn-outline-secondary"
            type="button"
            :data-active="ctnPriceConfigActive ? 1 : 0"
            @click="toggleCtnPriceConfig"
            title="Ajustar precio por monedas"
          >
            <i class="fa-solid fa-sliders"></i>
          </button>
        </div>
      </div>
    </div>

    <div
      id="container-ctn-price-config"
      class="form-group col-12"
      v-show="ctnPriceConfigActive"
      style="
        max-height: 155px;
        overflow-y: auto;
        border: 1px solid #ddd;
        padding: 1rem;
        border-radius: 0.25rem;
      "
    >
      <div class="d-flex">
        <h6 class="mb-3 mr-1">Ajustar precio del cartón</h6>
        <div
          v-if="loadingCtnPriceConfig"
          class="spinner-border spinner-border-sm text-primary"
          role="status"
        >
          <span class="sr-only">Cargando...</span>
        </div>
      </div>
      <div id="content-ctn-price-config" class="row">
        <!-- Aquí contenido dinámico -->
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, computed } from "vue";
import dayjs from "dayjs";

// Props simulados, ajusta según cómo recibas datos
const props = defineProps({
  timeZone: { type: Object, required: true },
  automaticRoom: { type: Boolean, default: false },
  creator: { type: Object, required: true },
});

console.log(props.timeZone);

// Fecha mínima hoy en formato YYYY-MM-DD (ajustado a zona horaria)
const minDate = computed(() =>
  dayjs().tz(props.timeZone.name).format("YYYY-MM-DD")
);

// Estado reactivo del formulario
const state = reactive({
  date: minDate.value,
  time: dayjs().tz(props.timeZone.name).format("HH:mm"),
  roomType: "",
  gameMode: "",
  prizePoolType: "",
  allowedCoins: [],
  amountAddedPrizePool: null,
  roomCommn: 20,
  jackpotPct: null,
  timeChip: 5,
  rounds: 1,
  roundsConfigActive: false,
  loadingRoundsConfig: false,
  roundsPct: null,
  serialsPct: 5,
  numSerials: 5,
  ctnPrice: 1,
  ctnPriceConfigActive: false,
  loadingCtnPriceConfig: false,
});

// Para que el v-model sea más limpio, desestructura las propiedades:
const {
  date,
  time,
  roomType,
  gameMode,
  prizePoolType,
  allowedCoins,
  amountAddedPrizePool,
  roomCommn,
  jackpotPct,
  timeChip,
  rounds,
  roundsConfigActive,
  loadingRoundsConfig,
  roundsPct,
  serialsPct,
  numSerials,
  ctnPrice,
  ctnPriceConfigActive,
  loadingCtnPriceConfig,
} = state;

// Opciones de select (rellena con tus datos)
const roomTypeOptions = ref([
  { value: "type1", label: "Tipo 1" },
  { value: "type2", label: "Tipo 2" },
]);
const gameModeOptions = ref([
  { value: "mode1", label: "Modo 1" },
  { value: "mode2", label: "Modo 2" },
]);
const prizePoolTypeOptions = ref([
  { value: "pool1", label: "Bote 1" },
  { value: "pool2", label: "Bote 2" },
]);
const allowedCoinsOptions = ref([
  { value: "coin1", label: "Moneda 1" },
  { value: "coin2", label: "Moneda 2" },
]);

// Métodos para alternar configuraciones
function toggleRoundsConfig() {
  state.roundsConfigActive = !state.roundsConfigActive;
}
function toggleCtnPriceConfig() {
  state.ctnPriceConfigActive = !state.ctnPriceConfigActive;
}
</script>

<style scoped>
/* Opcional: estilos específicos para el componente */
</style>
