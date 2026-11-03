<script setup lang="ts">
import { ref } from 'vue'
import backendClient from '@js/plugins/axios'
import type { ExternalPlayerDetail, ExternalPlayerPrizeEntry } from '@/js/types/roles/admin/external-player'

const props = defineProps<{
  detail: ExternalPlayerDetail
}>()

const statusLabel: Record<string, string> = {
  pending: 'Pendiente',
  completed: 'Completado',
  failed: 'Fallido',
  cancelled: 'Cancelado',
  waiting: 'Por entregar',
  processing: 'En revisión',
  claimed: 'Entregado',
  suspended: 'En revisión',
  disputed: 'En disputa',
}

function formatStatus(status: string): string {
  return statusLabel[status] ?? status
}

const extraRows = ref<ExternalPlayerPrizeEntry[]>([])
const currentPage = ref(1)
const hasMore = ref(props.detail.has_more_prizes ?? false)
const loadingMore = ref(false)

async function loadMore() {
  loadingMore.value = true
  try {
    const nextPage = currentPage.value + 1
    const { data } = await backendClient.get(`/admin/external-players/${props.detail.id}/prize-history`, {
      params: { page: nextPage },
    })
    extraRows.value = [...extraRows.value, ...data.rows]
    hasMore.value = data.has_more
    currentPage.value = data.current_page
  } catch {
    // silencioso — el botón sigue disponible para reintentar
  } finally {
    loadingMore.value = false
  }
}
</script>

<template>
  <div class="ep-detail__divider" />
  <div class="ep-activity">
    <p class="ep-detail__label mb-2"><i class="fas fa-trophy me-1"></i>Premios ganados</p>

    <ul v-if="(detail.recent_prizes?.length ?? 0) > 0 || extraRows.length" class="ep-activity__list">
      <li v-for="row in [...(detail.recent_prizes ?? []), ...extraRows]" :key="row.id" class="ep-activity__item">
        <span class="ep-activity__amount">{{ row.amount.toFixed(2) }}</span>
        <span class="ep-activity__meta">{{ formatStatus(row.status) }} · {{ row.date }}<template v-if="row.method"> · {{ row.method }}</template></span>
      </li>
    </ul>
    <p v-else class="ep-activity__empty">Sin premios registrados.</p>

    <button
      v-if="hasMore"
      type="button"
      class="btn btn-sm btn-link ep-activity__more"
      :disabled="loadingMore"
      @click="loadMore"
    >
      <span v-if="loadingMore" class="spinner-border spinner-border-sm me-1"></span>
      <template v-else>Cargar más</template>
    </button>
  </div>
</template>

<style scoped>
.ep-detail__divider {
  height: 1px;
  background: var(--ds-border);
  margin: 0.75rem 0;
}

.ep-detail__label {
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--ds-text-muted);
  margin: 0;
}

.ep-activity__list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.ep-activity__item {
  display: flex;
  flex-direction: column;
  gap: 1px;
  font-size: 0.8rem;
  padding: 4px 0;
  border-bottom: 1px solid var(--ds-border);
}

.ep-activity__amount { font-weight: 600; color: var(--ds-text); }
.ep-activity__meta   { font-size: 0.72rem; color: var(--ds-text-muted); }

.ep-activity__empty {
  font-size: 0.8rem;
  color: var(--ds-text-muted);
  margin: 0;
}

.ep-activity__more { padding-left: 0; }

.mb-2 { margin-bottom: 0.5rem; }
.me-1 { margin-right: 0.25rem; }
</style>
