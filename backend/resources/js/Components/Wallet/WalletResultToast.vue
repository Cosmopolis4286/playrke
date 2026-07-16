<script setup lang="ts">
export interface ResultToastData {
  type: 'approved' | 'rejected'
  movementType: 'DEPOSIT' | 'WITHDRAWAL'
  amount: number
  currencySymbol: string
}

defineProps<{
  toast: ResultToastData | null
}>()

defineEmits<{
  (e: 'dismiss'): void
}>()
</script>

<template>
  <Transition name="wt-toast">
    <div
      v-if="toast"
      class="wt-toast"
      :class="toast.type === 'approved' ? 'wt-toast--approved' : 'wt-toast--rejected'"
    >
      <div class="wt-toast__icon">
        <i :class="toast.type === 'approved' ? 'fas fa-check-circle' : 'fas fa-times-circle'"></i>
      </div>
      <div class="wt-toast__body">
        <p class="wt-toast__title">
          <template v-if="toast.type === 'approved'">
            {{ toast.movementType === 'DEPOSIT' ? '✅ Depósito aprobado' : '✅ Retiro aprobado' }}
          </template>
          <template v-else>
            {{ toast.movementType === 'DEPOSIT' ? '❌ Depósito rechazado' : '↩️ Retiro rechazado — saldo reintegrado' }}
          </template>
        </p>
        <p class="wt-toast__sub">
          {{ toast.currencySymbol }} {{ toast.amount.toLocaleString('es-VE', { minimumFractionDigits: 2 }) }}
        </p>
      </div>
      <button class="wt-toast__close" @click="$emit('dismiss')">
        <i class="fas fa-times"></i>
      </button>
    </div>
  </Transition>
</template>

<style scoped>
.wt-toast {
  position: fixed;
  bottom: 1.5rem;
  right: 1.5rem;
  z-index: 9999;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.9rem 1.1rem;
  border-radius: 14px;
  min-width: 280px;
  max-width: 380px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.18);
  animation: wt-slide-in 0.35s cubic-bezier(0.34,1.56,0.64,1);
}

.wt-toast--approved {
  background: linear-gradient(135deg, #22c55e, #16a34a);
  color: #fff;
}

.wt-toast--rejected {
  background: linear-gradient(135deg, #ef4444, #dc2626);
  color: #fff;
}

.wt-toast__icon { font-size: 1.5rem; flex-shrink: 0; }

.wt-toast__body { flex: 1; min-width: 0; }

.wt-toast__title {
  font-size: 0.875rem;
  font-weight: 700;
  margin: 0 0 2px;
  line-height: 1.2;
}

.wt-toast__sub {
  font-size: 0.78rem;
  opacity: 0.88;
  margin: 0;
}

.wt-toast__close {
  background: rgba(255,255,255,0.25);
  border: none;
  color: #fff;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 0.65rem;
  flex-shrink: 0;
  transition: background 0.15s;
}
.wt-toast__close:hover { background: rgba(255,255,255,0.4); }

/* Transición del toast */
.wt-toast-enter-active { animation: wt-slide-in 0.35s cubic-bezier(0.34,1.56,0.64,1); }
.wt-toast-leave-active { animation: wt-slide-out 0.25s ease forwards; }

@keyframes wt-slide-in {
  from { transform: translateX(110%); opacity: 0; }
  to   { transform: translateX(0);    opacity: 1; }
}

@keyframes wt-slide-out {
  from { transform: translateX(0);    opacity: 1; }
  to   { transform: translateX(110%); opacity: 0; }
}
</style>
