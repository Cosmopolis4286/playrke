<script setup lang="ts">
import type { WalletMovement } from '@js/types/wallet'

defineProps<{
  movement: WalletMovement
}>()
</script>

<template>
  <div
    class="mv-detail__feedback"
    :class="{
      'mv-detail__feedback--pending':   movement.status === 'PENDING',
      'mv-detail__feedback--completed': movement.status === 'COMPLETED',
      'mv-detail__feedback--rejected':  movement.status === 'REJECTED',
    }"
  >
    <i :class="{
      'fas fa-hourglass-half': movement.status === 'PENDING',
      'fas fa-check-circle':  movement.status === 'COMPLETED',
      'fas fa-times-circle':  movement.status === 'REJECTED',
      'fas fa-ban':           movement.status === 'CANCELLED',
    }"></i>
    <span>
      <template v-if="movement.status === 'PENDING'">
        Tu solicitud está siendo revisada por nuestro equipo. Recibirás una notificación cuando sea procesada.
      </template>
      <template v-else-if="movement.status === 'COMPLETED'">
        {{
          movement.type === 'DEPOSIT'
            ? 'Los fondos han sido acreditados exitosamente a tu billetera.'
            : movement.type === 'WITHDRAWAL'
            ? 'Tu retiro fue procesado exitosamente.'
            : 'Transacción completada exitosamente.'
        }}
      </template>
      <template v-else-if="movement.status === 'REJECTED'">
        {{
          movement.type === 'DEPOSIT'
            ? 'Tu depósito fue rechazado. El monto no fue acreditado.'
            : 'Tu retiro fue rechazado. El monto ha sido reintegrado a tu saldo.'
        }}
      </template>
      <template v-else>
        Esta transacción fue cancelada.
      </template>
    </span>
  </div>
</template>

<style scoped>
.mv-detail__feedback {
  display: flex;
  align-items: flex-start;
  gap: 0.65rem;
  padding: 0.85rem 1rem;
  border-radius: 10px;
  font-size: 0.83rem;
  line-height: 1.5;
}
.mv-detail__feedback i { font-size: 1rem; flex-shrink: 0; margin-top: 1px; }
.mv-detail__feedback--pending   { background: rgba(245, 158, 11, .14); color: #d97706; }
.mv-detail__feedback--completed { background: rgba(34, 197, 94, .14); color: #4ade80; }
.mv-detail__feedback--rejected  { background: rgba(220, 38, 38, .14); color: #f87171; }
</style>
