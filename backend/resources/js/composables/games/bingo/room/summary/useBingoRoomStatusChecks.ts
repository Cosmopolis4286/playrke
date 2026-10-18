import type { Ref } from 'vue'
import type { BingoRoom } from '@/js/types/games/bingo/room/bingo-room'

/** Estados en los que el sorteo de la sala aún no ha iniciado ni terminado. */
const EDITABLE_ROOM_STATUSES = ['ACTIVE', 'INACTIVE', 'OFFLINE']

/**
 * Predicados sobre el estado de las salas, usados por la tabla de resumen
 * (deshabilitar acciones, mostrar/ocultar botones extra). Todos operan sobre
 * el snapshot actual de `rooms` — no hacen llamadas propias al backend.
 */
export function useBingoRoomStatusChecks(rooms: Ref<BingoRoom[]>) {
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

  const hasActiveStatus = (ids: string[]): boolean =>
    ids.some(id => {
      const room = rooms.value.find(r => String(r.id) === String(id))
      return room != null && room.status === 'ACTIVE'
    })

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

  const canCancelRoom = (ids: string[]): boolean =>
    ids.length === 1 && hasPlayers(ids) && hasActiveStatus(ids)

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

  const canManageInvitations = (ids: string[]): boolean =>
    ids.length === 1 &&
    rooms.value.some(r => String(r.id) === String(ids[0]) && r.can_manage_invitations === true)

  return {
    isExpired, hasPlayers, hasActiveStatus, isNotEditableStatus,
    canCancelRoom, canActivateNow, isManualRoom, canManageInvitations,
  }
}
