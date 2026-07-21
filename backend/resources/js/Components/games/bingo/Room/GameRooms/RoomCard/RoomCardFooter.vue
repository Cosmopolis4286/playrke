<script setup lang="ts">
import { computed } from 'vue'

interface PlayerPreview { initial: string }

const props = defineProps<{
  variantLabel: string
  variantBalls: number
  cardPriceFormatted: string
  sym: string
  minEntryCostFormatted: string | null
  minCardsToStart: number | null
  playersPreview: PlayerPreview[]
  extraPlayersCount: number
  totalPlayers: number
  playerCardCount: number
  maxPlayers?: number | null
}>()

// "X/X" (actual/máximo) cuando la sala define un cupo; si no, solo el total
const playersLabel = computed(() =>
  props.maxPlayers ? `${props.totalPlayers}/${props.maxPlayers}` : `${props.totalPlayers}`
)
const playersTooltip = computed(() =>
  props.maxPlayers
    ? `${props.totalPlayers} de ${props.maxPlayers} jugadores — cupo máximo de la sala`
    : `${props.totalPlayers} jugador${props.totalPlayers !== 1 ? 'es' : ''} en esta sala`
)
const isFull = computed(() =>
  props.maxPlayers != null && props.totalPlayers >= props.maxPlayers
)
</script>

<template>
  <div class="rc__footer">
    <div class="rc__price-block">
      <span
        v-tooltip="`${variantLabel} · ${variantBalls} bolas en total`"
        class="rc__price"
      >
        <i class="fas fa-ticket-alt" />{{ cardPriceFormatted }} {{ sym }}
      </span>
      <span
        v-if="minEntryCostFormatted"
        v-tooltip="`Costo estimado para alcanzar el mínimo de ${minCardsToStart} cartones`"
        class="rc__price-min"
      >
        desde {{ minEntryCostFormatted }} {{ sym }}
      </span>
    </div>

    <div class="rc__stats">
      <!-- Prueba social: iniciales anónimas de jugadores ya en la sala -->
      <div
        v-if="playersPreview.length > 0"
        v-tooltip="playersTooltip"
        class="rc__avatars"
      >
        <span
          v-for="(p, i) in playersPreview"
          :key="i"
          class="rc__avatar"
          :style="{ zIndex: playersPreview.length - i }"
        >{{ p.initial }}</span>
        <span v-if="extraPlayersCount > 0" class="rc__avatar rc__avatar--more">+{{ extraPlayersCount }}</span>
        <span v-if="maxPlayers" class="rc__avatars-cap" :class="{ 'rc__avatars-cap--full': isFull }">/{{ maxPlayers }}</span>
      </div>
      <span
        v-else
        v-tooltip="playersTooltip"
        class="rc__stat"
        :class="{ 'rc__stat--mine': playerCardCount > 0, 'rc__stat--full': isFull }"
      >
        <i class="fas fa-users" />
        <span>{{ playersLabel }}</span>
      </span>
      <span
        v-if="playerCardCount > 0"
        v-tooltip="`Ya compraste ${playerCardCount} cartón${playerCardCount !== 1 ? 'es' : ''} en esta sala`"
        class="rc__stat rc__stat--card-count"
      >
        <i class="fas fa-check-circle" />
        <span>{{ playerCardCount }}</span>
      </span>
    </div>
  </div>
</template>

<style scoped>
/* ── Footer: precio + stats ── */
.rc__footer {
  display: flex; align-items: flex-start; justify-content: space-between; gap: 6px;
  padding-top: 7px;
  border-top: 1px solid rgba(255,255,255,.08);
  flex-shrink: 0;
}

.rc__price-block {
  display: flex; flex-direction: column; gap: 1px;
  min-width: 0;
}

.rc__price {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 10.5px; font-weight: 700; letter-spacing: .2px;
  color: #7ec0ec;
  white-space: nowrap;
}
.rc__price i { font-size: 9px; color: #4a94cc; }

/* Costo total estimado para alcanzar el mínimo de cartones — ayuda a decidir sin abrir la sala */
.rc__price-min {
  font-size: 8.5px; font-weight: 600; letter-spacing: .1px;
  color: #4a6488;
  white-space: nowrap;
}

/* Avatares apilados — prueba social con iniciales anónimas */
.rc__avatars {
  display: flex; align-items: center;
}
.rc__avatar {
  display: inline-flex; align-items: center; justify-content: center;
  width: 18px; height: 18px; border-radius: 50%;
  margin-left: -6px;
  font-size: 8px; font-weight: 700; color: #cfe0f5;
  background: linear-gradient(145deg, #2a4068 0%, #16233f 100%);
  border: 1.5px solid #0c1230;
  box-shadow: 0 1px 3px rgba(0,0,0,.35);
}
.rc__avatar:first-child { margin-left: 0; }
.rc__avatar--more {
  color: #8aa0c0;
  background: rgba(255,255,255,.06);
  font-size: 7px;
}

/* Cupo máximo junto a los avatares — "/8" */
.rc__avatars-cap {
  margin-left: 3px;
  font-size: 9px; font-weight: 700;
  color: #4a6488;
}
.rc__avatars-cap--full { color: #ff9430; }

/* Stats: jugadores + cartones míos */
.rc__stats {
  display: flex; align-items: center; gap: 4px; flex-shrink: 0;
}
.rc__stat {
  display: inline-flex; align-items: center; gap: 3px;
  padding: 3px 7px; border-radius: 5px;
  font-size: 10px; font-weight: 700;
  color: #5a7ca0;
  background: rgba(255,255,255,.05);
  border: 1px solid rgba(255,255,255,.09);
  white-space: nowrap;
  transition: border-color .25s ease, background .25s ease;
}
.rc__stat i { font-size: 8.5px; color: #3a5878; }

/* jugadores cuando el jugador participa en la sala */
.rc__stat--mine {
  color: #6a8aac;
}

/* mis cartones */
.rc__stat--card-count {
  color: #a070e0;
  background: rgba(110,55,210,.1);
  border-color: rgba(110,55,210,.22);
}
.rc__stat--card-count i { color: #7840c0; }

/* sala llena — cupo máximo alcanzado */
.rc__stat--full {
  color: #ff9430;
  background: rgba(255,148,48,.08);
  border-color: rgba(255,148,48,.22);
}
.rc__stat--full i { color: #ff9430; }
</style>
