<script setup lang="ts">
import type { GanadoresContent } from '@js/types/content/landing-section'

interface LeaderboardEntry {
  readonly rank: number
  readonly winner_name: string
  readonly total_amount: number
  readonly wins_count: number
}

defineProps<{
  content: GanadoresContent | null
  leaderboard: LeaderboardEntry[]
}>()
</script>

<template>
  <div class="leaderboard-grid">
    <div>
      <span class="eyebrow">{{ content?.eyebrow ?? 'Ranking de la semana' }}</span>
      <h2 class="verify-title">{{ content?.title ?? 'Los mejores jugadores de los últimos 7 días' }}</h2>
      <p class="verify-sub">
        {{ content?.paragraph ?? 'Suma de ganancias en dinero por jugador, actualizado cada pocos minutos. Los nombres se muestran parcialmente por privacidad.' }}
      </p>
    </div>
    <div class="lb-card">
      <div v-if="leaderboard.length" class="lb-list">
        <div
          v-for="(entry, i) in leaderboard"
          :key="entry.rank"
          class="lb-row reveal-child"
          :style="{ '--stagger-index': i }"
        >
          <span class="lb-rank">{{ String(entry.rank).padStart(2, '0') }}</span>
          <span class="lb-name">{{ entry.winner_name }}</span>
          <span class="lb-amt">+${{ entry.total_amount.toFixed(2) }}</span>
        </div>
      </div>
      <p v-else class="ticker-empty">Sin datos suficientes todavía esta semana</p>
    </div>
  </div>
</template>
