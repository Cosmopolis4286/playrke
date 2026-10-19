<script setup lang="ts">
  import { ref, computed, watch } from 'vue'
  import { router } from '@inertiajs/vue3'

  import PageHeader from '@/js/Components/Common/PageHeader/PageHeader.vue'
  import ExchangeRateTable from '@js/Components/ExchangeRate/ExchangeRateTable.vue'
  import ExchangeRateForm from '@/js/Components/ExchangeRate/ExchangeRateForm.vue'
  import TableActionButtons from '@/js/Components/Common/Table/TableActionButtons.vue'
  import TableFooterInfo from '@/js/Components/Common/Table/TableFooterInfo.vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'
  import NetworkExchangeRateWidget from '@/js/Components/Common/ExchangeRate/NetworkExchangeRateWidget.vue'

  import { useExchangeRate } from '@/js/composables/exchange-rate/useExchangeRate'
  import { useModalForm } from '@/js/composables/common/useModalForm'

  import type { ExchangeRatePageProps, UsedCurrency } from '@js/types/exchange-rate'

  /**
   * Props Inertia
   */
  const props = defineProps<ExchangeRatePageProps>()

  /**
   * Determina si el usuario gestiona la tasa OFICIAL (admin/superadmin/
   * treasurer) en vez de una tasa propia (commerce/customer) — sin el
   * límite de UX "una tasa por moneda ya usada" que aplica a estos últimos
   * en canCreate(), y sin necesidad de mostrar el ISO code junto al valor
   * en ExchangeRateTable (ya lo saben por columna "Moneda").
   *
   * Antes decía 'company' — typo/rol inexistente en el sistema (el prefix
   * real es 'commerce') — y omitía 'superadmin'/'treasurer', así que ningún
   * admin/superadmin/tesorero entraba nunca en este grupo pese a ser
   * exactamente a quienes debía aplicar.
   */
  const hasPrivilegedRole = computed(() =>
    ['admin', 'superadmin', 'treasurer'].includes(props.roleUser)
  )

  /**
   * Búsqueda/filtro client-side sobre el listado (BaseTable no trae
   * filtrado propio conectado en esta página, ver useTableFiltering — no se
   * usa acá porque el volumen de filas es pequeño, un catálogo de pocas
   * monedas, y no justifica esa infraestructura).
   */
  const searchQuery = ref('')
  const statusFilter = ref<string>('')

  const statusFilterOptions = computed(() => {
    const seen = new Set<string>()
    exchangeRates.value.forEach(r => seen.add(r.status))
    return Array.from(seen)
  })

  const filteredExchangeRates = computed(() => {
    return exchangeRates.value.filter(rate => {
      if (statusFilter.value && rate.status !== statusFilter.value) return false

      if (searchQuery.value.trim()) {
        const q = searchQuery.value.trim().toLowerCase()
        const currency = currencies.find(c => c.id === rate.currency_id)
        const haystack = [currency?.name, currency?.description, currency?.iso_code, rate.user_name]
          .filter(Boolean)
          .join(' ')
          .toLowerCase()
        if (!haystack.includes(q)) return false
      }

      return true
    })
  })

  const hasActiveFilters = computed(() => !!searchQuery.value.trim() || !!statusFilter.value)

  function clearFilters(): void {
    searchQuery.value = ''
    statusFilter.value = ''
  }

  /**
   * Stat cards resumen arriba de la tabla — total de tasas activas, cuántas
   * son automáticas (cronjob) vs. manuales, y la actualización más reciente
   * entre todas las monedas.
   */
  const summaryStats = computed(() => {
    const rates = exchangeRates.value
    const active = rates.filter(r => r.status === 'ACTIVE')
    const automatic = rates.filter(r => r.source === 'system').length
    const manual = rates.length - automatic

    const mostRecent = rates.reduce<string | null>((latest, r) => {
      if (!r.updated_at) return latest
      if (!latest || r.updated_at > latest) return r.updated_at
      return latest
    }, null)

    return {
      activeCount: active.length,
      automatic,
      manual,
      mostRecent,
    }
  })

  function formatDateTime(value: string | null): string {
    if (!value) return '—'
    const d = new Date(value)
    if (isNaN(d.getTime())) return value
    return d.toLocaleString('es-ES', {
      year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit',
    })
  }

  /**
   * Indica si es posible registrar una nueva tasa de cambio
   */
  const canCreate = computed(() => {
    if (hasPrivilegedRole.value) return true
    return currencies.length > 0 && usedCurrencies.value.length < currencies.length
  })

  /**
   * Composable de negocio para manejo de tasas
   */
  const {
    exchangeRateData,
    exchangeRates,
    setInitialExchangeRates,
    create,
    edit,
    toggleStatusMany,
    removeMany,
    updateExchangeRates,
    submit,
    toggleAutoUpdate,
  } = useExchangeRate(props.tokenId)

  // Inicializar exchangeRates con las props
  setInitialExchangeRates(props.exchangeRates)

  /**
   * IDs seleccionados en la tabla
   */
  const selectedIds = ref<string[]>([])

  /**
   * Propiedades y estado local
   */
  const currencies = props.currencies
  const usedCurrencies = ref<UsedCurrency[]>([])

  /**
   * Loading exclusivo para la acción "Actualizar tasas"
   * Vive en el padre porque aquí vive la operación async
   */
  const updatingExchangeRates = ref(false)

  /**
   * Estado para controlar actualización automática via cronjob
   */
  const autoUpdateEnabled = ref(props.autoUpdateEnabled)

  /**
   * Estado de la actualización automática vía BCV (solo VES)
   */
  const bcvAutoUpdateEnabled = ref(props.bcvAutoUpdateEnabled)

  /**
   * Indica si el usuario tiene registrada una tasa para VES (Bolívar venezolano)
   */
  const hasVesRate = computed(() =>
    exchangeRates.value.some(rate => {
      const currency = currencies.find(c => c.id === rate.currency_id)
      return currency?.iso_code === 'VES'
    })
  )

  /**
   * Genera las monedas usadas en las tasas para presentación
   */
  watch(
    exchangeRates,
    newRates => {
      usedCurrencies.value = newRates.map(rate => {
        const currency = currencies.find(c => c.id === rate.currency_id)

        return {
          id: rate.currency_id,
          name: currency?.name ?? 'Desconocido',
          description: currency?.description ?? 'Desconocido',
          iso_code: currency?.iso_code ?? 'Desconocido',
          type: currency?.type,
          country: currency?.country
            ? {
                id: currency.country.id,
                name: currency.country.name,
                iso_code: currency.country.iso_code_3
              }
            : null
        }
      })
    },
    { immediate: true }
  )

  /**
   * Composable para el modal y formulario
   */
  const {
    showModalForm,
    modalTitle,
    formRef: exchangeRateFormRef,
    handleSubmit
  } = useModalForm(exchangeRateData, submit, {
    defaultTitle: 'Tasa de Cambio'
  })

  /**
   * Abre el modal en modo creación, inicializando datos
   */
  function handleCreate(): void {
    create()
    showModalForm.value = true
  }

  /**
   * Abre el modal en modo edición cargando datos existentes
   *
   * @param id Identificador de la tasa a editar
   */
  async function handleEdit(id: string): Promise<void> {
    await edit(id)
    showModalForm.value = true
  }

  /**
   * Alterna el status de las tasas seleccionadas — una sola confirmación
   * agregada para todo el lote (antes: una confirmación por cada fila
   * seleccionada, ver toggleStatusMany en useExchangeRate).
   */
  async function handleChangeStatus(ids: string[]): Promise<void> {
    await toggleStatusMany(ids)
    selectedIds.value = []
  }

  /**
   * Elimina las tasas seleccionadas — una sola confirmación agregada para
   * todo el lote (antes: una confirmación por cada fila seleccionada, ver
   * removeMany en useExchangeRate).
   */
  async function handleDelete(ids: string[]): Promise<void> {
    await removeMany(ids)
    selectedIds.value = []
  }

  /**
   * Ejecuta la sincronización masiva de tasas de cambio
   * y controla el loading del botón "Actualizar".
   */
  async function handleUpdateExchangeRates(): Promise<void> {
    if (updatingExchangeRates.value) return

    updatingExchangeRates.value = true
    try {
      await updateExchangeRates()
    } finally {
      updatingExchangeRates.value = false
    }
  }

  /**
   * Maneja el cambio del switch de actualización automática
   */
  async function handleAutoUpdateToggle(enabled: boolean): Promise<void> {
    const success = await toggleAutoUpdate(enabled)
    if (!success) {
      // Revertir el estado si hay error
      autoUpdateEnabled.value = !enabled
    }
  }

  /**
   * Navega a la vista histórica de la tasa seleccionada.
   *
   * router.visit() de Inertia (no window.location.href): la versión
   * anterior forzaba una recarga de página completa, rompiendo la SPA y
   * perdiendo el estado de este composable innecesariamente — la ruta ya
   * es una página Inertia (ExchangeRateController::historical()), no hay
   * ninguna razón para no navegarla como tal.
   */
  /**
   * Comercializadora: comparación "tu tasa vs. la oficial" arriba de la
   * tabla. Se recarga (key) cada vez que cambian sus tasas.
   */
  const widgetKey = ref(0)
  watch(exchangeRates, () => { widgetKey.value++ }, { deep: true })

  function goHistorical(): void {
    if (!selectedIds.value.length) return

    const selected = exchangeRates.value.find(er => String(er.id) === selectedIds.value[0])
    if (!selected?.currency_id) return

    router.visit(`/exchange-rate/historical/${selected.currency_id}`, {
      data: props.tokenId ? { tokenId: props.tokenId } : {},
    })
  }

</script>
<template>
  <PageHeader
    icon="fa-money-bill-trend-up"
    title="Tasas de Cambio"
    :subtitle="hasPrivilegedRole ? 'Tasa oficial de la plataforma · por 1 USD' : 'Tu tasa propia por moneda · por 1 USD'"
    :breadcrumb-items="[
      { label: 'Dashboard', route: '/dashboard', icon: 'fas fa-home' },
      { label: 'Tasas de Cambio' },
    ]"
  >
    <template v-if="exchangeRates.length > 0" #actions>
      <div class="er-kpis">
        <span class="er-kpi"><strong>{{ summaryStats.activeCount }}</strong> activas</span>
        <span v-if="hasPrivilegedRole" class="er-kpi" title="Automáticas / manuales">
          <i class="fas fa-robot"></i><strong>{{ summaryStats.automatic }}</strong> / {{ summaryStats.manual }}
        </span>
        <span class="er-kpi" title="Última actualización">
          <i class="fas fa-clock"></i>{{ formatDateTime(summaryStats.mostRecent) }}
        </span>
      </div>
    </template>
  </PageHeader>

  <!-- Comercializadora / cliente: con qué tasa venden hoy sus salas -->
  <section v-if="!hasPrivilegedRole" class="er-panel">
    <header class="er-panel__head">
      <h3 class="er-panel__title">Tasas vigentes en tus ventas</h3>
      <span
        class="er-panel__help"
        title="Las ventas de tus salas usan tu tasa propia de cada moneda; si no la registras (o la desactivas), se usa la oficial de la plataforma."
      >
        <i class="fas fa-circle-question"></i> Propia o, si no hay, la oficial
      </span>
    </header>
    <NetworkExchangeRateWidget :key="widgetKey" bare hide-manage-link />
  </section>

  <section class="er-panel er-panel--flush">
    <!-- Barra de herramientas: búsqueda, filtro, auto-actualización y acciones en una línea -->
    <div class="er-toolbar">
      <h3 class="er-panel__title er-toolbar__title">{{ hasPrivilegedRole ? 'Tasas oficiales' : 'Mis tasas' }}</h3>

      <template v-if="exchangeRates.length > 0">
        <div class="er-search">
          <i class="fas fa-search"></i>
          <input v-model="searchQuery" type="search" class="form-control form-control-sm" placeholder="Buscar moneda…" />
        </div>
        <select v-model="statusFilter" class="form-control form-control-sm er-status">
          <option value="">Todos</option>
          <option v-for="status in statusFilterOptions" :key="status" :value="status">{{ status }}</option>
        </select>
        <div
          class="custom-control custom-switch er-auto"
          :title="autoUpdateEnabled && hasVesRate && bcvAutoUpdateEnabled ? 'VES se actualiza aparte con la tasa del BCV' : 'Actualizar las tasas automáticamente'"
        >
          <input
            id="er-auto-update"
            v-model="autoUpdateEnabled"
            type="checkbox"
            class="custom-control-input"
            @change="handleAutoUpdateToggle(autoUpdateEnabled)"
          />
          <label class="custom-control-label" for="er-auto-update">
            Automática<span v-if="autoUpdateEnabled && hasVesRate && bcvAutoUpdateEnabled" class="er-auto__bcv">· VES por BCV</span>
          </label>
        </div>
      </template>

      <div class="er-toolbar__actions">
        <TableActionButtons
          :selected-ids="selectedIds"
          :show-create-button="canCreate"
          show-edit-button
          show-delete-button
          has-extra-actions
          @create="handleCreate"
          @edit="handleEdit"
          @delete="handleDelete"
        >
          <template #action-buttons="{ selectedIds: ids }">
            <button
              v-if="ids.length > 0"
              class="btn btn-sm btn-secondary"
              title="Activar / desactivar"
              @click="handleChangeStatus(ids)"
            >
              <i class="fa-solid fa-repeat"></i>
            </button>

            <button
              v-if="ids.length === 1"
              class="btn btn-sm btn-outline-primary"
              type="button"
              title="Ver histórico"
              @click="goHistorical"
            >
              <i class="fa-solid fa-chart-line"></i>
            </button>

            <button
              class="btn btn-sm btn-outline-secondary"
              type="button"
              :disabled="updatingExchangeRates || exchangeRates.length === 0"
              :title="exchangeRates.length === 0 ? 'Registra al menos una tasa para consultar valores' : 'Consultar valores actuales'"
              @click="handleUpdateExchangeRates"
            >
              <span v-if="updatingExchangeRates" class="spinner-border spinner-border-sm" aria-hidden="true"></span>
              <i v-else class="fa-solid fa-rotate"></i>
            </button>
          </template>
        </TableActionButtons>
      </div>
    </div>

    <ExchangeRateTable
      :usedCurrencies="usedCurrencies"
      :exchange-rates="filteredExchangeRates"
      :has-privileged-role="hasPrivilegedRole"
      :selected-ids="selectedIds"
      @selection-change="ids => (selectedIds = ids)"
    />

    <div class="er-footer">
      <TableFooterInfo
        :filtered-count="filteredExchangeRates.length"
        :total-count="exchangeRates.length"
        :selected-count="selectedIds.length"
      />
      <button
        v-if="hasActiveFilters && filteredExchangeRates.length === 0"
        type="button"
        class="btn btn-link btn-sm p-0"
        @click="clearFilters"
      >
        Limpiar filtros
      </button>
    </div>
  </section>

  <Modal
    v-model="showModalForm"
    :title="modalTitle"
    static
    show-confirm
    :on-submit="handleSubmit"
    :confirm-disabled="!(exchangeRateFormRef?.isValid ?? false)"
  >
    <ExchangeRateForm
      ref="exchangeRateFormRef"
      v-model="exchangeRateData"
      :currencies="currencies"
      :usedCurrencies="usedCurrencies"
      :bcv-auto-update-enabled="bcvAutoUpdateEnabled"
      @update:bcv-auto-update-enabled="bcvAutoUpdateEnabled = $event"
    />
  </Modal>
</template>

<style scoped>
/* KPIs compactos en la cabecera */
.er-kpis { display: flex; flex-wrap: wrap; gap: 0.4rem; }
.er-kpi {
  display: inline-flex; align-items: center; gap: 0.3rem;
  padding: 0.2rem 0.6rem;
  border: 1px solid var(--ds-border); border-radius: 999px;
  background: var(--ds-surface);
  font-size: 0.75rem; color: var(--ds-text-2); white-space: nowrap;
}
.er-kpi i { color: var(--ds-text-muted); font-size: 0.7rem; }
.er-kpi strong { color: var(--ds-text); font-variant-numeric: tabular-nums; }

/* Paneles */
.er-panel {
  margin-bottom: 0.85rem;
  padding: 0.75rem 0.85rem;
  border: 1px solid var(--ds-border);
  border-radius: 12px;
  background: var(--ds-surface);
}
.er-panel--flush { padding: 0; overflow: hidden; }
.er-panel__head { display: flex; align-items: baseline; justify-content: space-between; gap: 0.75rem; margin-bottom: 0.55rem; }
.er-panel__title { margin: 0; font-size: 0.85rem; font-weight: 700; color: var(--ds-text); }
.er-panel__help { font-size: 0.72rem; color: var(--ds-text-muted); cursor: help; white-space: nowrap; }

/* Barra de herramientas */
.er-toolbar {
  display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;
  padding: 0.55rem 0.85rem;
  border-bottom: 1px solid var(--ds-border);
}
.er-toolbar__title { margin-right: 0.25rem; }
.er-toolbar__actions { margin-left: auto; }
.er-toolbar__actions :deep(.mb-3),
.er-toolbar__actions :deep(.mb-2) { margin-bottom: 0 !important; }

.er-search { position: relative; width: 190px; }
.er-search i { position: absolute; left: 0.55rem; top: 50%; transform: translateY(-50%); font-size: 0.7rem; color: var(--ds-text-muted); }
.er-search input { padding-left: 1.6rem; }
.er-status { width: auto; min-width: 110px; }
.er-auto { font-size: 0.8rem; margin: 0; }
.er-auto__bcv { margin-left: 0.3rem; font-size: 0.7rem; color: var(--ds-text-muted); }

.er-footer { display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; padding: 0.4rem 0.85rem; border-top: 1px solid var(--ds-border); font-size: 0.78rem; }
.er-footer :deep(*) { margin-bottom: 0; }

@media (max-width: 576px) {
  .er-search { width: 100%; }
  .er-toolbar__actions { margin-left: 0; width: 100%; }
}
</style>
