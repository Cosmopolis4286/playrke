<script setup lang="ts">
/**
 * Opportunity Feed — salas activas de Bingo y Trivia combinadas como
 * oportunidades para el jugador. Ver progreso/estrategia-producto-plan.md,
 * Fase 4. No reemplaza los lobbies propios de cada juego (Games/Bingo/Room/
 * GameRooms.vue, Games/Trivia/Lobby.vue) — es una vista de descubrimiento
 * que enlaza hacia ellos.
 */
import { onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import OpportunityCard from '@js/Components/games/Opportunities/OpportunityCard.vue'
import { useOpportunityFeed } from '@js/composables/games/opportunities/useOpportunityFeed'
import type { OpportunityFeed, Opportunity } from '@js/types/games/opportunity'

const props = defineProps<{ feed: OpportunityFeed }>()

const { feed, loading, error, refresh } = useOpportunityFeed(props.feed)

onMounted(() => {
  // Refresca contra el backend al montar en vez de confiar solo en los props
  // iniciales — las salas cambian de estado con frecuencia (empiezan,
  // se llenan) y esta es una vista pensada para revisitarse seguido.
  refresh()
})

function goToRoom(opportunity: Opportunity) {
  if (opportunity.game === 'bingo') {
    router.visit(`/bingo/${opportunity.room_id}/lobby`)
  } else {
    router.visit(`/trivia/lobby`)
  }
}
</script>

<template>
  <div class="opportunities-page">
    <div class="opportunities-header">
      <div>
        <h1 class="opportunities-title"><i class="fas fa-fire"></i> Oportunidades</h1>
        <p class="opportunities-sub">Salas activas de Bingo y Trivia, todas en un solo lugar</p>
      </div>
      <button class="opportunities-refresh" :class="{ 'opportunities-refresh--spin': loading }" :disabled="loading" @click="refresh">
        <i class="fas fa-sync-alt"></i> Actualizar
      </button>
    </div>

    <div v-if="loading && !feed?.opportunities.length" class="opportunities-state">
      <span class="opportunities-spinner"></span><span>Buscando oportunidades...</span>
    </div>

    <div v-else-if="error" class="opportunities-state opportunities-state--error">
      <i class="fas fa-exclamation-triangle"></i> {{ error }}
    </div>

    <div v-else-if="feed?.opportunities.length" class="opportunities-grid">
      <button
        v-for="opp in feed.opportunities"
        :key="`${opp.game}-${opp.room_id}`"
        class="opportunities-grid__item"
        type="button"
        @click="goToRoom(opp)"
      >
        <OpportunityCard :opportunity="opp" />
      </button>
    </div>

    <div v-else class="opportunities-state opportunities-state--empty">
      <i class="fas fa-inbox"></i>
      <p>No hay oportunidades activas ahora mismo</p>
      <small>Vuelve a revisar en unos minutos</small>
    </div>
  </div>
</template>

<style scoped>
.opportunities-page { max-width: 1100px; margin: 0 auto; padding: 1.5rem; }

.opportunities-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1.5rem;
}
.opportunities-title { font-size: 1.3rem; font-weight: 700; color: var(--ds-text); margin: 0 0 0.25rem; display: flex; align-items: center; gap: 0.5rem; }
.opportunities-title i { color: #f7971e; }
.opportunities-sub { font-size: 0.85rem; color: var(--ds-text-2); margin: 0; }

.opportunities-refresh {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.6rem 1rem;
  border-radius: 10px;
  border: 1px solid var(--ds-border-2);
  background: var(--ds-surface-2);
  color: var(--ds-text);
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
}
.opportunities-refresh:hover:not(:disabled) { background: rgba(102,126,234,.12); border-color: #667eea; }
.opportunities-refresh:disabled { opacity: 0.6; cursor: not-allowed; }
.opportunities-refresh--spin i { animation: spin 0.7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.opportunities-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 1rem;
}
.opportunities-grid__item {
  all: unset;
  cursor: pointer;
  display: block;
}

.opportunities-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 4rem 1rem;
  color: var(--ds-text-2);
  font-size: 0.9rem;
  text-align: center;
}
.opportunities-state--error { color: #ef4444; }
.opportunities-state--empty i { font-size: 2rem; color: var(--ds-text-muted); }
.opportunities-state p { margin: 0; font-size: 0.95rem; color: var(--ds-text-2); }
.opportunities-state small { font-size: 0.8rem; }

.opportunities-spinner {
  width: 24px; height: 24px;
  border: 3px solid var(--ds-border-2);
  border-top-color: #f7971e;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
</style>
