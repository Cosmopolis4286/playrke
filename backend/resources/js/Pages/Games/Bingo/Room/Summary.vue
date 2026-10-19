<script setup lang="ts">
  /**
   * Página Juego BINGO → GameRoom/Summary
   */
  import { computed, ref, watch } from 'vue'
  import backendClient from '@js/plugins/axios'

  // ==========================================================================
  // Componentes
  // ==========================================================================
  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'
  import BingoRoomForm from '@/js/Components/games/bingo/Room/Form/BingoRoomForm.vue'
  import BingoRoomDetails from '@/js/Components/games/bingo/Room/BingoRoomDetails.vue'
  import BingoRoomActivateModal from '@/js/Components/games/bingo/Room/BingoRoomActivateModal.vue'
  import BingoRoomInvitationsModal from '@/js/Components/games/bingo/Room/BingoRoomInvitationsModal.vue'
  import StatTile from '@/js/Components/Common/StatTile/StatTile.vue'

  // ==========================================================================
  // Composables
  // ==========================================================================
  import { useCrudEventListener } from '@/js/composables/form/useCrudEventListener'
  import { useModalForm } from '@/js/composables/common/useModalForm'
  import { useBingoRoom } from '@/js/composables/games/bingo/room/default/useBingoRoom'
  import { useBingoRoomStatusChecks } from '@js/composables/games/bingo/room/summary/useBingoRoomStatusChecks'
  import { useRoomDetailsModal } from '@js/composables/games/bingo/room/summary/useRoomDetailsModal'
  import { useRoomActivateModal } from '@js/composables/games/bingo/room/summary/useRoomActivateModal'
  import { useRoomInvitationsModal } from '@js/composables/games/bingo/room/summary/useRoomInvitationsModal'
  import { useMissingRateModal } from '@js/composables/games/bingo/room/summary/useMissingRateModal'
  import { useRoomSummaryFilters, useRoomSummaryExtraButtons } from '@js/composables/games/bingo/room/summary/useRoomSummaryTableConfig'
  import { useDateRangeExport } from '@js/composables/common/table/useDateRangeExport'
  import { usePage, router } from '@inertiajs/vue3'

  // ==========================================================================
  // Types
  // ==========================================================================
  import type { BingoRoomSummaryPageProps } from '@/js/types/games/bingo/room/summary/bingo-room-summary-page'
  import type { ServerQuery } from '@/js/composables/common/table/useTableController'
  import type { Pagination } from '@/js/types/common/table/pagination'

  // ==========================================================================
  // Props (Inertia)
  // ==========================================================================
  const props = defineProps<BingoRoomSummaryPageProps>()

  // ==========================================================================
  // Role checking
  // ==========================================================================
  const page = usePage()

  /** Verifica si el usuario es admin o superadmin usando props de Inertia */
  const isAdmin = computed(() => {
    const userRoles = (page.props.auth as any)?.user?.userRoles || [];
    return userRoles.some((ur: { role?: { prefix?: string } }) =>
      ur.role?.prefix === 'admin' || ur.role?.prefix === 'superadmin'
    );
  });

  // ==========================================================================
  // Lógica de negocio
  // ==========================================================================
  const {
    roomData,
    soldCurrencyIds,
    hostUserLabel,
    roomFormData,
    rooms,
    loading,
    setInitialRooms,
    create,
    edit,
    copyRoom,
    toggleStatusBulk,
    removeBulk,
    submit,
    goToRoom,
    cancelRoom,
    activateRoomNow,
    scheduleRoomStart,
    fetchRoomDetails
  } = useBingoRoom()

  setInitialRooms(props.rooms.rows)

  // ==========================================================================
  // Paginación server-side (Room/Summary.vue)
  // ==========================================================================
  // El backend solo pagina 15 salas por vez (BingoRoomContextService::
  // resolveSummaryGameRooms) — sin esto, las salas más allá de la página 1
  // quedaban invisibles y sin forma de buscarlas. Los filtros por columna
  // calculada (config_roomtype_label, etc.) mapean a su condición SQL real
  // vía BingoRoomController::summaryData; la búsqueda de texto libre solo
  // filtra por uuid_code (única columna de texto real en bingo_rooms), no
  // por tipo/modo/tipo bote (esas son labels calculados sin columna SQL
  // única que las cubra con seguridad).
  const pagination = ref<Pagination>(
    props.rooms_pagination ?? { total: props.rooms.rows.length, per_page: 15, current_page: 1, last_page: 1, from: null, to: null }
  )

  const FILTER_KEY_TO_PARAM: Record<string, string> = {
    config_roomtype_label: 'room_type_id',
    config_gamemode_label: 'game_mode_id',
    config_prizepooltype_label: 'prize_pool_type_id',
    currency_ids: 'currency_id',
    status: 'status',
    from_house: 'from_house',
    commerce_id: 'commerce_id',
  }

  // Última query de la tabla (búsqueda/filtros/página) — se conserva para
  // poder re-disparar el fetch a página 1 cuando cambia el rango de fechas
  // sin perder el resto de filtros activos (mismo patrón que
  // useWalletMovementsList.applyDateRange/fetchMovements).
  const lastQuery = ref<ServerQuery>({ search: '', filters: {}, sort: { key: null, direction: 'asc' }, page: 1 })

  async function fetchSummaryPage(query: ServerQuery) {
    lastQuery.value = query
    loading.value = true
    try {
      const params: Record<string, string | number> = {
        page: query.page,
        per_page: pagination.value.per_page,
      }

      if (query.search) params.search = query.search

      for (const [filterKey, value] of Object.entries(query.filters)) {
        if (!value) continue
        const param = FILTER_KEY_TO_PARAM[filterKey]
        if (param) params[param] = value
      }

      if (dateFrom.value) params.date_from = dateFrom.value
      if (dateTo.value) params.date_to = dateTo.value

      const { data } = await backendClient.get('/bingo-rooms/summary-data', { params })

      rooms.value = data.rooms?.rows ?? []
      if (data.pagination) pagination.value = data.pagination
    } catch {
      // silencioso — la tabla conserva la última página cargada correctamente
    } finally {
      loading.value = false
    }
  }

  // ==========================================================================
  // Filtro por período + exportación CSV — mismo patrón que /wallet-movements
  // (ver useDateRangeExport, extraído de ahí para reutilizar en ambas pantallas).
  // ==========================================================================
  const { dateFrom, dateTo, applyDateRange, exportUrl: exportUrlBase } = useDateRangeExport(
    '/bingo-rooms/export',
    () => fetchSummaryPage({ ...lastQuery.value, page: 1 }),
  )

  function exportUrl(): string {
    const params: Record<string, string | undefined> = {}
    if (lastQuery.value.search) params.search = lastQuery.value.search
    for (const [filterKey, value] of Object.entries(lastQuery.value.filters)) {
      if (!value) continue
      const param = FILTER_KEY_TO_PARAM[filterKey]
      if (param) params[param] = value
    }
    return exportUrlBase(params)
  }

  // ==========================================================================
  // Modal de Detalles
  // ==========================================================================
  const { showDetailsModal, selectedRoomDetails, loadingDetails, openRoomDetails, closeDetailsModal } =
    useRoomDetailsModal(fetchRoomDetails)

  // ==========================================================================
  // Modal de Activación / Programación de inicio (salas sin horario)
  // ==========================================================================
  const {
    showActivateModal, selectedActivateRoom, isActivatingRoom, isSchedulingRoom,
    openActivateModal, handleActivateNow, handleScheduleStart,
  } = useRoomActivateModal({ rooms, activateRoomNow, scheduleRoomStart })

  // ==========================================================================
  // Modal + Form handler
  // ==========================================================================

  const {
    showModalForm,
    modalTitle,
    formRef: gameBingoRoomFormRef,
    isSubmitting,
    handleSubmit
  } = useModalForm(roomData, submit, {
    defaultTitle: 'Sala de Bingo'
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
   * Conexión por un rename pendiente (ver comentario en BingoRoomSummaryDTO).
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
   * Opciones para tipos de jackpot.
   */
  const jackpotTypeOptions = computed(() => {
    return (props.jackpotTypes ?? []).map(j => ({
      value: String(j.id),
      code: j.code,
      label: j.label
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

  /**
   * Opciones de comercializadoras — solo llega poblado para admin/superadmin
   * (ver BingoRoomContextService::summaryGameRooms), usado por el filtro
   * "Comercializadora" y la columna homónima de la tabla. Incluye la opción
   * especial "Salas de sistema (admin)" (valor centinela '__SYSTEM__', ver
   * BingoRoomContextService::resolveSummaryGameRooms) para aislar las salas
   * creadas directo por un admin/superadmin, sin comercio asociado.
   */
  const commerceOptions = computed(() => {
    const options = (props.commerces ?? []).map(c => ({
      value: String(c.id),
      label: c.name
    }))
    options.push({ value: '__SYSTEM__', label: 'Salas de sistema (admin)' })
    return options
  })

  /**
   * true si la sala en edición es programada (no manual) y su fecha/hora ya pasó.
   * En edición, useBingoRoomBaseForm omite la validación de fecha pasada (el usuario
   * no puede cambiar una fecha ya vencida, pero sí otros campos), así que sin este
   * chequeo se podía activar una sala cuyo horario programado ya no tiene sentido.
   */
  const isScheduledDateInPast = computed(() => {
    if (roomFormData.value.startMode !== 'SCHEDULED') return false
    const { date, time } = roomFormData.value
    if (!date || !time) return false
    return new Date(`${date}T${time}:00`) < new Date()
  })

  // Si la fecha/hora programada de la sala en edición queda vencida (o ya lo estaba
  // al abrir el modal), evita que quede marcada para activarse.
  watch(isScheduledDateInPast, isPast => {
    if (isPast && roomFormData.value.activateRoom) {
      roomFormData.value = { ...roomFormData.value, activateRoom: false }
    }
  })

  // ==========================================================================
  // Modal: monedas sin tasa de cambio activa
  // ==========================================================================
  const {
    showMissingRateModal, currenciesMissingRateLabels,
    onSaveClick, goToExchangeRates, continueWithoutMissingCurrencies, canAttemptSave,
  } = useMissingRateModal({
    formRef: gameBingoRoomFormRef,
    allowedCurrenciesOptions,
    handleSubmit,
  })

  // ==========================================================================
  // Filtros
  // ==========================================================================

  const { filters } = useRoomSummaryFilters({
    roomTypeOptions, connectionTypeOptions, prizePoolTypeOptions, allowedCurrenciesOptions, isAdmin, commerceOptions,
  })

  // ==========================================================================
  // Botones extra
  // ==========================================================================

  const {
    isExpired, hasPlayers, hasActiveStatus, isNotEditableStatus,
    canCancelRoom, canActivateNow, isManualRoom, canManageInvitations,
  } = useBingoRoomStatusChecks(rooms)

  // Lista de invitados de salas VIP (creador o admin).
  const {
    showInvitationsModal, invitationsRoom, invitations, loadingInvitations, savingInvitation, invitationError,
    requirements: vipRequirements, savingRequirements,
    openInvitationsModal, addInvitation, removeInvitation, saveRequirements,
  } = useRoomInvitationsModal(rooms)

  async function handleAddInvitation(payload: Parameters<typeof addInvitation>[0], done: (ok: boolean) => void) {
    done(await addInvitation(payload))
  }

  /**
   * true si la sala actualmente en edición ya tiene jugadores registrados —
   * en ese caso el formulario bloquea el switch de estado (¿Activar sala?),
   * pero permite seguir editando el resto (monedas, precios, bote, rondas,
   * comisión, jackpot, tiempo ficha, min. cartones, modo/horario de inicio).
   */
  const editingRoomHasPlayers = computed(() => hasPlayers([String(roomData.value.id ?? '')]))

  const extraButtons = useRoomSummaryExtraButtons({
    isExpired, hasPlayers, canCancelRoom, canActivateNow, isManualRoom, canManageInvitations,
    openRoomDetails, openInvitationsModal, openActivateModal, goToRoom, cancelRoom,
  })

  const subtitle = `<a title="Ir a configuración del Bingo" href="/bingo/config" class="text-primary font-weight-bold">Ir a configuración</a>`

  /** "Paso X de Y" para orientar al usuario dentro del wizard del modal. */
  const stepProgressLabel = computed(() => {
    const ref = gameBingoRoomFormRef.value
    if (!ref?.steps || !ref.step) return ''
    const idx = ref.steps.indexOf(ref.step)
    if (idx === -1) return ''
    return `Paso ${idx + 1} de ${ref.steps.length}`
  })
</script>

<template>
  <!-- KPIs por estado (todas las salas que ve el usuario, sin filtros) -->
  <div v-if="props.statusCounts?.total" class="room-kpis">
    <StatTile label="Disponibles" :value="props.statusCounts.active" icon="fa-door-open" variant="success" />
    <StatTile label="Inician hoy" :value="props.statusCounts.today" icon="fa-calendar-day" :variant="props.statusCounts.today ? 'info' : 'default'" />
    <StatTile label="En juego" :value="props.statusCounts.in_progress" icon="fa-circle-play" :variant="props.statusCounts.in_progress ? 'warning' : 'default'" />
    <StatTile label="Finalizadas" :value="props.statusCounts.finished" icon="fa-flag-checkered" />
    <StatTile label="Canceladas" :value="props.statusCounts.cancelled" icon="fa-ban" :variant="props.statusCounts.cancelled ? 'danger' : 'default'" />
  </div>

  <TableWithFilterBridge
    tableId="bingo-room-table"
    :title="props.title"
    :subtitle="subtitle"
    :columns="props.rooms.columns"
    :rows="rooms"
    :filters="filters"
    search-placeholder="Buscar por código de sala..."
    showCreateButton
    showEditButton
    showDeleteButton
    :disable-edit="isNotEditableStatus"
    disable-edit-reason="No se puede editar una sala En Juego, Finalizada o Cancelada"
    :disable-delete="hasPlayers"
    disable-delete-reason="No se puede eliminar una sala con jugadores registrados"
    :extra-buttons="extraButtons"
    :loading="loading"
    server-side
    :server-total-pages="pagination.last_page"
    @query-change="fetchSummaryPage"
  >
    <template #action-buttons>
      <div class="room-date-range">
        <input
          v-model="dateFrom"
          type="date"
          class="form-control form-control-sm"
          title="Desde"
          @change="applyDateRange"
        />
        <span class="room-date-range__sep">—</span>
        <input
          v-model="dateTo"
          type="date"
          class="form-control form-control-sm"
          title="Hasta"
          @change="applyDateRange"
        />
      </div>
      <a class="btn btn-sm btn-outline-secondary" title="Exportar salas filtradas a CSV" :href="exportUrl()">
        <i class="fas fa-file-csv mr-1"></i>Exportar
      </a>
    </template>
  </TableWithFilterBridge>

  <Modal
    v-model="showModalForm"
    :title="modalTitle"
    size="lg"
    static
  >
    <BingoRoomForm
      ref="gameBingoRoomFormRef"
      v-model="roomFormData"
      :is-edit="!!roomData.id"
      :room-meta="roomData"
      :room-type-options="roomTypeOptions"
      :connection-type-options="connectionTypeOptions"
      :game-mode-options="gameModeOptions"
      :host-user-label="hostUserLabel"
      :prize-pool-type-options="prizePoolTypeOptions"
      :jackpot-type-options="jackpotTypeOptions"
      :allowed-currencies-options="allowedCurrenciesOptions"
      :bingo-pattern-options="bingoPatternOptions"
      :currencies-with-active-rate="props.currenciesWithActiveRate"
      :existing-rooms="rooms"
      :editing-room-id="roomData.id ? String(roomData.id) : null"
      :editing-room-has-players="editingRoomHasPlayers"
      :sold-currency-ids="soldCurrencyIds"
    />

    <template #footer>
      <div class="brf">
        <!-- Fila de estado: detalles + progreso + borrador (se envuelve/oculta antes que los botones de acción) -->
        <div class="brf__status">
          <button
            v-if="editingRoomHasPlayers"
            type="button"
            class="btn btn-sm btn-outline-warning brf__details-btn"
            :disabled="isSubmitting"
            title="Ver ventas, ganadores y desglose financiero sin cerrar la edición"
            @click="openRoomDetails([String(roomData.id)])"
          >
            <i class="fas fa-info-circle"></i> <span class="brf__details-text">Ver Detalles</span>
          </button>
          <small v-if="stepProgressLabel" class="text-muted brf__step-label">
            {{ stepProgressLabel }}
          </small>
          <small
            v-if="gameBingoRoomFormRef?.draftStatus === 'saving' || gameBingoRoomFormRef?.draftStatus === 'saved'"
            class="text-muted brf__draft-label"
          >
            <i class="fa-solid fa-circle-notch fa-spin mr-1" v-if="gameBingoRoomFormRef?.draftStatus === 'saving'"></i>
            <i class="fa-solid fa-check mr-1" v-else></i>
            {{ gameBingoRoomFormRef?.draftStatus === 'saving' ? 'Guardando borrador...' : 'Borrador guardado' }}
          </small>
        </div>

        <!-- Fila de acciones: navegación del wizard, siempre visible y clickeable -->
        <div class="brf__actions">
          <button class="btn btn-sm btn-secondary brf__btn" :disabled="isSubmitting" @click="showModalForm = false">Cerrar</button>
          <button
            v-if="gameBingoRoomFormRef?.canGoPrev"
            type="button"
            class="btn btn-sm btn-outline-secondary brf__btn"
            :disabled="isSubmitting"
            @click="gameBingoRoomFormRef?.goPrev()"
          >
            <i class="fa-solid fa-arrow-left mr-1"></i> Anterior
          </button>
          <button
            v-if="gameBingoRoomFormRef?.step !== 'review'"
            type="button"
            class="btn btn-sm btn-primary brf__btn"
            :disabled="isSubmitting || !gameBingoRoomFormRef?.canGoNext"
            :title="!gameBingoRoomFormRef?.canGoNext ? 'Corrige los errores de este paso para continuar' : ''"
            @click="gameBingoRoomFormRef?.goNext()"
          >
            Siguiente <i class="fa-solid fa-arrow-right ml-1"></i>
          </button>
          <button
            v-else
            class="btn btn-sm btn-primary brf__btn"
            :disabled="!canAttemptSave || isSubmitting || loading"
            :title="!canAttemptSave ? 'Corrige los errores señalados en el resumen antes de guardar' : ''"
            @click="onSaveClick"
          >
            <span v-if="isSubmitting" class="spinner-border spinner-border-sm mr-1" role="status" aria-hidden="true"></span>
            <i v-else class="fa-solid fa-floppy-disk mr-1"></i>
            {{ isSubmitting ? 'Guardando...' : 'Guardar' }}
          </button>
        </div>
      </div>
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

  <!-- Modal de Detalles de Sala -->
  <Modal
    v-model="showDetailsModal"
    title="Detalles de Sala"
    size="lg"
  >
    <!-- Loader mientras se cargan los detalles -->
    <div v-if="loadingDetails" class="d-flex flex-column align-items-center justify-content-center py-5">
      <span class="spinner-border text-primary mb-3" role="status" aria-hidden="true" style="width:2.5rem;height:2.5rem;"></span>
      <p class="text-muted mb-0">Cargando detalles de la sala...</p>
    </div>

    <!-- Error si no cargó -->
    <div v-else-if="!selectedRoomDetails" class="d-flex flex-column align-items-center justify-content-center py-5">
      <i class="fas fa-exclamation-circle fa-3x text-danger mb-3"></i>
      <p class="text-muted mb-0">No se pudieron cargar los detalles. Intenta de nuevo.</p>
    </div>

    <BingoRoomDetails
      v-else
      :room="selectedRoomDetails"
      :room-type-options="roomTypeOptions"
      :connection-type-options="connectionTypeOptions"
      :game-mode-options="gameModeOptions"
      :prize-pool-type-options="prizePoolTypeOptions"
      :jackpot-type-options="jackpotTypeOptions"
      :allowed-currencies-options="allowedCurrenciesOptions"
    />

    <template #footer>
      <button class="btn btn-sm btn-secondary" @click="closeDetailsModal">Cerrar</button>
    </template>
  </Modal>

  <!-- Modal de Activación / Programación de Inicio (salas sin horario) -->
  <BingoRoomActivateModal
    v-model="showActivateModal"
    :room="selectedActivateRoom"
    :activating="isActivatingRoom"
    :scheduling="isSchedulingRoom"
    @activate-now="handleActivateNow"
    @schedule-start="handleScheduleStart"
  />

  <!-- Modal: invitados de sala VIP -->
  <BingoRoomInvitationsModal
    v-model="showInvitationsModal"
    :room="invitationsRoom"
    :invitations="invitations"
    :loading="loadingInvitations"
    :saving="savingInvitation"
    :error="invitationError"
    :requirements="vipRequirements"
    :saving-requirements="savingRequirements"
    @add="handleAddInvitation"
    @remove="removeInvitation"
    @save-requirements="saveRequirements"
  />
</template>

<style scoped>
/* Footer del wizard de registro/edición de sala — el slot #footer de Modal.vue
   solo reemplaza el CONTENIDO de .modal-footer (que sigue siendo flex-nowrap,
   justify-end del propio Modal), así que en pantallas angostas los ~6
   elementos (detalles, progreso, borrador, cerrar, anterior, siguiente/guardar)
   se comprimían o desbordaban sin ningún manejo responsive. Este wrapper toma
   el control total del layout interno sin tocar Modal.vue (compartido por
   otros modales del proyecto).
   Estructura: 2 filas — estado (info secundaria) arriba, acciones (siempre
   visibles y clickeables) abajo. En desktop ancho, ambas filas caben en una
   sola línea y se muestran lado a lado. */
.brf {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  width: 100%;
}
.brf__status {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem 0.75rem;
  min-width: 0;
}
.brf__step-label, .brf__draft-label { font-size: 0.78rem; white-space: nowrap; }
.brf__details-btn { flex-shrink: 0; }
.brf__actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  flex-wrap: wrap;
  gap: 0.5rem;
}
.brf__btn { flex: 0 0 auto; }

/* ≥576px (sm): una sola fila, estado a la izquierda y acciones a la derecha —
   mismo aspecto que el footer original en pantallas normales. */
@media (min-width: 576px) {
  .brf { flex-direction: row; align-items: center; justify-content: space-between; }
  .brf__status { flex: 1 1 auto; }
  .brf__actions { flex: 0 0 auto; }
}

/* <400px: los botones de acción ocupan el ancho completo, apilados, para que
   sigan siendo fáciles de tocar en vez de comprimirse en una fila apretada. */
@media (max-width: 399px) {
  .brf__details-text { display: none; }
  .brf__actions { flex-direction: column; align-items: stretch; }
  .brf__btn { width: 100%; justify-content: center; }
}

.room-kpis {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}
.room-kpis :deep(.stat-tile) { padding: 0.55rem 0.75rem; }
.room-kpis :deep(.stat-tile-icon) { width: 32px; height: 32px; }
.room-kpis :deep(.stat-tile-value) { font-size: 1.1rem; }
@media (max-width: 992px) { .room-kpis { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
@media (max-width: 576px) { .room-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); } }

.room-date-range {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  margin-right: 0.5rem;
}
.room-date-range input[type="date"] { width: 140px; }
.room-date-range__sep { color: var(--ds-text-muted); font-size: 0.8rem; }

.mr-1 { margin-right: 0.25rem; }
</style>
