import { ref } from 'vue'
import axios from 'axios'
import type { OpportunityFeed } from '@js/types/games/opportunity'

/**
 * Opportunity Feed: salas activas de Bingo y Trivia combinadas como
 * oportunidades para el jugador (entrada, premio, hora de inicio) — ver
 * progreso/estrategia-producto-plan.md, Fase 4.
 */
export function useOpportunityFeed(initialFeed: OpportunityFeed | null = null) {
  const feed    = ref<OpportunityFeed | null>(initialFeed)
  const loading = ref(false)
  const error   = ref<string | null>(null)

  async function refresh() {
    loading.value = true
    error.value   = null
    try {
      const { data } = await axios.get<OpportunityFeed>('/opportunities/fetch')
      feed.value = data
    } catch {
      error.value = 'No se pudieron cargar las oportunidades.'
    } finally {
      loading.value = false
    }
  }

  return { feed, loading, error, refresh }
}
