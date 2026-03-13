<script setup lang="ts">
  import { computed, ref } from 'vue'
  import BaseTable from '@js/Components/Common/Table/BaseTable.vue'
  import type { Column, Row } from '@/js/types/common/table/column-row'
  import type { Currency } from '@js/types/currency'

  const props = defineProps<{
    currencies: Currency[]
    selectedIds: string[]
    hasPrivilegedRole?: boolean
  }>()

  defineEmits<{
    (e: 'selection-change', ids: string[]): void
  }>()

  /* =======================
   * Filtros
   * ======================= */
  const search   = ref('')
  const filterType   = ref<'' | 'FIAT' | 'CRYPTO' | 'TOKEN'>('')
  const filterStatus = ref<'' | 'ACTIVE' | 'INACTIVE'>('')

  const filtered = computed(() => {
    let list = props.currencies

    if (search.value) {
      const q = search.value.toLowerCase()
      list = list.filter(c =>
        c.name.toLowerCase().includes(q) ||
        c.iso_code.toLowerCase().includes(q) ||
        (c.description ?? '').toLowerCase().includes(q) ||
        (c.symbol ?? '').toLowerCase().includes(q)
      )
    }

    if (filterType.value)   list = list.filter(c => c.type   === filterType.value)
    if (filterStatus.value) list = list.filter(c => c.status === filterStatus.value)

    return list
  })

  /* =======================
   * Columnas
   * ======================= */
  const columns: Column[] = [
    { key: 'name',     label: 'Nombre' },
    { key: 'iso_code', label: 'Código ISO' },
    { key: 'symbol',   label: 'Símbolo' },
    { key: 'type',     label: 'Tipo' },
    { key: 'status',   label: 'Estado' },
    { key: 'country',  label: 'País' }
  ]

  /* =======================
   * Helpers de badge
   * ======================= */
  const typeBadge: Record<string, string> = {
    FIAT:   'badge-primary',
    CRYPTO: 'badge-warning',
    TOKEN:  'badge-info'
  }

  const typeLabel: Record<string, string> = {
    FIAT:   'Fiat',
    CRYPTO: 'Cripto',
    TOKEN:  'Token'
  }
</script>

<template>
  <!-- Barra de filtros -->
  <div class="row mb-2 mt-3 align-items-end">
    <div class="col-md-5">
      <input
        v-model="search"
        type="text"
        class="form-control form-control-sm"
        placeholder="Buscar por nombre, código, símbolo..."
      />
    </div>
    <div class="col-md-3">
      <select v-model="filterType" class="form-control form-control-sm">
        <option value="">Todos los tipos</option>
        <option value="FIAT">Fiat</option>
        <option value="CRYPTO">Criptomoneda</option>
        <option value="TOKEN">Token</option>
      </select>
    </div>
    <div class="col-md-3">
      <select v-model="filterStatus" class="form-control form-control-sm">
        <option value="">Todos los estados</option>
        <option value="ACTIVE">Activa</option>
        <option value="INACTIVE">Inactiva</option>
      </select>
    </div>
    <div class="col-md-1 text-right">
      <button
        v-if="search || filterType || filterStatus"
        class="btn btn-sm btn-outline-secondary"
        title="Limpiar filtros"
        @click="search = ''; filterType = ''; filterStatus = ''"
      >
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
  </div>

  <BaseTable
    table-id="currency-table"
    :columns="columns"
    :rows="filtered as Row[]"
    :selected-ids="selectedIds"
    selectable
    empty-message="No hay monedas registradas"
    @selection-change="$emit('selection-change', $event)"
  >
    <!-- Tipo -->
    <template #cell-type="{ row }">
      <span :class="['badge', typeBadge[row.type] ?? 'badge-light']">
        {{ typeLabel[row.type] ?? row.type }}
      </span>
    </template>

    <!-- Estado -->
    <template #cell-status="{ row }">
      <span :class="['badge', row.status === 'ACTIVE' ? 'badge-success' : 'badge-secondary']">
        {{ row.status === 'ACTIVE' ? 'Activa' : 'Inactiva' }}
      </span>
    </template>

    <!-- País -->
    <template #cell-country="{ row }">
      {{ row.country?.name ?? '—' }}
    </template>
  </BaseTable>
</template>
