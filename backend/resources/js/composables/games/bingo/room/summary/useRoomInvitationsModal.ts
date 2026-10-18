import { ref, type Ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast, showToastConfirmAction } from '@/js/utils/toastNotify'
import type { BingoRoom } from '@/js/types/games/bingo/room/bingo-room'

export interface BingoRoomInvitation {
  id: string
  type: 'player' | 'phone'
  name: string
  detail: string | null
  created_at: string | null
}

export type InvitationType = 'player' | 'phone'

/** Requisitos de acceso automático (null = sin requisito). */
export interface VipRequirements {
  min_level: number | null
  min_spend_usd: number | null
  spend_window_days: number
}

/**
 * Lista de invitados de una sala VIP (GET/POST/DELETE
 * /bingo-rooms/{room}/invitations). Solo la gestiona el creador o un admin.
 */
export function useRoomInvitationsModal(rooms: Ref<BingoRoom[]>) {
  const showInvitationsModal = ref(false)
  const invitationsRoom = ref<BingoRoom | null>(null)
  const invitations = ref<BingoRoomInvitation[]>([])
  const loadingInvitations = ref(false)
  const savingInvitation = ref(false)
  const invitationError = ref<string | null>(null)
  const requirements = ref<VipRequirements>({ min_level: null, min_spend_usd: null, spend_window_days: 30 })
  const savingRequirements = ref(false)

  const endpoint = (roomId: string | number) => `/bingo-rooms/${roomId}/invitations`

  function errorMessage(error: any, fallback: string): string {
    const errors = error?.response?.data?.errors
    if (errors && typeof errors === 'object') {
      const first = Object.values(errors).flat()[0]
      if (typeof first === 'string') return first
    }
    return error?.response?.data?.message ?? fallback
  }

  async function openInvitationsModal(ids: string[]): Promise<void> {
    if (ids.length !== 1) return
    const room = rooms.value.find(r => String(r.id) === String(ids[0]))
    if (!room) return

    invitationsRoom.value = room
    invitations.value = []
    invitationError.value = null
    showInvitationsModal.value = true
    loadingInvitations.value = true
    try {
      const { data } = await backendClient.get(endpoint(room.id))
      invitations.value = data.invitations ?? []
      if (data.requirements) requirements.value = data.requirements
    } catch (error: any) {
      invitationError.value = errorMessage(error, 'No se pudo cargar la lista de invitados.')
    } finally {
      loadingInvitations.value = false
    }
  }

  async function addInvitation(payload: { type: InvitationType; identifier?: string; phone?: string; label?: string }): Promise<boolean> {
    if (!invitationsRoom.value) return false
    savingInvitation.value = true
    invitationError.value = null
    try {
      const { data } = await backendClient.post(endpoint(invitationsRoom.value.id), payload)
      invitations.value = data.invitations ?? []
      showToast('Invitado agregado', 'success')
      return true
    } catch (error: any) {
      invitationError.value = errorMessage(error, 'No se pudo agregar el invitado.')
      return false
    } finally {
      savingInvitation.value = false
    }
  }

  async function removeInvitation(invitation: BingoRoomInvitation): Promise<void> {
    if (!invitationsRoom.value) return
    const confirm = await showToastConfirmAction({
      html: `¿Quitar a "<b>${invitation.name}</b>" de la lista de invitados? Ya no podrá entrar ni comprar cartones.`,
      icon: 'warning',
      confirmButtonText: 'Sí, quitar',
    })
    if (!confirm) return

    try {
      const { data } = await backendClient.delete(`${endpoint(invitationsRoom.value.id)}/${invitation.id}`)
      invitations.value = data.invitations ?? []
      showToast('Invitado quitado', 'success')
    } catch (error: any) {
      showToast(errorMessage(error, 'No se pudo quitar el invitado.'), 'error')
    }
  }

  async function saveRequirements(payload: { min_level: number | null; min_spend_usd: number | null }): Promise<void> {
    if (!invitationsRoom.value) return
    savingRequirements.value = true
    try {
      const { data } = await backendClient.patch(`${endpoint(invitationsRoom.value.id)}/requirements`, payload)
      requirements.value = data.requirements
      showToast('Requisitos guardados', 'success')
    } catch (error: any) {
      showToast(errorMessage(error, 'No se pudieron guardar los requisitos.'), 'error')
    } finally {
      savingRequirements.value = false
    }
  }

  return {
    showInvitationsModal, invitationsRoom, invitations, loadingInvitations, savingInvitation, invitationError,
    requirements, savingRequirements,
    openInvitationsModal, addInvitation, removeInvitation, saveRequirements,
  }
}
