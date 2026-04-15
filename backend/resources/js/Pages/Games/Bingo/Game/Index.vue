<script setup lang="ts">
/**
 * Página: Juego de Bingo (in-game)
 *
 * Vista equivalente al blade `in-games.blade.php`,
 * migrada a Vue 3 + Inertia.
 *
 * Responsabilidades:
 *  - Mostrar el estado actual del juego de bingo
 *  - Renderizar cartones del jugador
 *  - Gestionar modales de información
 *  - Manejar estado del juego en tiempo real
 */
// ==========================================================================
// Imports
// ==========================================================================
import { ref, onMounted, computed } from "vue";
import "@/css/games/bingo/bingo-in-game.css";

// ==========================================================================
// Componentes
// ==========================================================================
import BingoCartons from "@js/Components/Games/Bingo/views/inGame/Cartons.vue";

// ==========================================================================
// Types
// ==========================================================================
import type { BingoRoom, BingoCard } from '@js/Services/bingoService';

interface Player {
  ctns: number;
}

// ==========================================================================
// Props (Inertia)
// ==========================================================================
interface Props {
  title?: string;
  room: BingoRoom & {
    Config: {
      rounds?: number;
      bingo_patterns?: boolean;
    };
  };
  cartons?: BingoCard[];
  customerLogo?: string;
  currencySym?: string;
  prizePool?: number;
  prizePoolType?: string;
  round?: number;
  rounds?: number; // Agregado: número total de rondas
  bingoPattern?: string;
  vanDraw?: number[];
  player?: Player;
  encrypt?: boolean;
  chipsRest?: number[];
  draw?: number[];
  timeServ?: number;
  allChips?: number[];
  infoGame?: any;
  creatorType?: string;
  creatorId?: string;
  currencyId?: string;
  cardPrice?: number;
  startTime?: number;
  closingRoute?: string;
}

const props = withDefaults(defineProps<Props>(), {
  title: "Juego de Bingo",
  cartons: () => [],
  customerLogo: null,
  currencySym: "$",
  prizePool: 0,
  prizePoolType: "Premio",
  round: 1,
  bingoPattern: "",
  vanDraw: () => [],
  player: () => ({ ctns: 0 }),
  encrypt: false,
  chipsRest: () => [],
  draw: () => [],
  timeServ: 0,
  allChips: () => [],
  infoGame: null,
  creatorType: "",
  creatorId: "",
  currencyId: "",
  cardPrice: 0,
  startTime: 0,
  closingRoute: "",
});

// ==========================================================================
// Estado reactivo
// ==========================================================================
const preRoute = ref("/");
const logo = ref(props.customerLogo || "/img/rke.png");
const encrypted = ref(props.room.encrypted);
const allCards = ref(props.cartons);
const roomStatus = ref(props.room.status);
const currencySymbol = ref(props.currencySym);
const chpLast = ref(props.vanDraw);
const player = ref(props.player);
const encrypt = ref(props.encrypt);
const restChips = ref(props.chipsRest);

let arrDraw = ref(props.draw);
let timeServ = ref(props.timeServ);
let allChips = ref(props.allChips);
let infoGame = ref(props.infoGame);

// Estado para juego activo
const prizePoolType = ref(props.prizePoolType);
const roundNum = ref(props.round);
const numRounds = ref(props.rounds || 1); // Usar rounds directamente desde props
const startTime = ref(props.startTime);

let loaderChip = ref(true);
let loadTimeServ = ref(false);
let actSound = ref(0);
let endWin = ref(
  (arrDraw.value.length && (roomStatus.value === 2 || roomStatus.value === 3)) 
    ? 0 
    : (roomStatus.value >= 4 ? 1 : 0)
);
let sizeCarrsel = ref(0);

// Estado para compra de cartones (status == 2)
const creatorType = ref(props.creatorType);
const creatorId = ref(props.creatorId);
const currencyId = ref(props.currencyId);
const cardPrice = ref(props.cardPrice);
let gameStartTime = ref(props.startTime);
let amount = ref(props.cardPrice);

// ==========================================================================
// Computed properties
// ==========================================================================
const shouldShowHistoryChips = computed(() => props.room.status > 2);
const shouldShowDrawModal = computed(() => props.room.status === 2);
const shouldShowWinnersModal = computed(() => props.room.status > 2);
const shouldShowPatternsModal = computed(() => 
  props.room.status > 2 && props.room.Config.bingo_patterns
);
const shouldShowSounds = computed(() => props.room.status > 2);
const shouldShowPurchaseScripts = computed(() => 
  props.room.status === 2 && props.creatorType !== 'customer'
);

// ==========================================================================
// Lifecycle
// ==========================================================================
onMounted(() => {
  // Inicializar audio si es necesario
  if (shouldShowSounds.value) {
    // Aquí se inicializarían los sonidos si fueran necesarios
  }
});
</script>

<template>
  <div class="bingo-in-game-page">
    <div class="col-md-11 col-lg-7 col-xl-8 m-auto">
      <!-- Container de últimos chips -->
      <div class="position-relative col-sm-8 col-md-7 col-lg-6">
        <div id="cont-last-chips" class="py-2">
          <div id="cont-loader">
            <div id="loader-last-chips"></div>
          </div>
        </div>
        <div v-if="room.status === 3" id="text-check-bingo">
          Comprobando si hay <b>BINGO</b>
        </div>
      </div>

      <!-- Container de información del bingo -->
      <div id="container-bingo-info" class="mb-2 p-2">
        <div id="cont-prize-pool-info">
          <div id="container-btn-options" class="mr-2">
            <div class="btn-group-vertical">
              <button type="button" disabled id="btn-view-winners" class="btn btn-sm btn-dark">
                <i class="fa-solid fa-trophy"></i>
              </button>
              <button type="button" disabled id="btn-bingo-rules" class="btn btn-sm btn-dark" data-toggle="modal"
                  data-target="#modal-bingo-rules">
                <i class="fa-solid fa-circle-info"></i>
              </button>
            </div>
          </div>
          <div id="cont-prize-pool-info-amount">
            <span id="cont-prize-pool-type">{{ prizePoolType }}</span>
            <div id="cont-prize-pool-amount">
              <span id="prize-pool-currency-sym">{{ currencySym }}</span>
              <span id="prize-pool-amount">{{ prizePool ?? 0 }}</span>
            </div>
          </div>
        </div>
        <div id="cont-bingo-info-extras">
          <div id="cont-game-mode-opcs">
            <div v-if="rounds" id="container-round">
              Ronda<span id="num-round">#{{ round }}</span>
            </div>
            <div v-if="bingoPattern" id="text-game-mode">{{ bingoPattern }}</div>
          </div>
        </div>
      </div>

      <!-- Container de historial de chips (solo si status > 2) -->
      <div v-if="shouldShowHistoryChips" class="container-history-chips">
        <!-- Aquí iría el componente de historial de chips -->
      </div>

      <!-- Cartones del jugador -->
      <div class="container mt-1">
        <BingoCartons 
          :cartons="cartons ?? []" 
          :customerLogo="customerLogo ?? null"
          :roomId="room.id?.toString()"
        />
      </div>

      <!-- Modales según el estado del juego -->
      
      <!-- Modal de información de sorteo (status == 2) -->
      <div v-if="shouldShowDrawModal" class="modal-info-draw">
        <!-- Aquí iría el modal de información de sorteo -->
      </div>

      <!-- Modal de información de ganadores (status > 2) -->
      <div v-if="shouldShowWinnersModal" class="modal-info-winners">
        <!-- Aquí iría el modal de información de ganadores -->
      </div>

      <!-- Sonidos (status > 2) -->
      <div v-if="shouldShowSounds" class="sounds-container">
        <!-- Aquí irían los controles de sonido -->
      </div>

      <!-- Modal de patrones de bingo (status > 2 y tiene patrones) -->
      <div v-if="shouldShowPatternsModal" class="modal-bingo-patterns">
        <!-- Aquí iría el modal de patrones de bingo -->
      </div>

      <!-- Modal de reglas del bingo (siempre visible) -->
      <div class="modal-bingo-rules">
        <!-- Aquí iría el modal de reglas del bingo -->
      </div>
    </div>
  </div>
</template>

<style>
/* ═══════════════════════════════════════════════════════════════
   FONDO ESPACIAL — eliminado, ahora usa el fondo del CSS principal
═══════════════════════════════════════════════════════════════ */
/* body {
  background-image: url("/img/body/space.png") !important;
} */

/* Eliminar content-header y padding para ocupar todo el espacio */
.content-header {
  display: none !important;
  padding: 0 !important;
  min-height: 0 !important;
  height: 0 !important;
}

.content-wrapper > .content {
  padding: 0 !important;
}

/* La section.content debe ser flex para que .bingo-in-game-page
   pueda estirarse al 100% de altura */
.content-wrapper > .content {
  display: flex;
  flex-direction: column;
}

/* El div#app que Inertia monta también debe estirarse */
.content-wrapper > .content > #app,
.content-wrapper > .content > [data-page] {
  flex: 1;
  display: flex;
  flex-direction: column;
}
</style>

<style scoped>
/* ═══════════════════════════════════════════════════════════════
   PÁGINA DE JUEGO — layout principal
═══════════════════════════════════════════════════════════════ */
.bingo-in-game-page {
  flex: 1;
  min-height: 100vh;
  padding: 20px 16px 60px;
  position: relative;
  isolation: isolate;
  overflow-y: auto;
  overflow-x: hidden;
}

/* Scrollbar premium */
.bingo-in-game-page {
  scrollbar-width: thin;
  scrollbar-color: #7b4fff rgba(255, 255, 255, 0.03);
}

.bingo-in-game-page::-webkit-scrollbar {
  width: 4px;
}
.bingo-in-game-page::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.03);
  border-radius: 10px;
}
.bingo-in-game-page::-webkit-scrollbar-thumb {
  background: linear-gradient(180deg, #7b4fff 0%, #2af598 45%, #009efd 100%);
  border-radius: 10px;
  box-shadow: 0 0 6px rgba(42, 245, 152, 0.45);
}
.bingo-in-game-page::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(180deg, #9d6fff 0%, #3fffa8 45%, #33b8ff 100%);
  box-shadow: 0 0 10px rgba(42, 245, 152, 0.7);
}
.bingo-in-game-page::-webkit-scrollbar-button {
  display: none;
}

/* Estilos para los containers principales */
.position-relative {
  position: relative;
}

#cont-last-chips {
  min-height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
}

#loader-last-chips {
  width: 30px;
  height: 30px;
  border: 3px solid rgba(255, 255, 255, 0.1);
  border-top: 3px solid #2af598;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

#text-check-bingo {
  position: absolute;
  bottom: -25px;
  left: 50%;
  transform: translateX(-50%);
  font-size: 14px;
  color: #2af598;
  text-align: center;
  white-space: nowrap;
}

/* Estilos para el container de información del bingo */
#container-bingo-info {
  background: rgba(0, 0, 0, 0.3);
  border-radius: 10px;
  border: 1px solid rgba(42, 245, 152, 0.2);
  backdrop-filter: blur(10px);
}

#cont-prize-pool-info {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

#container-btn-options {
  display: flex;
}

.btn-group-vertical {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.btn-group-vertical .btn {
  padding: 8px 12px;
  font-size: 12px;
  border-radius: 5px;
  background: rgba(0, 0, 0, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: #fff;
  transition: all 0.3s ease;
}

.btn-group-vertical .btn:hover:not(:disabled) {
  background: rgba(42, 245, 152, 0.2);
  border-color: rgba(42, 245, 152, 0.4);
}

#cont-prize-pool-info-amount {
  flex: 1;
  text-align: center;
}

#cont-prize-pool-type {
  font-size: 12px;
  color: #4a6696;
  text-transform: uppercase;
  letter-spacing: 1px;
}

#cont-prize-pool-amount {
  font-size: 24px;
  font-weight: bold;
  color: #2af598;
  margin-top: 5px;
}

#prize-pool-currency-sym {
  font-size: 16px;
  color: #7b4fff;
}

#cont-bingo-info-extras {
  margin-top: 10px;
  text-align: center;
}

#cont-game-mode-opcs {
  display: flex;
  justify-content: center;
  gap: 20px;
  flex-wrap: wrap;
}

#container-round {
  font-size: 14px;
  color: #e0eaff;
}

#num-round {
  color: #2af598;
  font-weight: bold;
}

#text-game-mode {
  font-size: 14px;
  color: #009efd;
  font-weight: bold;
}

/* Responsive */
@media (max-width: 768px) {
  .bingo-in-game-page {
    padding: 15px 10px 40px;
  }
  
  #cont-prize-pool-info {
    flex-direction: column;
    gap: 15px;
  }
  
  #container-btn-options {
    order: 2;
  }
  
  #cont-prize-pool-info-amount {
    order: 1;
  }
  
  #cont-game-mode-opcs {
    flex-direction: column;
    gap: 8px;
  }
}
</style>
