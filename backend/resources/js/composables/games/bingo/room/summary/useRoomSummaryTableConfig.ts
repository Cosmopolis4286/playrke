import { computed, type ComputedRef } from 'vue'
import type { ExtraButton } from '@js/types/common/table/extra-buttons'
import type { TableFilterInterface } from '@js/types/common/table/table-filter'

interface OptionLike { value: string; label: string }
interface PrizePoolTypeOption { value: string; code: string; label: string }

/** Filtros de la tabla de resumen de salas — "Origen" y "Comercializadora" solo aplican a admin/superadmin. */
export function useRoomSummaryFilters(deps: {
  roomTypeOptions: ComputedRef<OptionLike[]>
  connectionTypeOptions: ComputedRef<OptionLike[]>
  prizePoolTypeOptions: ComputedRef<PrizePoolTypeOption[]>
  allowedCurrenciesOptions: ComputedRef<OptionLike[]>
  isAdmin: ComputedRef<boolean>
  commerceOptions?: ComputedRef<OptionLike[]>
}) {
  const { roomTypeOptions, connectionTypeOptions, prizePoolTypeOptions, allowedCurrenciesOptions, isAdmin, commerceOptions } = deps

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
        label: 'tipo de conexión',
        placeholder: 'Filtrar por tipo de conexión',
        options: connectionTypeOptions.value
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
    ]

    // Agregar filtro from_house y commerce_id solo para admin/superadmin
    if (isAdmin.value) {
      baseFilters.push({
        key: 'from_house',
        label: 'Origen',
        placeholder: 'Filtrar por origen',
        options: [
          { value: 'Y', label: 'Salas propias de la app' },
          { value: 'N', label: 'Salas de usuarios' }
        ]
      })

      if (commerceOptions?.value.length) {
        baseFilters.push({
          key: 'commerce_id',
          label: 'Comercializadora',
          placeholder: 'Filtrar por comercializadora',
          options: commerceOptions.value
        })
      }
    }

    return baseFilters
  })

  return { filters }
}

/** Botones de acción extra de la tabla de resumen de salas. */
export function useRoomSummaryExtraButtons(deps: {
  isExpired: (ids: string[]) => boolean
  hasPlayers: (ids: string[]) => boolean
  canCancelRoom: (ids: string[]) => boolean
  canActivateNow: (ids: string[]) => boolean
  isManualRoom: (ids: string[]) => boolean
  openRoomDetails: (ids: string[]) => void
  openActivateModal: (ids: string[]) => void
  goToRoom: (ids: string[]) => void
  cancelRoom: (roomId: string) => void
}): ExtraButton[] {
  const { isExpired, hasPlayers, canCancelRoom, canActivateNow, isManualRoom, openRoomDetails, openActivateModal, goToRoom, cancelRoom } = deps

  return [
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
}
