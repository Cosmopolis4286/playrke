<script setup lang="ts">
  /**
   * Página Salas de Bingo Automaticas (Autogenerables) → Bingo/Room/Automatic/Summary
   */

  import { computed, ref, watch } from 'vue'
  import { router } from '@inertiajs/vue3'
  import backendClient from '@js/plugins/axios'

  // ==========================================================================
  // Componentes
  // ==========================================================================

  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'
  import BingoRoomForm from '@/js/Components/games/bingo/Room/Form/BingoRoomForm.vue'

  // ==========================================================================
  // Composables
  // ==========================================================================

  import { useCrudEventListener } from '@/js/composables/form/useCrudEventListener'
  import { useModalForm } from '@/js/composables/common/useModalForm'
  import { useBingoRoomAutomatic } from '@/js/composables/games/bingo/room/automatic/useBingoRoomAutomatic'

  // ==========================================================================
  // Types
  // ==========================================================================

  import type { BingoRoomAutomaticSummaryPageProps } from '@/js/types/games/bingo/room/automatic/summary/bingo-room-automatic-summary-page'
  import type { ExtraButton } from '@js/types/common/table/extra-buttons'
  import type { TableFilterInterface } from '@js/types/common/table/table-filter'
  import type { ServerQuery } from '@/js/composables/common/table/useTableController'
  import type { Pagination } from '@/js/types/common/table/pagination'

  // ==========================================================================
  // Props (Inertia)
  // ==========================================================================

  const props = defineProps<BingoRoomAutomaticSummaryPageProps>()

  // ==========================================================================
  // Lógica de negocio
  // ==========================================================================

  const {
    roomData,
    roomFormData,
    rooms,
    setInitialRooms,
    create,
    edit,
    copyRoom,
    toggleStatusBulk,
    removeBulk,
    submit,
    executeJob
  } = useBingoRoomAutomatic()

  setInitialRooms(props.rooms.rows)

  // ==========================================================================
  // Paginación server-side (Room/Automatic/Summary.vue)
  // ==========================================================================
  // Mismo bug/arreglo que Room/Summary.vue: el backend solo pagina 15 salas
  // por vez (BingoRoomAutomaticContextService::resolveGameRooms) — sin esto,
  // salas más allá de la página 1 quedaban invisibles. No hay columna de
  // texto legible (uuid_code) en bingo_rooms_automatic, así que el buscador
  // de texto libre no se envía al backend y opera solo sobre la página
  // actualmente cargada.
  const summaryLoading = ref(false)
  const pagination = ref<Pagination>(
    props.rooms_pagination ?? { total: props.rooms.rows.length, per_page: 15, current_page: 1, last_page: 1, from: null, to: null }
  )

  const FILTER_KEY_TO_PARAM: Record<string, string> = {
    roomtype_label: 'room_type_id',
    gamemode_label: 'game_mode_id',
    prizepooltype_label: 'prize_pool_type_id',
    currency_ids: 'currency_id',
    status: 'status',
  }

  async function fetchSummaryPage(query: ServerQuery) {
    summaryLoading.value = true
    try {
      const params: Record<string, string | number> = {
        page: query.page,
        per_page: pagination.value.per_page,
      }

      for (const [filterKey, value] of Object.entries(query.filters)) {
        if (!value) continue
        const param = FILTER_KEY_TO_PARAM[filterKey]
        if (param) params[param] = value
      }

      const { data } = await backendClient.get('/bingo-automatic-rooms/summary-data', { params })

      rooms.value = data.rooms?.rows ?? []
      if (data.pagination) pagination.value = data.pagination
    } catch {
      // silencioso — la tabla conserva la última página cargada correctamente
    } finally {
      summaryLoading.value = false
    }
  }

  // ==========================================================================
  // Modal + Form handler
  // ==========================================================================

  const {
    showModalForm,
    modalTitle,
    formRef: gameBingoRoomFormRef,
    handleSubmit
  } = useModalForm(roomData, submit, {
    defaultTitle: 'Sala de Bingo'
  })

  watch(showModalForm, isOpen => {
    if (!isOpen) {
      gameBingoRoomFormRef.value?.reset?.()
      create()
    }
  })

  // ==========================================================================
  // Modal: monedas sin tasa de cambio activa
  // ==========================================================================
  const showMissingRateModal = ref(false)

  const currenciesMissingRateLabels = computed(() => {
    const missing = gameBingoRoomFormRef.value?.currenciesMissingRate ?? []
    return missing
      .map(id => allowedCurrenciesOptions.value.find(opt => String(opt.value) === id)?.label ?? id)
      .join(', ')
  })

  /**
   * Intercepta el click de "Guardar": si la única razón de invalidez es que
   * hay monedas seleccionadas sin tasa de cambio activa, en vez de dejar el
   * botón simplemente deshabilitado se ofrece al usuario decidir cómo seguir
   * (quitar esas monedas o ir a configurar la tasa) antes de continuar.
   */
  const onSaveClick = () => {
    const missing = gameBingoRoomFormRef.value?.currenciesMissingRate ?? []
    if (missing.length > 0) {
      showMissingRateModal.value = true
      return
    }
    handleSubmit()
  }

  const goToExchangeRates = () => {
    router.visit('/exchange-rate')
  }

  const continueWithoutMissingCurrencies = () => {
    gameBingoRoomFormRef.value?.removeCurrenciesMissingRate?.()
    showMissingRateModal.value = false
  }

  const canAttemptSave = computed(() => {
    if (gameBingoRoomFormRef.value?.isValid) return true
    return (gameBingoRoomFormRef.value?.currenciesMissingRate?.length ?? 0) > 0
  })

  /** "Paso X de Y" para orientar al usuario dentro del wizard del modal. */
  const stepProgressLabel = computed(() => {
    const ref = gameBingoRoomFormRef.value
    if (!ref?.steps || !ref.step) return ''
    const idx = ref.steps.indexOf(ref.step)
    if (idx === -1) return ''
    return `Paso ${idx + 1} de ${ref.steps.length}`
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
      if (ok) {
        showModalForm.value = true
      }
    },

    delete: async ids => {
      if (!ids) return
      await removeBulk(ids)
    },

    toggleStatus: async ids => {
      if (!ids) return
      await toggleStatusBulk(ids)
    },

    copy: async ids => {
      if (!ids || ids.length !== 1) return
      await copyRoom(ids[0])
    }
  })

  /**
   * Opciones para tipos de sala.
   */
  const roomTypeOptions = computed(() => {
    return (props.roomTypes ?? []).map(r => ({
      value: String(r.id),
      code: r.code,
      label: r.label
    }))
  })

  /**
   * Opciones para modo de juego (catálogo real game_modes: Solitario, Múltiple,
   * Ranking...) — NO confundir con `props.gameModes`, que hoy mapea Tipo de
   * Conexión por un rename pendiente (ver comentario abajo).
   */
  const gameModeOptions = computed(() => {
    return (props.gameModeCatalog ?? []).map(g => ({
      value: String(g.id),
      label: g.label
    }))
  })

  /**
   * Opciones para tipo de conexión.
   * Nota: `props.gameModes` es la key expuesta por Inertia (el backend aún no
   * la renombró) — se mapea aquí a vocabulario interno "Tipo de Conexión".
   */
  const connectionTypeOptions = computed(() => {
    return (props.gameModes ?? []).map(g => ({
      value: String(g.id),
      label: g.label
    }))
  })

  /**
   * Opciones para tipos de prize pool.
   */
  const prizePoolTypeOptions = computed(() => {
    return (props.prizePoolTypes ?? []).map(p => ({
      value: String(p.id),
      code: p.code,
      label: p.label
    }))
  })

  /**
   * Opciones de monedas permitidas.
   */
  const allowedCurrenciesOptions = computed(() => {
    return (props.currencies ?? []).map(c => ({
      value: String(c.id),
      label: c.iso_code
    }))
  })

  /**
   * Opciones de patrones de bingo.
   */
  const bingoPatternOptions = computed(() => {
    return (props.bingoPatterns ?? []).map(p => ({
      value: String(p.id),
      label: p.name
    }))
  })

  // ==========================================================================
  // Filtros
  // ==========================================================================

  const filters = computed<readonly TableFilterInterface[]>(() => [
    {
      key: 'roomtype_label',
      label: 'tipo',
      placeholder: 'Filtrar por tipo',
      options: roomTypeOptions.value
    },
    {
      key: 'gamemode_label',
      label: 'tipo de conexión',
      placeholder: 'Filtrar por tipo de conexión',
      options: connectionTypeOptions.value
    },
    {
      key: 'prizepooltype_label',
      label: 'tipo bote',
      placeholder: 'Filtrar por tipo bote',
      options: prizePoolTypeOptions.value
    },
    {
      key: 'currency_ids',
      label: 'moneda',
      placeholder: 'Filtrar por moneda',
      options: allowedCurrenciesOptions.value
    },
    {
      key: 'status',
      label: 'Estado',
      placeholder: 'Filtrar por estado',
      options: [
        { value: 'ACTIVE', label: 'Disponible' },
        { value: 'IN_PROGRESS', label: 'En Juego' },
        { value: 'FINISHED', label: 'Finalizada' },
        { value: 'REFUND', label: 'Reembolso' },
        { value: 'OFFLINE', label: 'Fuera de Línea' }
      ]
    }
  ])

  // ==========================================================================
  // Botones extra
  // ==========================================================================

  const extraButtons: ExtraButton[] = [
    {
      label: 'Ejecutar Job',
      class: 'btn-dark text-white',
      show: (ids: string[]) => !ids.length && rooms.value.length > 0,
      onClick: () => executeJob()
    },
    {
      label: 'Salas de Juego',
      class: 'btn-secondary',
      show: (ids: string[]) => !ids.length,
      onClick: () => {
        window.location.href = '/bingo-rooms'
      }
    },
    {
      icon: 'fas fa-exchange-alt',
      class: 'btn-secondary',
      show: false,
      action: 'toggleStatus'
    },
    {
      icon: 'fas fa-copy',
      class: 'btn-info text-white',
      show: (ids: string[]) => ids.length === 1,
      action: 'copy'
    }
  ]
</script>

<template>
  <TableWithFilterBridge
    tableId="bingo-room-table"
    :title="props.title"
    :subtitle="props.subtitle"
    :columns="props.rooms.columns"
    :rows="rooms"
    :filters="filters"
    search-placeholder="Buscar en la página actual..."
    showCreateButton
    showEditButton
    showDeleteButton
    :extra-buttons="extraButtons"
    :loading="summaryLoading"
    server-side
    :server-total-pages="pagination.last_page"
    @query-change="fetchSummaryPage"
  />

  <Modal
    v-model="showModalForm"
    :title="modalTitle"
    size="lg"
    static
  >
    <BingoRoomForm
      ref="gameBingoRoomFormRef"
      v-model="roomFormData"
      :automatic-room="true"
      :is-edit="!!roomData.id"
      :room-type-options="roomTypeOptions"
      :connection-type-options="connectionTypeOptions"
      :game-mode-options="gameModeOptions"
      :prize-pool-type-options="prizePoolTypeOptions"
      :allowed-currencies-options="allowedCurrenciesOptions"
      :bingo-pattern-options="bingoPatternOptions"
      :currencies-with-active-rate="props.currenciesWithActiveRate"
    />

    <template #footer>
      <small v-if="stepProgressLabel" class="text-muted mr-2" style="font-size: 0.78rem">
        {{ stepProgressLabel }}
      </small>
      <button class="btn btn-sm btn-secondary" @click="showModalForm = false">Cerrar</button>
      <button
        v-if="gameBingoRoomFormRef?.canGoPrev"
        type="button"
        class="btn btn-sm btn-outline-secondary"
        @click="gameBingoRoomFormRef?.goPrev()"
      >
        <i class="fa-solid fa-arrow-left mr-1"></i> Anterior
      </button>
      <button
        v-if="gameBingoRoomFormRef?.step !== 'review'"
        type="button"
        class="btn btn-sm btn-primary"
        :disabled="!gameBingoRoomFormRef?.canGoNext"
        :title="!gameBingoRoomFormRef?.canGoNext ? 'Corrige los errores de este paso para continuar' : ''"
        @click="gameBingoRoomFormRef?.goNext()"
      >
        Siguiente <i class="fa-solid fa-arrow-right ml-1"></i>
      </button>
      <button
        v-else
        class="btn btn-sm btn-primary"
        :disabled="!canAttemptSave"
        :title="!canAttemptSave ? 'Corrige los errores señalados en el resumen antes de guardar' : ''"
        @click="onSaveClick"
      >
        <i class="fa-solid fa-floppy-disk mr-1"></i> Guardar
      </button>
    </template>
  </Modal>

  <!-- Modal: monedas sin tasa de cambio activa -->
  <Modal
    v-model="showMissingRateModal"
    title="Monedas sin tasa de cambio activa"
    size="sm"
  >
    <div class="py-2">
      <i class="fas fa-exclamation-triangle text-warning fa-2x d-block mb-3 text-center"></i>
      <p class="mb-2">
        Las siguientes monedas no tienen tasa de cambio activa: <strong>{{ currenciesMissingRateLabels }}</strong>.
      </p>
      <p class="mb-0 text-muted" style="font-size: 0.85rem">
        Sin la tasa, el sistema no puede registrar correctamente el equivalente en USD para esas monedas.
        Puedes continuar sin ellas, o ir a configurar la tasa antes de guardar la sala.
      </p>
    </div>
    <template #footer>
      <button class="btn btn-sm btn-secondary" @click="showMissingRateModal = false">Cancelar</button>
      <button class="btn btn-sm btn-warning" @click="goToExchangeRates">Ir a Tasas de Cambio</button>
      <button class="btn btn-sm btn-primary" @click="continueWithoutMissingCurrencies">Continuar sin esas monedas</button>
    </template>
  </Modal>
</template>
