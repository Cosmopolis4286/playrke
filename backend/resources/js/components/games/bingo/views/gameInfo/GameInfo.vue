<!-- resources/js/games/bingo/views/GameInfo.vue — Spectator panel -->
<template>
  <div class="gi-wrap">

    <!-- ── Header: ESPECTADOR badge + ronda + estado conexión ── -->
    <div class="gi-header">
      <div class="gi-header__left">
        <div class="gi-badge">
          <span class="gi-badge__dot" :class="{ 'gi-badge__dot--live': numbers.length > 0 }" />
          <span>MODO ESPECTADOR</span>
        </div>
        <div v-if="data.round" class="gi-round">
          <i class="fas fa-sync-alt" /> Ronda {{ data.round }}
        </div>
      </div>
      <div class="gi-header__right">
        <div v-if="numbers.length > 0" class="gi-pct">
          {{ pct }}% completado
        </div>
      </div>
    </div>

    <!-- ── Board ── -->
    <BingoBoard
      :calledNumbers="numbers"
      :currentNumber="currentNumber"
      :variant="data.gameVariant ?? 'BINGO_75'"
      :drawTimestamps="drawTimestamps"
    />

    <!-- ── Admin caller ── -->
    <div class="gi-caller">
      <NumberCaller :calledNumbers="numbers" @callNumber="callNumberFromInput" />
    </div>

  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from "vue";

import axios from '@js/plugins/axios';
import echo  from "@js/plugins/echo";

import BingoBoard   from "@js/Components/Games/Bingo/views/gameInfo/BingoBoard.vue";
import NumberCaller from "@js/Components/Games/Bingo/views/gameInfo/NumberCaller.vue";

const VARIANT_TOTALS = { BINGO_75: 75, BINGO_90: 90, BINGO_80: 80, BINGO_30: 30 };

const props = defineProps({
  data: { type: Object, required: true },
});

const numbers        = ref([...props.data.numbers]);
const currentNumber  = ref(null);
const roomId         = ref(props.data.roomId ?? 1);
// number → ISO timestamp string, populated as chips arrive via WebSocket
const drawTimestamps = ref({});

let channel = null;

const totalNumbers = computed(() => VARIANT_TOTALS[props.data.gameVariant] ?? 75);
const pct = computed(() => totalNumbers.value > 0
  ? Math.round((numbers.value.length / totalNumbers.value) * 100)
  : 0
);

// ── WebSocket ─────────────────────────────────────────────────────────────────
const subscribeToChannel = (id) => {
  channel = echo.channel(`bingo.room.${id}`);
  channel.listen(".BingoNumberCalled", (event) => {
    if (!numbers.value.includes(event.number)) {
      numbers.value.push(event.number);
      // Record exact time this chip arrived in the browser
      drawTimestamps.value = { ...drawTimestamps.value, [event.number]: new Date().toISOString() };
    }
    currentNumber.value = event.number;
  });
};

const unsubscribeFromChannel = (id) => {
  if (!id) return;
  echo.leave(`bingo.room.${id}`);
  channel = null;
};

// ── Lifecycle ─────────────────────────────────────────────────────────────────
onMounted(async () => {
  try {
    const res = await axios.get(window.route("bingo.called-numbers", roomId.value));
    numbers.value = res.data ?? [];
    // Pre-existing numbers don't have timestamps — leave them empty (will show "—")
  } catch (error) {
    console.error("❌ Error al cargar números anteriores:", error);
  }
  subscribeToChannel(roomId.value);
});

onBeforeUnmount(() => { unsubscribeFromChannel(roomId.value); });

watch(() => props.data.numbers, (n) => { numbers.value = [...n]; });

watch(() => props.data.roomId, (newId, oldId) => {
  if (newId && newId !== oldId) {
    unsubscribeFromChannel(oldId);
    roomId.value = newId;
    drawTimestamps.value = {};
    subscribeToChannel(newId);
  }
});

// ── Admin action ──────────────────────────────────────────────────────────────
async function callNumberFromInput(number) {
  try {
    await axios.post(window.route("bingo.callNumber", roomId.value), { number });
  } catch (err) {
    console.error("❌ Error al enviar número", err);
  }
}
</script>

<style scoped>
.gi-wrap {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 6px;
}

/* ── Header ── */
.gi-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  flex-wrap: wrap;
}
.gi-header__left  { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.gi-header__right { display: flex; align-items: center; gap: 8px; }

/* Espectador badge */
.gi-badge {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 3px 10px 3px 8px;
  border-radius: 20px;
  background: rgba(123,79,255,0.12);
  border: 1px solid rgba(123,79,255,0.25);
  font-size: 9px; font-weight: 800;
  color: #a78bfa; letter-spacing: 1px; text-transform: uppercase;
}
.gi-badge__dot {
  width: 7px; height: 7px; border-radius: 50%;
  background: #3a4a70;
  transition: background 0.3s, box-shadow 0.3s;
}
.gi-badge__dot--live {
  background: #a78bfa;
  box-shadow: 0 0 6px #a78bfa;
  animation: gi-blink 1.8s ease-in-out infinite;
}
@keyframes gi-blink { 0%,100% { opacity: 1; } 50% { opacity: 0.25; } }

/* Round badge */
.gi-round {
  font-size: 10px; font-weight: 700;
  color: #4a5a88;
  display: flex; align-items: center; gap: 4px;
}
.gi-round i { font-size: 9px; }

/* Percentage */
.gi-pct {
  font-size: 10px; font-weight: 700; color: #3a4a70;
}

/* ── Admin caller ── */
.gi-caller { margin-top: 2px; }
</style>
