<script setup lang="ts">
import { useWallet } from '@js/composables/wallet/useWallet'
import type { WalletPageProps } from '@js/types/wallet'

const props = defineProps<WalletPageProps>()

const {
  wallets,
  activeWallet,
  modalOpen,
  operation,
  amount,
  loading,
  errorMsg,
  successMsg,
  canWithdraw,
  formattedBalance,
  openModal,
  closeModal,
  selectWallet,
  submit,
} = useWallet(props.wallets)
</script>

<template>
  <div class="container-fluid py-4">

    <!-- Encabezado -->
    <div class="row mb-4">
      <div class="col-12">
        <h4 class="mb-0">
          <i class="fas fa-wallet mr-2 text-primary"></i>Mi Billetera
        </h4>
        <p class="text-muted mb-0" style="font-size:0.875rem">
          Gestiona tus depósitos y retiros
        </p>
      </div>
    </div>

    <!-- Sin billeteras -->
    <div v-if="!wallets.length" class="row">
      <div class="col-12">
        <div class="card shadow-sm">
          <div class="card-body text-center py-5">
            <i class="fas fa-wallet fa-3x text-muted mb-3"></i>
            <h6 class="text-muted">No tienes billeteras activas</h6>
            <p class="text-muted mb-0" style="font-size:0.85rem">
              Contacta al soporte para activar tu billetera.
            </p>
          </div>
        </div>
      </div>
    </div>

    <template v-else>

      <!-- Selector de billetera (si hay varias) -->
      <div v-if="wallets.length > 1" class="row mb-3">
        <div class="col-12">
          <div class="d-flex flex-wrap gap-2">
            <button
              v-for="wallet in wallets"
              :key="wallet.id"
              type="button"
              class="btn btn-sm"
              :class="activeWallet?.id === wallet.id ? 'btn-primary' : 'btn-outline-secondary'"
              @click="selectWallet(wallet)"
            >
              {{ wallet.currency.symbol }} {{ wallet.currency.iso_code }}
            </button>
          </div>
        </div>
      </div>

      <!-- Tarjeta de saldo -->
      <div class="row mb-4">
        <div class="col-12 col-md-6 col-lg-4">
          <div class="card shadow-sm border-0" style="background: linear-gradient(135deg, #1a237e 0%, #0d47a1 100%); color:#fff;">
            <div class="card-body py-4">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                  <p class="mb-0" style="font-size:0.8rem; opacity:0.8">Saldo disponible</p>
                  <h2 class="mb-0 font-weight-bold">
                    {{ activeWallet?.currency.symbol }} {{ formattedBalance }}
                  </h2>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center"
                     style="width:42px;height:42px;background:rgba(255,255,255,0.15)">
                  <i class="fas fa-coins"></i>
                </div>
              </div>
              <div class="d-flex align-items-center">
                <span
                  class="badge mr-2"
                  :class="{
                    'badge-success': activeWallet?.status === 'ACTIVE',
                    'badge-warning': activeWallet?.status === 'INACTIVE',
                    'badge-danger':  activeWallet?.status === 'BLOCKED',
                  }"
                >
                  {{ activeWallet?.status === 'ACTIVE' ? 'Activa' :
                     activeWallet?.status === 'INACTIVE' ? 'Inactiva' : 'Bloqueada' }}
                </span>
                <small style="opacity:0.7">{{ activeWallet?.currency.name }}</small>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Acciones -->
      <div class="row mb-4">
        <div class="col-12">
          <div class="d-flex flex-wrap" style="gap:0.75rem">
            <button
              type="button"
              class="btn btn-success"
              @click="openModal('deposit')"
            >
              <i class="fas fa-arrow-down mr-2"></i>Depositar
            </button>
            <button
              type="button"
              class="btn btn-warning"
              :disabled="!canWithdraw"
              @click="openModal('withdraw')"
            >
              <i class="fas fa-arrow-up mr-2"></i>Retirar
            </button>
          </div>
        </div>
      </div>

    </template>

    <!-- ════════════════════════════════════════
         MODAL DEPÓSITO / RETIRO
         ════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition
        enter-active-class="duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="modalOpen"
          class="fixed inset-0 z-[200] flex items-center justify-center p-4"
          @click.self="closeModal"
        >
          <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="closeModal" />

          <Transition
            enter-active-class="duration-200 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
          >
            <div
              v-if="modalOpen"
              class="relative bg-white rounded-2xl shadow-2xl w-full"
              style="max-width: 420px"
              @click.stop
            >
              <!-- Header -->
              <div
                class="flex items-center justify-between p-5 rounded-t-2xl"
                :class="operation === 'deposit'
                  ? 'bg-gradient-to-r from-green-50 to-emerald-50 border-b border-green-100'
                  : 'bg-gradient-to-r from-yellow-50 to-amber-50 border-b border-yellow-100'"
              >
                <div class="flex items-center gap-3">
                  <div
                    class="p-2 rounded-xl shadow"
                    :class="operation === 'deposit' ? 'bg-green-500' : 'bg-yellow-500'"
                  >
                    <i
                      class="text-white text-sm"
                      :class="operation === 'deposit' ? 'fas fa-arrow-down' : 'fas fa-arrow-up'"
                    />
                  </div>
                  <div>
                    <h6 class="font-bold text-gray-900 mb-0">
                      {{ operation === 'deposit' ? 'Depositar fondos' : 'Retirar fondos' }}
                    </h6>
                    <p class="text-xs text-gray-500 mb-0">
                      Billetera {{ activeWallet?.currency.iso_code }}
                    </p>
                  </div>
                </div>
                <button
                  type="button"
                  class="p-1.5 hover:bg-white/70 rounded-lg transition-colors text-gray-400 hover:text-gray-600"
                  @click="closeModal"
                >
                  <i class="fas fa-times text-sm" />
                </button>
              </div>

              <!-- Body -->
              <div class="p-5 space-y-4">

                <!-- Saldo actual -->
                <div class="bg-gray-50 rounded-xl px-4 py-3 flex justify-between items-center">
                  <span class="text-sm text-gray-500">Saldo actual</span>
                  <span class="font-semibold text-gray-900">
                    {{ activeWallet?.currency.symbol }} {{ formattedBalance }}
                  </span>
                </div>

                <!-- Monto -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    Monto a {{ operation === 'deposit' ? 'depositar' : 'retirar' }}
                  </label>
                  <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-semibold text-sm">
                      {{ activeWallet?.currency.symbol }}
                    </span>
                    <input
                      v-model="amount"
                      type="number"
                      min="0.01"
                      step="0.01"
                      placeholder="0.00"
                      class="w-full border border-gray-300 rounded-xl pl-9 pr-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300 focus:border-transparent"
                      :disabled="loading"
                      @keyup.enter="submit"
                    />
                  </div>
                </div>

                <!-- Mensajes -->
                <div
                  v-if="errorMsg"
                  class="flex items-start gap-2 bg-red-50 border border-red-200 rounded-xl px-3 py-2.5 text-sm text-red-700"
                >
                  <i class="fas fa-exclamation-circle mt-0.5 flex-shrink-0" />
                  <span>{{ errorMsg }}</span>
                </div>
                <div
                  v-if="successMsg"
                  class="flex items-start gap-2 bg-green-50 border border-green-200 rounded-xl px-3 py-2.5 text-sm text-green-700"
                >
                  <i class="fas fa-check-circle mt-0.5 flex-shrink-0" />
                  <span>{{ successMsg }}</span>
                </div>

              </div>

              <!-- Footer -->
              <div class="px-5 pb-5 flex gap-3">
                <button
                  type="button"
                  class="flex-1 py-2.5 border border-gray-300 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors"
                  :disabled="loading"
                  @click="closeModal"
                >
                  Cancelar
                </button>
                <button
                  type="button"
                  class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-white transition-all disabled:opacity-50"
                  :class="operation === 'deposit' ? 'bg-green-500 hover:bg-green-600' : 'bg-yellow-500 hover:bg-yellow-600'"
                  :disabled="loading"
                  @click="submit"
                >
                  <span v-if="loading">
                    <i class="fas fa-spinner fa-spin mr-1" /> Procesando...
                  </span>
                  <span v-else>
                    {{ operation === 'deposit' ? 'Confirmar depósito' : 'Confirmar retiro' }}
                  </span>
                </button>
              </div>

            </div>
          </Transition>
        </div>
      </Transition>
    </Teleport>

  </div>
</template>
