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

<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import backendClient from '@js/plugins/axios'
import echo         from '@js/plugins/echo'
import BingoBoard   from '@js/Components/games/bingo/views/gameInfo/BingoBoard.vue'
import NumberCaller from '@js/Components/games/bingo/views/gameInfo/NumberCaller.vue'

declare const route: (name: string, params?: unknown) => string

const VARIANT_TOTALS: Record<string, number> = {
  BINGO_75: 75, BINGO_90: 90, BINGO_80: 80, BINGO_30: 30,
}

interface GameInfoData {
  numbers:     number[]
  roomId:      string | number
  gameVariant?: string
  round?:      number
}

const props = defineProps<{ data: GameInfoData }>()

const numbers        = ref<number[]>([...props.data.numbers])
const currentNumber  = ref<number | null>(null)
const roomId         = ref<string | number>(props.data.roomId ?? 1)
const drawTimestamps = ref<Record<number, string>>({})

const totalNumbers = computed(() => VARIANT_TOTALS[props.data.gameVariant ?? ''] ?? 75)
const pct = computed(() =>
  totalNumbers.value > 0 ? Math.round((numbers.value.length / totalNumbers.value) * 100) : 0
)

function subscribeToChannel(id: string | number) {
  echo.channel(`bingo.room.${id}`)
    .listen('.BingoNumberCalled', (event: { number: number }) => {
      if (!numbers.value.includes(event.number)) {
        numbers.value.push(event.number)
        drawTimestamps.value = { ...drawTimestamps.value, [event.number]: new Date().toISOString() }
      }
      currentNumber.value = event.number
    })
}

function unsubscribeFromChannel(id: string | number) {
  if (!id) return
  echo.leaveChannel(`bingo.room.${id}`)
}

onMounted(async () => {
  try {
    const res = await backendClient.get(route('bingo.called-numbers', roomId.value))
    numbers.value = res.data ?? []
  } catch {
    // silencioso — la tabla de números anteriores simplemente queda vacía
  }
  subscribeToChannel(roomId.value)
})

onBeforeUnmount(() => unsubscribeFromChannel(roomId.value))

watch(() => props.data.numbers, (n) => { numbers.value = [...n] })

watch(() => props.data.roomId, (newId, oldId) => {
  if (newId && newId !== oldId) {
    unsubscribeFromChannel(oldId)
    roomId.value         = newId
    drawTimestamps.value = {}
    subscribeToChannel(newId)
  }
})

async function callNumberFromInput(number: number) {
  await backendClient.post(route('bingo.callNumber', roomId.value), { number })
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
