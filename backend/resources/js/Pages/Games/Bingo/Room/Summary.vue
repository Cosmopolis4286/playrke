<script setup lang="ts">
  /**
   * Página Juego BINGO → GameRoom/Summary
   */
  import { computed, ref, watch } from 'vue'

  // ==========================================================================
  // Componentes
  // ==========================================================================
  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'
  import BingoRoomForm from '@/js/Components/Games/Bingo/Room/Form/BingoRoomForm.vue'
  import BingoRoomDetails from '@/js/Components/Games/Bingo/Room/BingoRoomDetails.vue'
  import BingoRoomActivateModal from '@/js/Components/Games/Bingo/Room/BingoRoomActivateModal.vue'

  // ==========================================================================
  // Composables
  // ==========================================================================
  import { useCrudEventListener } from '@/js/composables/form/useCrudEventListener'
  import { useModalForm } from '@/js/composables/common/useModalForm'
  import { useBingoRoom } from '@/js/composables/games/bingo/room/default/useBingoRoom'
  import { usePage, router } from '@inertiajs/vue3'

  // ==========================================================================
  // Types
  // ==========================================================================
  import type { BingoRoomSummaryPageProps } from '@/js/types/games/bingo/room/summary/bingo-room-summary-page'
  import type { ExtraButton } from '@js/types/common/table/extra-buttons'
  import type { TableFilterInterface } from '@js/types/common/table/table-filter'
  import type { BingoRoom } from '@/js/types/games/bingo/room/bingo-room'

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
    const userRoles = page.props.auth?.user?.userRoles || [];
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
  // Modal de Detalles
  // ==========================================================================
  const showDetailsModal = ref(false)
  const selectedRoomDetails = ref<BingoRoom | null>(null)
  const loadingDetails = ref(false)

  const openRoomDetails = async (ids: string[]) => {
    if (!ids || ids.length !== 1) return

    const roomId = ids[0]
    selectedRoomDetails.value = null
    loadingDetails.value = true
    showDetailsModal.value = true

    const roomDetails = await fetchRoomDetails(roomId)
    selectedRoomDetails.value = roomDetails ?? null

    loadingDetails.value = false
  }

  const closeDetailsModal = () => {
    showDetailsModal.value = false
    selectedRoomDetails.value = null
  }

  // ==========================================================================
  // Modal de Activación / Programación de inicio (salas sin horario)
  // ==========================================================================
  const showActivateModal = ref(false)
  const selectedActivateRoom = ref<BingoRoom | null>(null)
  const isActivatingRoom = ref(false)
  const isSchedulingRoom = ref(false)

  const openActivateModal = (ids: string[]) => {
    if (ids.length !== 1) return
    const room = rooms.value.find(r => String(r.id) === String(ids[0]))
    if (!room) return

    selectedActivateRoom.value = room
    showActivateModal.value = true
  }

  const handleActivateNow = async (roomId: string | number) => {
    isActivatingRoom.value = true
    const ok = await activateRoomNow(roomId)
    isActivatingRoom.value = false
    if (ok) showActivateModal.value = false
  }

  const handleScheduleStart = async (roomId: string | number, date: string, time: string) => {
    isSchedulingRoom.value = true
    const ok = await scheduleRoomStart(roomId, date, time)
    isSchedulingRoom.value = false
    if (ok) showActivateModal.value = false
  }

  watch(showActivateModal, open => {
    if (!open) selectedActivateRoom.value = null
  })

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
      label: r.label
    }))
  })

  /**
   * Opciones para modos de juego.
   */
  const gameModeOptions = computed(() => {
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

  /**
   * Habilita el botón "Guardar" cuando el formulario es válido, o cuando la
   * única razón de invalidez son monedas sin tasa activa — en ese caso el
   * click abre el modal de decisión (onSaveClick) en vez de enviar.
   */
  const canAttemptSave = computed(() => {
    if (gameBingoRoomFormRef.value?.isValid) return true
    return (gameBingoRoomFormRef.value?.currenciesMissingRate?.length ?? 0) > 0
  })

  // ==========================================================================
  // Filtros
  // ==========================================================================

  const filters = computed<readonly TableFilterInterface[]>(() => {
  const baseFilters = [
    {
      key: 'config_roomtype_label',
      label: 'tipo',
      placeholder: 'Filtrar por tipo',
      options: roomTypeOptions.value
    },
    {
      key: 'config_gamemode_label',
      label: 'modo',
      placeholder: 'Filtrar por modo',
      options: gameModeOptions.value
    },
    {
      key: 'config_prizepooltype_label',
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
  ];

  // Agregar filtro from_house solo para admin/superadmin
  if (isAdmin.value) {
    baseFilters.push({
      key: 'from_house',
      label: 'Origen',
      placeholder: 'Filtrar por origen',
      options: [
        { value: 'Y', label: 'Salas propias de la app' },
        { value: 'N', label: 'Salas de usuarios' }
      ]
    });
  }

  return baseFilters;
})

  // ==========================================================================
  // Botones extra
  // ==========================================================================

  const nowUnix = (): number => Math.floor(Date.now() / 1000)

  const isExpired = (ids: string[]): boolean =>
    ids.some(id => {
      const room = rooms.value.find(r => String(r.id) === String(id))
      return room != null && room.unix_date != null && Number(room.unix_date) <= nowUnix()
    })

  const hasPlayers = (ids: string[]): boolean =>
    ids.some(id => {
      const room = rooms.value.find(r => String(r.id) === String(id))
      return room != null && (room.total_players ?? 0) > 0
    })

  /**
   * true si la sala actualmente en edición ya tiene jugadores registrados —
   * en ese caso el formulario bloquea el switch de estado (¿Activar sala?),
   * pero permite seguir editando el resto (monedas, precios, bote, rondas,
   * comisión, jackpot, tiempo ficha, min. cartones, modo/horario de inicio).
   */
  const editingRoomHasPlayers = computed(() => hasPlayers([String(roomData.value.id ?? '')]))

  const hasActiveStatus = (ids: string[]): boolean =>
    ids.some(id => {
      const room = rooms.value.find(r => String(r.id) === String(id))
      return room != null && room.status === 'ACTIVE'
    })

  /** Estados en los que el sorteo de la sala aún no ha iniciado ni terminado. */
  const EDITABLE_ROOM_STATUSES = ['ACTIVE', 'INACTIVE', 'OFFLINE']

  /**
   * Solo se puede editar una sala mientras su sorteo no ha iniciado ni
   * finalizado — una vez IN_PROGRESS/FINISHED/CANCELLED ya puede haber
   * premios entregados (bingo_card_prizes), y syncRounds() en el backend
   * borra y recrea las rondas en cada guardado, lo que borraría ese
   * historial (bingo_card_prizes.round_id es cascadeOnDelete).
   */
  const isNotEditableStatus = (ids: string[]): boolean =>
    ids.some(id => {
      const room = rooms.value.find(r => String(r.id) === String(id))
      return room != null && !EDITABLE_ROOM_STATUSES.includes(room.status)
    })

  const canCancelRoom = (ids: string[]): boolean => {
    return ids.length === 1 && hasPlayers(ids) && hasActiveStatus(ids)
  }

  const canActivateNow = (ids: string[]): boolean =>
    ids.length === 1 &&
    ids.some(id => {
      const room = rooms.value.find(r => String(r.id) === String(id))
      return room != null && room.can_activate_now === true
    })

  const isManualRoom = (ids: string[]): boolean =>
    ids.length === 1 &&
    ids.some(id => {
      const room = rooms.value.find(r => String(r.id) === String(id))
      return room != null && room.start_mode === 'MANUAL'
    })

  const extraButtons: ExtraButton[] = [
    {
      label: 'Salas Automáticas',
      class: 'btn-secondary',
      show: (ids: string[]) => !ids.length,
      onClick: (ids: string[]) => {
        if (ids.length) return
        window.location.href = `/bingo-automatic-rooms`
      }
    },
    {
      label: 'Ver Salas',
      class: 'btn-primary',
      show: (ids: string[]) => !ids.length,
      onClick: (ids: string[]) => {
        if (ids.length) return
        window.location.href = `/bingo/game-rooms`
      }
    },
    {
      icon: 'fas fa-info-circle',
      class: 'btn-warning text-white',
      title: 'Ver Detalles',
      show: (ids: string[]) => ids.length === 1 && hasPlayers(ids),
      onClick: openRoomDetails
    },
    {
      icon: 'fas fa-exchange-alt',
      class: 'btn-secondary',
      title: 'Alternar Estado',
      show: false,
      action: 'toggleStatus',
      disabled: (ids: string[]) => isExpired(ids) || hasPlayers(ids),
      disabledReason: (ids: string[]) =>
        isExpired(ids)
          ? 'La sala ya expiró, no se puede cambiar el estado'
          : 'La sala tiene jugadores registrados, no se puede cambiar el estado'
    },
    {
      icon: 'fas fa-copy',
      class: 'btn-primary text-white',
      title: 'Copiar Sala',
      show: (ids: string[]) => ids.length === 1,
      action: 'copy'
    },
    {
      icon: 'fas fa-sign-out-alt',
      class: 'btn-dark',
      title: 'Ir a Sala',
      show: (ids: string[]) => ids.length === 1,
      onClick: goToRoom
    },
    {
      icon: 'fas fa-times',
      class: 'btn-danger',
      title: 'Cancelar Sala',
      show: (ids: string[]) => canCancelRoom(ids),
      onClick: (ids: string[]) => {
        if (ids.length === 1) {
          cancelRoom(ids[0])
        }
      }
    },
    {
      icon: 'fas fa-calendar-alt',
      class: 'btn-warning text-white',
      title: 'Programar Horario',
      show: (ids: string[]) => isManualRoom(ids) && !canActivateNow(ids),
      onClick: openActivateModal
    },
    {
      icon: 'fas fa-play',
      class: 'btn-success text-white',
      title: 'Activar Ahora',
      show: (ids: string[]) => canActivateNow(ids),
      onClick: openActivateModal
    }
  ]

  const subtitle = `<a title="Ir a configuración del Bingo" href="/bingo/config" class="text-primary font-weight-bold">Ir a configuración</a>`
</script>

<template>
  <TableWithFilterBridge
    tableId="bingo-room-table"
    :title="props.title"
    :subtitle="subtitle"
    :columns="props.rooms.columns"
    :rows="rooms"
    :filters="filters"
    search-placeholder="Buscar por código, tipo, modo, tipo bote..."
    showCreateButton
    showEditButton
    showDeleteButton
    :disable-edit="isNotEditableStatus"
    disable-edit-reason="No se puede editar una sala En Juego, Finalizada o Cancelada"
    :disable-delete="hasPlayers"
    disable-delete-reason="No se puede eliminar una sala con jugadores registrados"
    :extra-buttons="extraButtons"
    :loading="loading"
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
      :is-edit="!!roomData.id"
      :room-type-options="roomTypeOptions"
      :game-mode-options="gameModeOptions"
      :prize-pool-type-options="prizePoolTypeOptions"
      :allowed-currencies-options="allowedCurrenciesOptions"
      :bingo-pattern-options="bingoPatternOptions"
      :currencies-with-active-rate="props.currenciesWithActiveRate"
      :existing-rooms="rooms"
      :editing-room-id="roomData.id ? String(roomData.id) : null"
      :editing-room-has-players="editingRoomHasPlayers"
      :sold-currency-ids="soldCurrencyIds"
    />

    <template #footer>
      <small
        v-if="gameBingoRoomFormRef?.draftStatus === 'saving' || gameBingoRoomFormRef?.draftStatus === 'saved'"
        class="text-muted mr-2"
        style="font-size: 0.78rem"
      >
        {{ gameBingoRoomFormRef?.draftStatus === 'saving' ? 'Guardando borrador...' : 'Borrador guardado' }}
      </small>
      <button class="btn btn-sm btn-secondary" :disabled="isSubmitting" @click="showModalForm = false">Cerrar</button>
      <button
        v-if="gameBingoRoomFormRef?.canGoPrev"
        type="button"
        class="btn btn-sm btn-outline-secondary"
        :disabled="isSubmitting"
        @click="gameBingoRoomFormRef?.goPrev()"
      >
        Anterior
      </button>
      <button
        v-if="gameBingoRoomFormRef?.step !== 'review'"
        type="button"
        class="btn btn-sm btn-primary"
        :disabled="isSubmitting || !gameBingoRoomFormRef?.canGoNext"
        @click="gameBingoRoomFormRef?.goNext()"
      >
        Siguiente
      </button>
      <button
        v-else
        class="btn btn-sm btn-primary"
        :disabled="!canAttemptSave || isSubmitting || loading"
        @click="onSaveClick"
      >
        <span v-if="isSubmitting" class="spinner-border spinner-border-sm mr-1" role="status" aria-hidden="true"></span>
        {{ isSubmitting ? 'Guardando...' : 'Guardar' }}
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
      :game-mode-options="gameModeOptions"
      :prize-pool-type-options="prizePoolTypeOptions"
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
</template>
