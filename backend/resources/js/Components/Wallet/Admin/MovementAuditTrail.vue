<script setup lang="ts">
import { ref } from 'vue'
import backendClient from '@js/plugins/axios'
import type { Movement, MovementAuditEntry } from '@js/types/wallet/admin-movement'

const props = defineProps<{
  detail: Movement
}>()

const eventLabel: Record<string, string> = {
  created: 'Creado',
  updated: 'Actualizado',
  deleted: 'Eliminado',
}

function formatEventLabel(event: string): string {
  return eventLabel[event] ?? event
}

const extraRows = ref<MovementAuditEntry[]>([])
const currentPage = ref(1)
const hasMore = ref(props.detail.has_more_audits ?? false)
const loadingMore = ref(false)

async function loadMore() {
  loadingMore.value = true
  try {
    const nextPage = currentPage.value + 1
    const { data } = await backendClient.get(`/wallet-movements/${props.detail.id}/audit-history`, {
      params: { page: nextPage },
    })
    extraRows.value = [...extraRows.value, ...data.data.rows]
    hasMore.value = data.data.has_more
    currentPage.value = data.data.current_page
  } catch {
    // silencioso — el botón sigue disponible para reintentar
  } finally {
    loadingMore.value = false
  }
}
</script>

<template>
  <div class="mv-detail__divider" />
  <div class="mv-audit">
    <p class="mv-detail__label mb-2"><i class="fas fa-history me-1"></i>Historial de auditoría</p>

    <ul v-if="(detail.recent_audits?.length ?? 0) > 0 || extraRows.length" class="mv-audit__list">
      <li v-for="audit in [...(detail.recent_audits ?? []), ...extraRows]" :key="audit.id" class="mv-audit__item">
        <span class="mv-audit__event">{{ formatEventLabel(audit.event) }}</span>
        <span class="mv-audit__meta">{{ audit.actor }} · {{ audit.date }}</span>
      </li>
    </ul>
    <p v-else class="mv-audit__empty">Sin actividad registrada.</p>

    <button
      v-if="hasMore"
      type="button"
      class="btn btn-sm btn-link mv-audit__more"
      :disabled="loadingMore"
      @click="loadMore"
    >
      <span v-if="loadingMore" class="spinner-border spinner-border-sm me-1"></span>
      <template v-else>Cargar más</template>
    </button>
  </div>
</template>

<style scoped>
.mv-detail__divider {
  height: 1px;
  background: var(--ds-border);
  margin: 0.75rem 0;
}

.mv-detail__label {
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--ds-text-muted);
  margin: 0;
}

.mv-audit__list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.mv-audit__item {
  display: flex;
  flex-direction: column;
  gap: 1px;
  font-size: 0.8rem;
  padding: 4px 0;
  border-bottom: 1px solid var(--ds-border);
}

.mv-audit__event { font-weight: 600; color: var(--ds-text); }
.mv-audit__meta  { font-size: 0.72rem; color: var(--ds-text-muted); }

.mv-audit__empty {
  font-size: 0.8rem;
  color: var(--ds-text-muted);
  margin: 0;
}

.mv-audit__more { padding-left: 0; }

.mb-2 { margin-bottom: 0.5rem; }
.me-1 { margin-right: 0.25rem; }
</style>
