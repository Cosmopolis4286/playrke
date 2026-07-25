<template>
  <div class="ep-toolbar">
    <div class="ep-search">
      <i class="fas fa-search"></i>
      <input
        v-model="search"
        type="text"
        class="form-control"
        placeholder="Buscar por nombre, teléfono, identificación o link..."
        @input="onSearchInput"
      />
    </div>
    <select v-model="status" class="form-control ep-status-filter" @change="emitChange">
      <option value="">Todos los estados</option>
      <option value="ACTIVE">Activos</option>
      <option value="INACTIVE">Inactivos</option>
      <option value="BLOCKED">Bloqueados</option>
    </select>
    <button class="btn btn-outline-primary" :disabled="loading" @click="emitChange">
      <i class="fas fa-sync-alt mr-2" :class="{ 'fa-spin': loading }"></i>Buscar
    </button>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import type { ExternalPlayerFilters } from '@/js/types/roles/admin/external-player'

defineProps<{
  loading: boolean
}>()

const emit = defineEmits<{
  (e: 'change', filters: ExternalPlayerFilters): void
  (e: 'change-debounced', filters: ExternalPlayerFilters): void
}>()

const search = ref('')
const status = ref<ExternalPlayerFilters['status']>('')

function currentFilters(): ExternalPlayerFilters {
  return { search: search.value || undefined, status: status.value || undefined }
}

function emitChange() {
  emit('change', currentFilters())
}

function onSearchInput() {
  emit('change-debounced', currentFilters())
}
</script>

<style scoped>
.ep-toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 0.6rem;
  margin-bottom: 1rem;
}
.ep-search {
  position: relative;
  flex: 1 1 280px;
  display: flex;
  align-items: center;
}
.ep-search i { position: absolute; left: 0.75rem; color: var(--ds-text-muted); font-size: 0.8rem; }
.ep-search .form-control { padding-left: 2.1rem; }
.ep-status-filter { flex: 0 1 180px; }

@media (max-width: 768px) {
  .ep-toolbar { flex-direction: column; }
  .ep-status-filter { flex: 1; }
}
</style>
