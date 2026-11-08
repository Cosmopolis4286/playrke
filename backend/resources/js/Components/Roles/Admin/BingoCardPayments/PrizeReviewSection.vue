<script setup lang="ts">
/**
   * Premios de Bingo por resolver (D4/D5): suspendidos (ganador sin billetera
   * o comprador externo) o con los reintentos automáticos agotados. El admin
   * reintenta el pago, registra la entrega fuera del sistema o anula el premio
   * — ver BingoPrizeReviewService.
   */
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import Swal from 'sweetalert2'

import RelativeTime from '@/js/Components/Common/RelativeTime/RelativeTime.vue'
import backendClient from '@/js/plugins/axios'
import { showToast } from '@/js/utils/toastNotify'

import type { BingoPrizeReview } from '@/js/types/games/bingo/payment/card-payment-review'

defineProps<{ prizes: BingoPrizeReview[] }>()

const busyId = ref<string | null>(null)

const REASON_LABEL: Record<BingoPrizeReview['reason'], string> = {
  external: 'Comprador externo',
  no_wallet: 'Sin billetera activa',
  exhausted: 'Reintentos agotados'
}

function errorMessage(e: unknown, fallback: string): string {
  const data = (e as { response?: { data?: { message?: string, errors?: Record<string, string[]> } } })?.response?.data
  const firstValidation = data?.errors ? Object.values(data.errors)[0]?.[0] : undefined

  return firstValidation ?? data?.message ?? fallback
}

async function send(prize: BingoPrizeReview, action: 'retry' | 'deliver' | 'cancel', payload: Record<string, string>, success: string) {
  busyId.value = prize.id
  try {
    await backendClient.post(`/bingo/prize-review/${prize.id}/${action}`, payload)
    showToast(success, 'success')
    router.reload({ only: ['prizeReviews'] })
  } catch (e) {
    showToast(errorMessage(e, 'No se pudo completar la acción.'), 'error')
  } finally {
    busyId.value = null
  }
}

async function retry(prize: BingoPrizeReview) {
  const { isConfirmed } = await Swal.fire({
    title: 'Reintentar el pago',
    text: `Se intentará acreditar ${prize.amount_label} a la billetera de ${prize.winner}.`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Reintentar',
    cancelButtonText: 'Cancelar',
    reverseButtons: true
  })
  if (isConfirmed) await send(prize, 'retry', {}, 'Reintento enviado. Si sigue sin billetera, el premio vuelve a la cola.')
}

async function deliver(prize: BingoPrizeReview) {
  const { isConfirmed, value } = await Swal.fire({
    title: 'Registrar entrega fuera del sistema',
    text: `Confirma que ${prize.winner} recibió ${prize.amount_label} (efectivo, transferencia o pago móvil). No se acredita a ninguna billetera.`,
    icon: 'warning',
    input: 'text',
    inputLabel: 'Referencia de la entrega',
    inputPlaceholder: 'Ej.: transferencia 00123456789',
    inputAttributes: { maxlength: '100' },
    inputValidator: (v) => (!v || v.trim().length < 3 ? 'La referencia es obligatoria (mínimo 3 caracteres)' : undefined),
    showCancelButton: true,
    confirmButtonText: 'Registrar entrega',
    cancelButtonText: 'Cancelar',
    reverseButtons: true,
    focusCancel: true
  })
  if (isConfirmed && value) await send(prize, 'deliver', { reference: String(value).trim() }, 'Entrega registrada.')
}

async function cancel(prize: BingoPrizeReview) {
  const { isConfirmed, value } = await Swal.fire({
    title: 'Anular premio',
    text: `El premio de ${prize.amount_label} no se pagará. Esta acción no se puede deshacer.`,
    icon: 'warning',
    input: 'textarea',
    inputLabel: 'Motivo',
    inputAttributes: { maxlength: '255' },
    inputValidator: (v) => (!v || v.trim().length < 5 ? 'El motivo es obligatorio (mínimo 5 caracteres)' : undefined),
    showCancelButton: true,
    confirmButtonText: 'Anular',
    confirmButtonColor: '#dc2626',
    cancelButtonText: 'Volver',
    reverseButtons: true,
    focusCancel: true
  })
  if (isConfirmed && value) await send(prize, 'cancel', { reason: String(value).trim() }, 'Premio anulado.')
}
</script>

<template>
  <div v-if="prizes.length" class="card mt-4">
    <div class="card-header">
      <h3 class="card-title mb-0">
        <i class="fas fa-trophy mr-1" />Premios por resolver
      </h3>
      <small class="text-muted d-block">
        El pago automático no pudo completarlos: reintenta el pago, registra la entrega fuera del sistema o anúlalos.
      </small>
    </div>
    <div class="card-body p-0 table-responsive">
      <table class="table table-sm table-hover mb-0">
        <thead>
          <tr>
            <th>Sala</th>
            <th>Ganador</th>
            <th class="text-right">
              Premio
            </th>
            <th>Motivo</th>
            <th>Desde</th>
            <th class="text-right">
              Acciones
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="prize in prizes" :key="prize.id">
            <td class="text-nowrap">
              {{ prize.room_uuid_code ?? '—' }}
            </td>
            <td>{{ prize.winner }}</td>
            <td class="text-right font-weight-bold text-nowrap">
              {{ prize.amount_label }}
            </td>
            <td>
              <span class="badge" :class="prize.reason === 'exhausted' ? 'bg-danger' : 'bg-warning text-dark'">{{ REASON_LABEL[prize.reason] }}</span>
              <small v-if="prize.reason === 'exhausted'" class="d-block text-muted" :title="prize.last_error ?? ''">
                {{ prize.attempts }} intentos<span v-if="prize.last_error"> · {{ prize.last_error }}</span>
              </small>
            </td>
            <td class="text-nowrap">
              <RelativeTime :value="prize.created_at" />
            </td>
            <td class="text-right text-nowrap">
              <button
                v-if="prize.reason !== 'external'"
                type="button"
                class="btn btn-xs btn-outline-primary mr-1"
                :disabled="busyId === prize.id"
                @click="retry(prize)"
              >
                <i class="fas fa-rotate-right mr-1" />Reintentar
              </button>
              <button
                v-if="prize.prize_status !== 'processing'"
                type="button"
                class="btn btn-xs btn-outline-success mr-1"
                :disabled="busyId === prize.id"
                @click="deliver(prize)"
              >
                <i class="fas fa-hand-holding-dollar mr-1" />Entregado
              </button>
              <button
                v-if="prize.prize_status !== 'processing'"
                type="button"
                class="btn btn-xs btn-outline-danger"
                :disabled="busyId === prize.id"
                @click="cancel(prize)"
              >
                <i class="fas fa-ban mr-1" />Anular
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
