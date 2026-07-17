<script setup lang="ts">
import type { BingoGameRoom } from '@/js/types/games/bingo/room/game-rooms/bingo-game-room'
import type { BingoAvailableCountry } from '@/js/types/games/bingo/card/purchase-modal/bingo-purchase-modal'

defineProps<{
  selectedRoom: BingoGameRoom | null
  prizeFormatted: string
  isPlayer: boolean | null
  currencySymbol: string
  selectedCountry: BingoAvailableCountry | null
}>()
</script>

<template>
  <div v-if="selectedRoom" class="pm-room-banner">
    <img
      :src="`/img/games/bingo/estelar_fd.png`"
      alt="sala"
      class="pm-room-banner__img"
    />
    <div class="pm-room-banner__info">
      <span class="pm-room-banner__mode">
        <i class="fas fa-gamepad mr-1" />Cartón Lleno
      </span>
      <div class="pm-room-banner__prize">
        <span class="pm-room-banner__prize-num">{{ prizeFormatted }}</span>
        <span class="pm-room-banner__prize-sym">
          {{
            isPlayer
              ? currencySymbol
              : (selectedCountry?.currency_symbol ?? currencySymbol ?? 'USD')
          }}
        </span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.pm-room-banner {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 5px 14px;
  background: rgba(0, 10, 40, 0.5);
  border-bottom: 1px solid rgba(100, 140, 255, 0.08);
}

.pm-room-banner__img {
  width: 32px;
  height: 22px;
  object-fit: cover;
  border-radius: 5px;
  opacity: 0.85;
}

.pm-room-banner__info {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 8px;
}

.pm-room-banner__mode {
  font-size: 9px;
  color: #5578bb;
  text-transform: uppercase;
  letter-spacing: 0.6px;
}

.pm-room-banner__prize {
  display: flex;
  align-items: baseline;
  gap: 3px;
}

.pm-room-banner__prize-num {
  font-family: 'Anton', sans-serif;
  font-size: 15px;
  background: linear-gradient(135deg, #ffe566, #ffab00);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.pm-room-banner__prize-sym {
  font-size: 10px;
  font-weight: 700;
  color: #ffd060;
  -webkit-text-fill-color: #ffd060;
}
</style>
