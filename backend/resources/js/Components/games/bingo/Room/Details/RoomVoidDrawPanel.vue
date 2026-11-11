<script setup lang="ts">
/**
 * Anular un sorteo ya iniciado que quedó trabado o corrupto (solo superadmin):
 * la sala pasa a cancelada y se reembolsan los cartones. El backend lo
 * rechaza si ya hay premios registrados (se resuelven en "Premios por
 * resolver"). Ver BingoRoomCrudService::voidStartedDraw().
 */
import { computed, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import backendClient from '@js/plugins/axios'
import { showToast } from '@js/utils/toastNotify'
import type { BingoRoom } from '@js/types/games/bingo/room/bingo-room'

const props = defineProps<{ room: BingoRoom }>()

const page = usePage()
const busy = ref(false)
const done = ref(false)

const isSuperadmin = computed(() => {
  const userRoles = ((page.props.auth as { user?: { userRoles?: { role?: { prefix?: string } }[] } })?.user?.userRoles) ?? []

  return userRoles.some(ur => ur.role?.prefix === 'superadmin')
})

const visible = computed(() => isSuperadmin.value && String(props.room.status) === 'IN_PROGRESS' && !done.value)

async function voidDraw() {
  const { isConfirmed, value } = await Swal.fire({
    title: 'Anular sorteo',
    text: 'La sala se cancelará y se reembolsarán todos los cartones. Úsalo solo si el sorteo quedó trabado o es inválido. No se puede deshacer.',
    icon: 'warning',
    input: 'textarea',
    inputLabel: 'Motivo (queda en la auditoría de la sala)',
    inputAttributes: { maxlength: '255' },
    inputValidator: v => (!v || v.trim().length < 10 ? 'Escribe el motivo (mínimo 10 caracteres)' : undefined),
    showCancelButton: true,
    confirmButtonText: 'Anular y reembolsar',
    confirmButtonColor: '#dc2626',
    cancelButtonText: 'Volver',
    reverseButtons: true,
    focusCancel: true
  })
  if (!isConfirmed || !value) return

  busy.value = true
  try {
    await backendClient.post(`/bingo-rooms/${props.room.id}/void-draw`, { reason: String(value).trim() })
    done.value = true
    showToast('Sorteo anulado. Los reembolsos se están procesando.', 'success')
  } catch (e) {
    const message = (e as { response?: { data?: { message?: string } } })?.response?.data?.message
    showToast(message ?? 'No se pudo anular el sorteo.', 'error')
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <div v-if="visible" class="alert alert-danger d-flex align-items-center justify-content-between mb-3">
    <span class="small mr-2">
      <i class="fas fa-triangle-exclamation mr-1" />
      ¿Sorteo trabado o inválido? Puedes anularlo y reembolsar los cartones.
    </span>
    <button
      type="button"
      class="btn btn-sm btn-danger text-nowrap"
      :disabled="busy"
      @click="voidDraw"
    >
      <i class="fas fa-ban mr-1" />Anular sorteo
    </button>
  </div>
</template>
