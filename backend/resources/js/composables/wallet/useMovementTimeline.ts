import { computed, type ComputedRef } from 'vue'
import type { Wallet, WalletMovement } from '@js/types/wallet'
import { formatAmount } from './useWalletOperationFormatters'

export interface TimelineStep {
  label:    string
  sub:      string | null
  done:     boolean
  active:   boolean
  rejected: boolean
  icon:     string
  date:     string | null
}

/** Construye los pasos del timeline de progreso, según el tipo y estado del movimiento. */
export function useMovementTimeline(movement: ComputedRef<WalletMovement | null>, activeWallet: ComputedRef<Wallet | null>) {
  const detailSteps = computed((): TimelineStep[] => {
    const mov = movement.value
    if (!mov) return []

    const sym = activeWallet.value?.currency.symbol ?? ''
    const decimals = activeWallet.value?.currency.decimal_places ?? 2
    const amt = (v: number) => formatAmount(v, decimals)

    const isDep  = mov.type === 'DEPOSIT'
    const isWith = mov.type === 'WITHDRAWAL'

    if (isDep || isWith) {
      const done     = mov.status === 'COMPLETED'
      const rejected = mov.status === 'REJECTED'
      const pending  = mov.status === 'PENDING'

      return [
        {
          label:    'Solicitud recibida',
          sub:      isDep
            ? `Depósito de ${sym} ${amt(mov.amount)} registrado`
            : `Retiro de ${sym} ${amt(mov.amount)} solicitado`,
          done:     true,
          active:   false,
          rejected: false,
          icon:     'fa-paper-plane',
          date:     mov.created_at,
        },
        {
          label:    pending ? 'En revisión' : (done ? 'Revisión completada' : 'Revisión fallida'),
          sub:      pending
            ? 'Nuestro equipo está verificando tu solicitud'
            : done
              ? (isDep ? 'Comprobante verificado correctamente' : 'Solicitud de retiro aprobada')
              : (isDep ? 'El comprobante no pudo ser verificado' : 'La solicitud fue rechazada'),
          done:     done || rejected,
          active:   pending,
          rejected: false,
          icon:     pending ? 'fa-hourglass-half' : (done ? 'fa-check' : 'fa-times'),
          date:     !pending && mov.processed_at ? mov.processed_at : null,
        },
        {
          label:    done
            ? (isDep ? 'Fondos acreditados' : 'Retiro enviado')
            : rejected
              ? (isDep ? 'Depósito rechazado' : 'Retiro cancelado')
              : (isDep ? 'Pendiente de acreditación' : 'Pendiente de envío'),
          sub:      done
            ? (isDep
                ? `+${sym} ${amt(mov.amount)} en tu billetera`
                : `${sym} ${amt(mov.amount)} transferidos`)
            : rejected
              ? (isDep ? 'El monto no fue acreditado' : 'El saldo fue reintegrado a tu cuenta')
              : null,
          done,
          active:   false,
          rejected,
          icon:     done ? 'fa-check-circle' : (rejected ? 'fa-times-circle' : 'fa-circle'),
          date:     (done || rejected) && mov.processed_at ? mov.processed_at : null,
        },
      ]
    }

    // PURCHASE
    if (mov.type === 'PURCHASE') {
      return [
        {
          label:    'Pago procesado',
          sub:      `${sym} ${amt(mov.amount)} descontados de tu saldo`,
          done:     true, active: false, rejected: false,
          icon:     'fa-check-circle',
          date:     mov.created_at,
        },
        {
          label:    'Cartón(es) asignados',
          sub:      mov.description ?? 'Tu participación en la sala fue registrada',
          done:     true, active: false, rejected: false,
          icon:     'fa-ticket-alt',
          date:     mov.created_at,
        },
      ]
    }

    // PRIZE
    if (mov.type === 'PRIZE') {
      return [
        {
          label:    '¡Premio ganado!',
          sub:      mov.description ?? 'Felicitaciones por tu victoria',
          done:     true, active: false, rejected: false,
          icon:     'fa-trophy',
          date:     mov.created_at,
        },
        {
          label:    'Fondos acreditados',
          sub:      `+${sym} ${amt(mov.amount)} añadidos a tu billetera`,
          done:     true, active: false, rejected: false,
          icon:     'fa-check-circle',
          date:     mov.created_at,
        },
      ]
    }

    // TRIVIA_ENTRY
    return [
      {
        label:    'Entrada registrada',
        sub:      mov.description ?? 'Participación en sala de Trivia',
        done:     true, active: false, rejected: false,
        icon:     'fa-brain',
        date:     mov.created_at,
      },
    ]
  })

  return { detailSteps }
}
