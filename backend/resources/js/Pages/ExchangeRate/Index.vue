<script setup lang="ts">
  import { ref, computed, watch } from 'vue'
  import { router } from '@inertiajs/vue3'

  import PageHeader from '@/js/Components/Common/PageHeader/PageHeader.vue'
  import FormSwitch from '@/js/Components/Common/Form/FormSwitch.vue'
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
    subtitle="Valores en base a Un Dólar (1 USD)."
    :breadcrumb-items="[
      { label: 'Dashboard', route: '/dashboard', icon: 'fas fa-home' },
      { label: 'Tasas de Cambio' },
    ]"
  />

  <div v-if="!hasPrivilegedRole" class="mb-3">
    <NetworkExchangeRateWidget :key="widgetKey" hide-manage-link />
    <p class="er-own-hint">
      <i class="fas fa-circle-info mr-1"></i>
      Las ventas de tus salas usan <strong>tu tasa propia</strong> de cada moneda; si no la registras (o la desactivas),
      se usa la <strong>tasa oficial</strong> de la plataforma.
    </p>
  </div>

  <div class="card">
    <div class="card-body">
      <!-- Resumen rápido -->
      <div v-if="exchangeRates.length > 0" class="er-stats">
        <div class="er-stat">
          <span class="er-stat__label">Tasas activas</span>
          <span class="er-stat__value">{{ summaryStats.activeCount }}</span>
        </div>
        <div class="er-stat">
          <span class="er-stat__label">Última actualización</span>
          <span class="er-stat__value er-stat__value--sm">{{ formatDateTime(summaryStats.mostRecent) }}</span>
        </div>
        <div v-if="hasPrivilegedRole" class="er-stat">
          <span class="er-stat__label">Automáticas</span>
          <span class="er-stat__value">
            <i class="fas fa-robot er-stat__icon"></i> {{ summaryStats.automatic }}
          </span>
        </div>
        <div v-if="hasPrivilegedRole" class="er-stat">
          <span class="er-stat__label">Manuales</span>
          <span class="er-stat__value">{{ summaryStats.manual }}</span>
        </div>
      </div>

      <!-- Switch de actualización automática: solo visible si hay tasas registradas -->
      <div v-if="exchangeRates.length > 0" class="row">
        <FormSwitch
          name="auto-update"
          class="ml-1 col-12"
          label="¿Actualización automática?"
          v-model="autoUpdateEnabled"
          @update:modelValue="handleAutoUpdateToggle"
        />

        <!-- Feedback: VES se actualiza vía BCV en lugar de la tasa general -->
        <div v-if="autoUpdateEnabled && hasVesRate && bcvAutoUpdateEnabled" class="col-12">
          <div class="alert alert-info py-2 mb-2">
            <i class="fa-solid fa-circle-info"></i>
            El Bolívar venezolano (VES) tiene activada su propia actualización automática con la tasa
            oficial del <strong>BCV</strong> y no se verá afectado por esta opción.
          </div>
        </div>
      </div>

      <hr class="mt-n2" />

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
          <!-- Cambiar Status (solo con selección) -->
          <button
            v-if="ids.length > 0"
            class="btn btn-sm btn-secondary"
            title="Cambiar Status"
            @click="handleChangeStatus(ids)"
          >
            <i class="fa-solid fa-repeat"></i>
          </button>

          <!-- Histórico (requiere exactamente 1 seleccionado) -->
          <button
            v-if="ids.length === 1"
            class="btn btn-sm btn-primary"
            type="button"
            title="Ver Histórico"
            @click="goHistorical"
          >
            <i class="fa-solid fa-money-bill-trend-up"></i> Histórico
          </button>

          <!-- Consultar Valores -->
          <VDropdown
            :shown="exchangeRates.length === 0 ? undefined : false"
            :triggers="exchangeRates.length === 0 ? ['hover', 'focus'] : []"
            placement="top"
            :autoHide="true"
          >
            <button
              class="btn btn-sm btn-secondary"
              type="button"
              :disabled="updatingExchangeRates || exchangeRates.length === 0"
              @click="handleUpdateExchangeRates"
            >
              <i class="fa-solid fa-search"></i> Consultar
              <span
                v-if="updatingExchangeRates"
                class="spinner-border spinner-border-sm ml-1"
                aria-hidden="true"
              ></span>
            </button>
            <template #popper>
              <div style="padding: 4px 8px; max-width: 220px; font-size: 0.8rem;">
                Registra al menos una tasa de cambio para poder consultar valores.
              </div>
            </template>
          </VDropdown>
        </template>
      </TableActionButtons>

      <!-- Búsqueda/filtro -->
      <div v-if="exchangeRates.length > 0" class="row mt-2 mb-1">
        <div class="col-md-6 col-lg-4">
          <input
            type="search"
            class="form-control form-control-sm"
            placeholder="Buscar por moneda o usuario..."
            v-model="searchQuery"
          />
        </div>
        <div class="col-md-4 col-lg-3 mt-2 mt-md-0">
          <select class="form-control form-control-sm" v-model="statusFilter">
            <option value="">Todos los status</option>
            <option v-for="status in statusFilterOptions" :key="status" :value="status">
              {{ status }}
            </option>
          </select>
        </div>
      </div>

      <ExchangeRateTable
        :usedCurrencies="usedCurrencies"
        :exchange-rates="filteredExchangeRates"
        :has-privileged-role="hasPrivilegedRole"
        :selected-ids="selectedIds"
        @selection-change="ids => (selectedIds = ids)"
      />

      <TableFooterInfo
        :filtered-count="filteredExchangeRates.length"
        :total-count="exchangeRates.length"
        :selected-count="selectedIds.length"
      />

      <div v-if="hasActiveFilters && filteredExchangeRates.length === 0" class="text-center text-muted py-2">
        Ningún registro coincide con los filtros aplicados.
        <button type="button" class="btn btn-link btn-sm p-0 align-baseline" @click="clearFilters">
          Limpiar filtros
        </button>
      </div>
    </div>
  </div>

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
.er-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.er-stat {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  padding: 0.6rem 0.85rem;
  background: var(--ds-surface-2, #f4f5f7);
  border-radius: 10px;
}

.er-stat__label {
  font-size: 0.72rem;
  color: var(--ds-text-muted, #8a94a6);
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.er-stat__value {
  font-size: 1.1rem;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
}

.er-stat__value--sm {
  font-size: 0.9rem;
  font-weight: 600;
}

.er-stat__icon {
  color: #a78bfa;
}
.er-own-hint { margin: 0.5rem 0 0; font-size: 0.8rem; color: var(--ds-text-muted); }
</style>
