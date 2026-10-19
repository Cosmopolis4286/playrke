<script setup lang="ts">
import { ref } from 'vue'
import type { BingoRoom } from '@js/types/games/bingo/room/bingo-room'
import { statusLabel, statusClass } from '@js/composables/games/bingo/room/useBingoRoomFormatters'

const props = defineProps<{
  room: BingoRoom
  scheduleInfo?: string
}>()

const copied = ref(false)

async function copyCode(): Promise<void> {
  const code = props.room.uuid_code ?? String(props.room.id)
  try {
    await navigator.clipboard.writeText(code)
    copied.value = true
    setTimeout(() => { copied.value = false }, 1500)
  } catch {
    // Sin permiso de portapapeles: el código sigue visible para copiarlo a mano.
  }
}
</script>

<template>
  <div class="details-header">
    <span class="badge badge-lg" :class="statusClass(room.status)">{{ statusLabel(room.status) }}</span>
    <button type="button" class="dh-code" :title="copied ? 'Copiado' : 'Copiar código de sala'" @click="copyCode">
      <span>{{ room.uuid_code ?? room.id }}</span>
      <i class="fas" :class="copied ? 'fa-check' : 'fa-copy'"></i>
    </button>
    <span v-if="scheduleInfo" class="dh-meta"><i class="fas fa-calendar-day"></i> {{ scheduleInfo }}</span>
    <span v-if="room.created_by?.username" class="dh-meta"><i class="fas fa-user"></i> {{ room.created_by.username }}</span>
    <span v-if="room.config?.in_live === 'Y'" class="badge badge-success">En Vivo</span>
    <span v-if="room.paid_prizes === 'Y'" class="badge badge-info">Premios Pagados</span>
    <span v-if="room.is_closed" class="badge badge-danger">Sala Cerrada</span>
  </div>
</template>

<style scoped>
.details-header {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.4rem 0.6rem;
  margin-bottom: 0.75rem;
}
.dh-code {
  display: inline-flex; align-items: center; gap: 0.35rem;
  padding: 0.15rem 0.5rem; border: 1px solid var(--ds-border); border-radius: 6px;
  background: var(--ds-surface-2); color: var(--ds-text);
  font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 0.78rem; font-weight: 700; cursor: pointer;
}
.dh-code i { font-size: 0.7rem; color: var(--ds-text-muted); }
.dh-meta { font-size: 0.75rem; color: var(--ds-text-muted); }
.dh-meta i { margin-right: 0.2rem; }

.badge {
  padding: 0.3rem 0.65rem;
  font-size: 0.72rem;
  font-weight: 600;
  border-radius: 0.25rem;
}
.badge-lg { font-size: 0.8rem; padding: 0.4rem 0.85rem; }

.badge-success   { background-color: #28a745; color: white; }
.badge-primary   { background-color: #007bff; color: white; }
.badge-info      { background-color: #17a2b8; color: white; }
.badge-warning   { background-color: #ffc107; color: #212529; }
.badge-danger    { background-color: #dc3545; color: white; }
.badge-secondary { background-color: #6c757d; color: white; }
.badge-dark      { background-color: #343a40; color: white; }
.badge-light     { background-color: var(--ds-surface-2); color: var(--ds-text); border: 1px solid var(--ds-border); }

</style>
