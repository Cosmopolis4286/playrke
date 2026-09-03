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
      <button v-if="search" class="ep-search-clear" title="Limpiar búsqueda" @click="clearSearch">
        <i class="fas fa-times"></i>
      </button>
    </div>

    <select v-model="gameId" class="form-control ep-filter" @change="emitChange">
      <option value="">Todos los juegos</option>
      <option v-for="game in games" :key="game.id" :value="game.id">{{ formatGameName(game.name) }}</option>
    </select>

    <select v-model="status" class="form-control ep-filter" @change="emitChange">
      <option value="">Todos los estados</option>
      <option value="ACTIVE">Activos</option>
      <option value="INACTIVE">Inactivos</option>
      <option value="BLOCKED">Bloqueados</option>
    </select>

    <input
      v-model="sellerSearch"
      type="text"
      class="form-control ep-filter"
      placeholder="Filtrar por vendedor..."
      @input="onSearchInput"
    />

    <!-- Comercio/Agencia: solo si hay más de una opción disponible — un
         commerce con un único comercio propio (el caso normal) no necesita
         elegir entre opciones que ya sabe cuáles son; ver hierarchy, ya
         acotada por rol server-side (ExternalPlayerAdminService::hierarchyOptions()). -->
    <select v-if="hierarchy.commerces.length > 1" v-model="commerceId" class="form-control ep-filter" @change="onCommerceChange">
      <option value="">Todos los comercios</option>
      <option v-for="commerce in hierarchy.commerces" :key="commerce.id" :value="commerce.id">{{ commerce.name }}</option>
    </select>

    <select v-if="subsidiaryOptions.length" v-model="subsidiaryId" class="form-control ep-filter" @change="emitChange">
      <option value="">Todas las agencias</option>
      <option v-for="subsidiary in subsidiaryOptions" :key="subsidiary.id" :value="subsidiary.id">{{ subsidiary.name }}</option>
    </select>

    <button v-if="hasActiveFilters" class="btn btn-outline-secondary" title="Limpiar filtros" @click="resetFilters">
      <i class="fas fa-filter-circle-xmark mr-2"></i>Limpiar
    </button>

    <button class="btn btn-outline-primary" :disabled="loading" @click="emitChange">
      <i class="fas fa-sync-alt mr-2" :class="{ 'fa-spin': loading }"></i>Buscar
    </button>

    <button class="btn btn-outline-success" :disabled="loading" title="Exportar a CSV" @click="$emit('export')">
      <i class="fas fa-file-csv mr-2"></i>Exportar
    </button>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import type { ExternalPlayerFilters, GameOption, HierarchyOptions } from '@/js/types/roles/admin/external-player'

const props = defineProps<{
  loading: boolean
  games: GameOption[]
  hierarchy: HierarchyOptions
}>()

const emit = defineEmits<{
  (e: 'change', filters: ExternalPlayerFilters): void
  (e: 'change-debounced', filters: ExternalPlayerFilters): void
  (e: 'export'): void
}>()

const search = ref('')
const status = ref<ExternalPlayerFilters['status']>('')
const gameId = ref('')
const sellerSearch = ref('')
// Con un solo comercio disponible (caso normal de un usuario 'commerce') el
// select de comercio se oculta (ver template), pero igual se preselecciona
// aquí para que el select de agencias pueda calcular sus opciones — sin
// esto, un commerce nunca podría filtrar por su propia agencia.
const commerceId = ref(props.hierarchy.commerces.length === 1 ? props.hierarchy.commerces[0].id : '')
const subsidiaryId = ref('')

/** Agencias del comercio elegido — vacío si no se filtró por comercio (oculta el select por completo). */
const subsidiaryOptions = computed(() => {
  if (!commerceId.value) return []
  return props.hierarchy.subsidiaries.filter(s => s.commerce_id === commerceId.value)
})

function onCommerceChange() {
  // Cambiar de comercio invalida cualquier agencia elegida previamente
  // (pertenecía al comercio anterior).
  subsidiaryId.value = ''
  emitChange()
}

const hasActiveFilters = computed(() =>
  !!(search.value || status.value || gameId.value || sellerSearch.value || commerceId.value || subsidiaryId.value)
)

// Nombres de juego se guardan en mayúsculas (BINGO, TRIVIA) — formato legible.
function formatGameName(name: string): string {
  return name.charAt(0) + name.slice(1).toLowerCase()
}

function currentFilters(): ExternalPlayerFilters {
  return {
    search: search.value || undefined,
    status: status.value || undefined,
    game_id: gameId.value || undefined,
    seller_search: sellerSearch.value || undefined,
    commerce_id: commerceId.value || undefined,
    subsidiary_id: subsidiaryId.value || undefined,
  }
}

function emitChange() {
  emit('change', currentFilters())
}

function onSearchInput() {
  emit('change-debounced', currentFilters())
}

function clearSearch() {
  search.value = ''
  emitChange()
}

function resetFilters() {
  search.value = ''
  status.value = ''
  gameId.value = ''
  sellerSearch.value = ''
  commerceId.value = ''
  subsidiaryId.value = ''
  emitChange()
}
</script>

<style scoped>
.ep-toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.6rem;
  margin-bottom: 1rem;
}
.ep-search {
  position: relative;
  flex: 1 1 280px;
  display: flex;
  align-items: center;
}
.ep-search i.fa-search { position: absolute; left: 0.75rem; color: var(--ds-text-muted); font-size: 0.8rem; }
.ep-search .form-control { padding-left: 2.1rem; padding-right: 2rem; }
.ep-search-clear {
  position: absolute;
  right: 0.6rem;
  border: none;
  background: transparent;
  color: var(--ds-text-muted);
  font-size: 0.75rem;
  cursor: pointer;
  padding: 0.2rem;
}
.ep-search-clear:hover { color: var(--ds-text); }

.ep-filter { flex: 0 1 170px; }

@media (max-width: 992px) {
  .ep-toolbar { flex-direction: column; align-items: stretch; }
  .ep-filter { flex: 1; }
}
</style>
