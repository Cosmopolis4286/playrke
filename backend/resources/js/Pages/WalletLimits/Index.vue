<script setup lang="ts">
  import { computed, ref } from 'vue'

  // ==========================================================================
  // Componentes
  // ==========================================================================

  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'
  import PageHeader from '@/js/Components/Common/PageHeader/PageHeader.vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'
  import WalletLimitForm from '@/js/Components/WalletLimit/WalletLimitForm.vue'
  import WalletLimitUsageModal from '@/js/Components/WalletLimit/WalletLimitUsageModal.vue'

  // ==========================================================================
  // Composables
  // ==========================================================================

  import { useCrudEventListener } from '@/js/composables/form/useCrudEventListener'
  import { useModalForm } from '@/js/composables/common/useModalForm'
  import { useWalletLimit } from '@/js/composables/walletLimit/useWalletLimit'

  // ==========================================================================
  // Types
  // ==========================================================================

  import type { WalletLimit, WalletLimitPageProps } from '@js/types/walletLimit'
  import type { Column } from '@js/types/common/table/column-row'
  import type { TableFilterInterface, TableFilterOption } from '@js/types/common/table/table-filter'
  import type { ExtraButton } from '@js/types/common/table/extra-buttons'

  // ==========================================================================
  // Props (Inertia)
  // ==========================================================================

  const props = defineProps<WalletLimitPageProps>()

  // ==========================================================================
  // Lógica de negocio
  // ==========================================================================

  const {
    walletLimitData,
    walletLimits,
    setInitialWalletLimits,
    create,
    edit,
    remove,
    toggleStatus,
    submit,
    usageSummary,
    isLoadingUsage,
    fetchUsageSummary
  } = useWalletLimit()

  setInitialWalletLimits(props.walletLimits)
  fetchUsageSummary()

  // ==========================================================================
  // Formato de montos: separador de miles + decimales según la moneda
  // ==========================================================================

  function formatAmount(value: number, decimals = 2): string {
    return new Intl.NumberFormat('es-VE', {
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals
    }).format(value)
  }

  function decimalsFor(row: WalletLimit): number {
    return row.currency?.decimal_places ?? 2
  }

  function symbolFor(row: WalletLimit): string {
    return row.currency?.symbol ?? '$'
  }

  /**
   * Un límite "Todas las monedas" aplica el mismo tope numérico por
   * separado a cada moneda activa que no tenga su propio límite específico.
   * Con más de una moneda en la plataforma (ej. USDT + VES, escalas muy
   * distintas), eso amerita un aviso — el admin puede no haberlo notado.
   */
  const hasMultipleCurrencies = computed(() => props.currencies.length > 1)

  // ==========================================================================
  // Filas de tabla: aplanan currency para mostrar el ISO code directamente
  // ==========================================================================

  const rows = computed(() =>
    walletLimits.value.map(limit => ({
      ...limit,
      currency_label: limit.currency ? limit.currency.iso_code : 'Todas'
    }))
  )

  // ==========================================================================
  // Modal de uso real (por fila)
  // ==========================================================================

  const showUsageModal = ref(false)
  const usageLimit = ref<WalletLimit | null>(null)

  const usageForSelected = computed(() =>
    usageLimit.value ? usageSummary.value[String(usageLimit.value.id)] ?? null : null
  )

  function openUsage(row: WalletLimit): void {
    usageLimit.value = row
    showUsageModal.value = true
  }

  /**
   * % de uso del período mensual, para el indicador rápido en la tabla.
   * En un límite propio de una moneda es directo. En el fallback compartido
   * (varias monedas con el mismo tope), NO se suman monedas distintas —
   * se toma la moneda con mayor % de consumo individual, que es el caso que
   * realmente le interesa al admin (cuál se acerca más a tocar el techo).
   */
  function usagePercent(row: WalletLimit): number | null {
    const usage = usageSummary.value[String(row.id)]
    if (!usage) return null

    if (!usage.is_shared_across_currencies) {
      if (usage.monthly.max <= 0) return null
      return Math.min(100, Math.round((usage.monthly.used / usage.monthly.max) * 100))
    }

    if (!usage.by_currency.length || usage.monthly.max <= 0) return null
    const worst = Math.max(...usage.by_currency.map(c => c.monthly))
    return Math.min(100, Math.round((worst / usage.monthly.max) * 100))
  }

  function usageBadgeClass(pct: number | null): string {
    if (pct === null) return 'badge-secondary'
    if (pct >= 90) return 'badge-danger'
    if (pct >= 70) return 'badge-warning'
    return 'badge-success'
  }

  // ==========================================================================
  // Modal + Form handler
  // ==========================================================================

  const {
    showModalForm,
    modalTitle,
    formRef: walletLimitFormRef,
    handleSubmit
  } = useModalForm(walletLimitData, submit, {
    defaultTitle: 'Límites de Billetera'
  })

  // ==========================================================================
  // CRUD global (eventos tabla)
  // ==========================================================================

  useCrudEventListener({
    create: () => {
      create()
      showModalForm.value = true
    },

    edit: async ids => {
      if (!ids || ids.length !== 1) return
      const ok = await edit(ids[0])
      if (ok) showModalForm.value = true
    },

    delete: async ids => {
      if (!ids) return
      for (const id of ids) await remove(id)
    },

    toggleStatus: async ids => {
      if (!ids) return
      for (const id of ids) await toggleStatus(id)
    }
  })

  // ==========================================================================
  // Columnas
  // ==========================================================================

  const columns: Column[] = [
    { key: 'currency_label',       label: 'Moneda' },
    { key: 'kyc_level',            label: 'Nivel KYC' },
    { key: 'deposit_range',        label: 'Depósito', searchable: false },
    { key: 'tx_range',             label: 'Retiro / tx', searchable: false, hideBelow: 'md' },
    { key: 'period_usage',         label: 'Uso de período (mes)', searchable: false, hideBelow: 'lg' },
    { key: 'is_active',            label: 'Estado' }
  ]

  // ==========================================================================
  // Filtros
  // ==========================================================================

  const filters = computed<readonly TableFilterInterface[]>(() => [
    {
      key: 'kyc_level',
      label: 'Nivel KYC',
      placeholder: 'Filtrar por nivel',
      options: [
        { value: '0', label: 'Nivel 0 — Sin verificar' },
        { value: '1', label: 'Nivel 1 — Básico' },
        { value: '2', label: 'Nivel 2 — Completo' }
      ] as TableFilterOption[]
    }
  ])

  // ==========================================================================
  // Botones extra
  // ==========================================================================

  const extraButtons: ExtraButton[] = [
    {
      icon: 'fas fa-exchange-alt',
      class: 'btn-secondary',
      show: false,
      action: 'toggleStatus'
    }
  ]

  function kycLabel(level: number): string {
    return level === 0 ? 'Nivel 0' : level === 1 ? 'Nivel 1' : 'Nivel 2'
  }
</script>

<template>
  <PageHeader
    icon="fa-scale-balanced"
    title="Límites de Billetera"
    subtitle="Montos mínimos y máximos de depósito y retiro, por moneda y nivel de verificación KYC."
    :breadcrumb-items="[
      { label: 'Dashboard', route: '/dashboard', icon: 'fas fa-home' },
      { label: 'Límites de Billetera' },
    ]"
  />

  <TableWithFilterBridge
    table-id="wallet-limit-table"
    title="Límites de Billetera"
    subtitle="Montos mínimos y máximos de depósito y retiro, por moneda y nivel de verificación KYC"
    :columns="columns"
    :rows="rows"
    :filters="filters"
    :extra-buttons="extraButtons"
    search-placeholder="Buscar por moneda..."
    show-create-button
    show-edit-button
    show-delete-button
    empty-message="No hay límites de billetera configurados"
  >
    <!-- Moneda -->
    <template #cell-currency_label="{ row }">
      <span class="badge" :class="row.currency_id ? 'badge-info' : 'badge-secondary'">
        {{ row.currency_label }}
      </span>
      <i
        v-if="!row.currency_id && hasMultipleCurrencies"
        class="fas fa-triangle-exclamation text-warning ms-1"
        :title="`Este tope aplica por separado a las ${props.currencies.length} monedas activas (escalas distintas: ${props.currencies.map(c => c.iso_code).join(', ')}). Considera límites específicos por moneda.`"
      ></i>
    </template>

    <!-- Nivel KYC -->
    <template #cell-kyc_level="{ row }">
      {{ kycLabel(row.kyc_level) }}
    </template>

    <!-- Depósito: rango mín–máx en una sola celda -->
    <template #cell-deposit_range="{ row }">
      <div class="wl-range">
        <span class="wl-range__value text-success">
          {{ symbolFor(row) }} {{ formatAmount(row.min_deposit, decimalsFor(row)) }}
        </span>
        <i class="fas fa-arrow-right wl-range__sep"></i>
        <span class="wl-range__value text-success fw-semibold">
          {{ symbolFor(row) }} {{ formatAmount(row.max_deposit, decimalsFor(row)) }}
        </span>
      </div>
    </template>

    <!-- Retiro por transacción: rango mín–máx -->
    <template #cell-tx_range="{ row }">
      <div class="wl-range">
        <span class="wl-range__value text-primary">
          {{ symbolFor(row) }} {{ formatAmount(row.min_per_transaction, decimalsFor(row)) }}
        </span>
        <i class="fas fa-arrow-right wl-range__sep"></i>
        <span class="wl-range__value text-primary fw-semibold">
          {{ symbolFor(row) }} {{ formatAmount(row.max_per_transaction, decimalsFor(row)) }}
        </span>
      </div>
    </template>

    <!-- Uso real del período mensual + acceso al detalle diario/semanal/mensual -->
    <template #cell-period_usage="{ row }">
      <button
        type="button"
        class="btn btn-sm btn-link wl-usage-btn p-0"
        :disabled="isLoadingUsage"
        @click.stop="openUsage(row)"
      >
        <span
          v-if="usagePercent(row) !== null"
          class="badge me-1"
          :class="usageBadgeClass(usagePercent(row))"
        >
          {{ usagePercent(row) }}%
        </span>
        <span v-else class="badge badge-secondary me-1">—</span>
        Ver uso
        <i class="fas fa-chart-line ms-1"></i>
      </button>
    </template>

    <!-- Estado -->
    <template #cell-is_active="{ row }">
      <span :class="['badge', row.is_active ? 'badge-success' : 'badge-secondary']">
        {{ row.is_active ? 'Activo' : 'Inactivo' }}
      </span>
    </template>
  </TableWithFilterBridge>

  <Modal
    v-model="showModalForm"
    :title="modalTitle"
    static
    show-confirm
    :on-submit="handleSubmit"
    :confirm-disabled="!(walletLimitFormRef?.isValid ?? false)"
  >
    <WalletLimitForm
      ref="walletLimitFormRef"
      v-model="walletLimitData"
      :currencies="props.currencies"
    />
  </Modal>

  <WalletLimitUsageModal
    v-model="showUsageModal"
    :limit="usageLimit"
    :usage="usageForSelected"
    :loading="isLoadingUsage"
  />
</template>

<style scoped>
.wl-range {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  white-space: nowrap;
  font-size: 0.85rem;
}

.wl-range__sep {
  font-size: 0.65rem;
  color: #9ca3af;
}

.wl-usage-btn {
  font-size: 0.85rem;
  text-decoration: none;
}

.wl-usage-btn:hover {
  text-decoration: underline;
}
</style>
