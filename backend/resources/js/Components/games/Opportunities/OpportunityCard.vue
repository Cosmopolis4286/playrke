<script setup lang="ts">
import { computed } from 'vue'
import type { Opportunity } from '@js/types/games/opportunity'

const props = defineProps<{
  opportunity: Opportunity
}>()

const gameIcon: Record<string, string> = {
  bingo: 'fa-th',
  trivia: 'fa-brain',
}

const gameGradient: Record<string, string> = {
  bingo: 'linear-gradient(135deg, #667eea, #764ba2)',
  trivia: 'linear-gradient(135deg, #11998e, #38ef7d)',
}

const startsInLabel = computed(() => {
  if (!props.opportunity.starts_at) return null
  const diffMs = props.opportunity.starts_at * 1000 - Date.now()
  if (diffMs <= 0) return 'Ya comenzó'
  const minutes = Math.round(diffMs / 60000)
  if (minutes < 1) return 'Comienza ya'
  if (minutes < 60) return `Comienza en ${minutes} min`
  const hours = Math.round(minutes / 60)
  return `Comienza en ${hours} h`
})

function formatAmount(amount: number | null | undefined, symbol: string | null | undefined): string {
  if (amount === null || amount === undefined) return '—'
  return `${symbol ?? ''}${amount.toLocaleString('es-VE', { maximumFractionDigits: 2 })}`
}
</script>

<template>
  <div class="opp-card">
    <div class="opp-card__badge" :style="{ background: gameGradient[opportunity.game] ?? '#667eea' }">
      <i class="fas" :class="gameIcon[opportunity.game] ?? 'fa-gamepad'"></i>
      {{ opportunity.game_label }}
    </div>

    <h3 class="opp-card__title">{{ opportunity.title ?? opportunity.room_code ?? 'Sala' }}</h3>

    <div class="opp-card__meta">
      <div class="opp-card__meta-item">
        <span class="opp-card__meta-label">Entrada</span>
        <span class="opp-card__meta-value">{{ formatAmount(opportunity.entry, opportunity.entry_currency_symbol ?? opportunity.currency_symbol) }}</span>
      </div>
      <div class="opp-card__meta-item">
        <span class="opp-card__meta-label">{{ opportunity.game === 'bingo' ? 'Premio' : 'Premio' }}</span>
        <span class="opp-card__meta-value opp-card__meta-value--prize">{{ formatAmount(opportunity.prize, opportunity.currency_symbol) }}</span>
      </div>
    </div>

    <div class="opp-card__footer">
      <span class="opp-card__players">
        <i class="fas fa-users"></i>
        {{ opportunity.total_players }}<template v-if="opportunity.max_players">/{{ opportunity.max_players }}</template>
      </span>
      <span v-if="startsInLabel" class="opp-card__starts">
        <i class="fas fa-clock"></i> {{ startsInLabel }}
      </span>
    </div>
  </div>
</template>

<style scoped>
.opp-card {
  background: var(--ds-surface);
  border: 1px solid var(--ds-border);
  border-radius: 14px;
  padding: 1.1rem 1.15rem;
  display: flex;
  flex-direction: column;
  gap: 0.7rem;
  transition: transform 0.15s, box-shadow 0.15s;
}
.opp-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.14);
}

.opp-card__badge {
  align-self: flex-start;
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.2rem 0.65rem;
  border-radius: 20px;
  color: #fff;
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.opp-card__title {
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--ds-text);
  margin: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.opp-card__meta {
  display: flex;
  gap: 1rem;
}
.opp-card__meta-item { display: flex; flex-direction: column; gap: 0.15rem; }
.opp-card__meta-label { font-size: 0.68rem; color: var(--ds-text-2); text-transform: uppercase; letter-spacing: 0.03em; }
.opp-card__meta-value { font-size: 0.95rem; font-weight: 700; color: var(--ds-text); }
.opp-card__meta-value--prize { color: #f7971e; }

.opp-card__footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-top: 0.6rem;
  border-top: 1px solid var(--ds-border);
  font-size: 0.76rem;
  color: var(--ds-text-2);
}
.opp-card__players, .opp-card__starts { display: flex; align-items: center; gap: 0.3rem; }
</style>
