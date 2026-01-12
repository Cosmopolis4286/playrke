<template>
  <div class="row">
    <!-- Zona horaria (solo lectura) -->
    <div class="form-group col-sm-6 col-md-4 col-lg-3">
      <label for="time-zone">Zona Horaria</label>
      <input type="text" class="form-control" :value="timeZone.utc" disabled />
    </div>

    <!-- Fecha (solo si no es sala automática) -->
    <div v-if="!automaticRoom" class="form-group col-sm-6 col-md-4 col-lg-3">
      <label for="date">Fecha*</label>
      <input type="date" id="date" v-model="form.date" class="form-control" required :min="minDate" />
    </div>

    <!-- Hora -->
    <div class="form-group col-sm-6 col-md-4 col-lg-3">
      <label for="time">Hora*</label>
      <input type="time" id="time" v-model="form.time" class="form-control" required />
    </div>

    <!-- Tipo de sala -->
    <div v-if="automaticRoom && creator.type !== 'customer'" class="form-group col-sm-6 col-md-4 col-lg-3">
      <label for="room-type">Tipo de Sala*</label>
      <select id="room-type" v-model="form.roomType" name="room_type" class="form-control"
        aria-label="Seleccionar tipo de sala">
        <option disabled :value="null">Selecciona tipo</option>
        <option v-for="option in roomTypeOptions" :key="option.value" :value="option.value">
          {{ option.label }}
        </option>
      </select>
    </div>
    <input v-else type="hidden" id="room-type" name="room_type" class="form-control" required :value="form.roomType" />

    <!-- Modo de juego -->
    <div class="form-group col-sm-6 col-md-4 col-lg-3">
      <label for="game-mode">Modo de Juego*</label>
      <select id="game-mode" v-model="form.gameMode" name="game_mode" class="form-control">
        <option disabled value="">Selecciona modo</option>
        <option v-for="option in gameModeOptions" :key="option.value" :value="option.value">
          {{ option.label }}
        </option>
      </select>
    </div>

    <!-- Tipo Bote -->
    <div class="form-group col-sm-6 col-md-4 col-lg-3">
      <label for="prize-pool-type">Tipo Bote*</label>
      <select id="prize-pool-type" v-model="form.prizePoolType" name="prize_pool_type" class="form-control" required>
        <option disabled value="">Selecciona tipo bote</option>
        <option v-for="option in prizePoolTypeOptions" :key="option.value" :value="option.value">
          {{ option.label }}
        </option>
      </select>
    </div>

    <!-- Monedas permitidas -->
    <div id="container-allowed-currencies" class="form-group col-sm-6">
      <label for="allowed-currencies">Monedas Permitidas*</label>
      <select multiple id="allowed-currencies" v-model="form.allowedCurrencies" name="allowed_currencies" class="form-control select2"
        data-allow-clear="1" placeholder="Seleccionar Monedas">
        <option v-for="currency in allowedCurrenciesOptions" :key="currency.value" :value="currency.value">
          {{ currency.label }}
        </option>
      </select>
    </div>

    <!-- Valor extra al bote -->
    <div id="container-amount-added-prize-pool" class="form-group col-sm-6 col-md-4 col-lg-3">
      <label for="amount-added-prize-pool">Valor Fijo/Extra*</label>
      <div class="input-group">
        <div class="input-group-prepend"><span class="input-group-text">$</span></div>
        <input type="number" id="amount-added-prize-pool" v-model.number="form.amountAddedPrizePool"
          name="amount_added_prize_pool" class="form-control" step="0.05" min="20" max="10000" />
      </div>
    </div>

    <input type="hidden" name="creator" :value="JSON.stringify(creator)" />

    <!-- Comisiones, jackpot y tiempo ficha -->
    <div class="form-group col-sm-6 col-md-4 col-lg-3">
      <label for="room-commn">%Sala*</label>
      <input type="number" id="room-commn" v-model.number="form.roomCommn" name="room_commn"
        class="form-control prize-pool-pct" step="0.05" min="10" max="50" required />
    </div>

    <div class="form-group col-sm-6 col-md-4 col-lg-3">
      <label for="jackpot-pct">%Jackpot</label>
      <input type="number" id="jackpot-pct" v-model.number="form.jackpotPct" name="jackpot_pct"
        class="form-control prize-pool-pct" step="0.5" min="1" max="15" />
    </div>

    <div class="form-group col-sm-6 col-md-4 col-lg-3">
      <label for="time-chip">Tiempo Ficha*</label>
      <div class="input-group">
        <input type="number" id="time-chip" v-model.number="form.timeChip" name="time_chip" class="form-control"
          step="1" min="3" max="10" required />
        <div class="input-group-append">
          <span class="input-group-text">SEG</span>
        </div>
      </div>
    </div>

    <!-- Rondas -->
    <div class="form-group col-sm-6 col-md-4 col-lg-3">
      <label for="rounds">Rondas*</label>
      <div class="input-group">
        <input type="number" id="rounds" v-model.number="form.rounds" name="rounds" class="form-control" step="1"
          min="1" max="15" required />
        <div class="content-rounds-pct input-group-append">
          <button id="btn-rounds-config" class="btn btn-outline-secondary" type="button"
            :data-active="form.roundsConfigActive ? 1 : 0" @click="toggleRoundsConfig"
            title="Ajustar precio por monedas">
            <i class="fa-solid fa-sliders"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- Configuración avanzada de rondas -->
    <div id="container-rounds-config" class="form-group col-12" v-show="form.roundsConfigActive"
      style="max-height: 155px; overflow-y: auto; border: 1px solid #ddd; padding: 1rem; border-radius: 0.25rem;">
      <div class="d-flex">
        <h6 class="mb-3 mr-1">Ajustar valores de las rondas</h6>
        <div v-if="form.loadingRoundsConfig" class="spinner-border spinner-border-sm text-primary" role="status">
          <span class="sr-only">Cargando...</span>
        </div>
      </div>
      <div id="content-rounds-config" class="row">
        <!-- Contenido dinámico de configuración de rondas -->
      </div>
    </div>

    <!-- Botón para crear la sala -->
    <div class="form-group col-12 text-center">
      <button type="button" @click="createRoom" class="btn btn-primary btn-lg">
        <i class="fa-solid fa-plus mr-2"></i>
        Crear Sala de Bingo
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref, computed, onMounted } from "vue";
import dayjs from "dayjs";
import utc from "dayjs/plugin/utc";
import timezone from "dayjs/plugin/timezone";
import { BingoService } from '@js/services/bingoService';

dayjs.extend(utc);
dayjs.extend(timezone);

/**
 * Props recibidas
 */
const props = defineProps < {
  timeZone: { name: string; utc: string };
  automaticRoom: boolean;
  creator: { type: string;[key: string]: any };
} > ();

/**
 * Fecha mínima de hoy según zona horaria
 */
const minDate = computed(() => dayjs().tz(props.timeZone.name).format("YYYY-MM-DD"));

/**
 * Estado reactivo centralizado del formulario
 */
const form = reactive({
  date: minDate.value,
  time: dayjs().tz(props.timeZone.name).format("HH:mm"),
  roomType: null as number | null,
  gameMode: "",
  prizePoolType: "",
  allowedCurrencies: [] as string[],
  amountAddedPrizePool: null as number | null,
  roomCommn: 20,
  jackpotPct: null as number | null,
  timeChip: 5,
  rounds: 1,
  roundsConfigActive: false,
  loadingRoundsConfig: false,
  roundsPct: null as number | null,
  serialsPct: 5,
  numSerials: 5,
  ctnPrice: 1,
  ctnPriceConfigActive: false,
  loadingCtnPriceConfig: false,
});

/**
 * Opciones de selects - cargadas dinámicamente
 */
const roomTypeOptions = ref<Array<{value: number, label: string}>>([]);
const gameModeOptions = ref([
  { value: "classic", label: "Clásico" },
  { value: "speed", label: "Rápido" },
  { value: "progressive", label: "Progresivo" },
]);
const prizePoolTypeOptions = ref([
  { value: "fixed", label: "Fijo" },
  { value: "percentage", label: "Porcentaje" },
  { value: "progressive", label: "Progresivo" },
]);
const allowedCurrenciesOptions = ref<Array<{value: string, label: string}>>([]);

// Estados de carga
const loadingRoomTypes = ref(false);
const loadingCurrencies = ref(false);

/**
 * Cargar tipos de sala desde API
 */
async function loadRoomTypes() {
  loadingRoomTypes.value = true;
  try {
    const response = await BingoService.getRoomTypes();
    if (response.success && response.data) {
      roomTypeOptions.value = response.data.map((type: any) => ({
        value: type.id,
        label: type.description || type.name
      }));
    }
  } catch (error) {
    console.error('Error cargando tipos de sala:', error);
  } finally {
    loadingRoomTypes.value = false;
  }
}

/**
 * Cargar monedas disponibles (esto vendría de WalletService en el futuro)
 */
async function loadCurrencies() {
  loadingCurrencies.value = true;
  try {
    // Por ahora datos mock, después se conectará con WalletService
    allowedCurrenciesOptions.value = [
      { value: "USD", label: "Dólar USD" },
      { value: "EUR", label: "Euro EUR" },
      { value: "COP", label: "Peso Colombiano" },
    ];
  } catch (error) {
    console.error('Error cargando monedas:', error);
  } finally {
    loadingCurrencies.value = false;
  }
}

/**
 * Crear nueva sala de bingo
 */
async function createRoom() {
  try {
    if (!form.roomType) {
      throw new Error('Debe seleccionar un tipo de sala');
    }

    const roomData = {
      roomType: form.roomType,
      cardPrice: form.ctnPrice,
      publicationDate: form.date ? new Date(`${form.date}T${form.time}:00`) : undefined,
      rkeCommission: 5, // Valor por defecto
      roomCommission: form.roomCommn,
      sellerCommission: 15, // Valor por defecto
      fullHousePrize: form.amountAddedPrizePool,
      serialPrize: (form.amountAddedPrizePool || 0) * (form.serialsPct || 5) / 100,
    };

    const response = await BingoService.createRoom(roomData);

    if (response.success) {
      console.log('Sala creada exitosamente:', response.data);
      // Aquí podrías redirigir o mostrar mensaje de éxito
    } else {
      console.error('Error creando sala:', response.message);
      // Mostrar errores al usuario
    }
  } catch (error) {
    console.error('Error en createRoom:', error);
  }
}

/**
 * Métodos para togglear configuraciones avanzadas
 */
function toggleRoundsConfig() {
  form.roundsConfigActive = !form.roundsConfigActive;
}
function toggleCtnPriceConfig() {
  form.ctnPriceConfigActive = !form.ctnPriceConfigActive;
}

/**
 * Inicializar componente
 */
onMounted(async () => {
  await Promise.all([
    loadRoomTypes(),
    loadCurrencies(),
  ]);
});
</script>

<style scoped>
/* Estilos opcionales */
</style>
