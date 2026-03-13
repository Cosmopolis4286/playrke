<script setup lang="ts">
import { useWallet } from '@js/composables/wallet/useWallet'
import type { WalletPageProps, WalletMovementType } from '@js/types/wallet'

const props = defineProps<WalletPageProps>()

const {
  wallets,
  activeWallet,
  movements,
  movementsMeta,
  movementsLoading,
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
  loadMoreMovements,
  submit,
} = useWallet(props.wallets)

// ─── helpers visuales para movimientos ───────────────────────────────────────
const movementMeta: Record<WalletMovementType, { icon: string; label: string; colorClass: string; bgClass: string; sign: '+' | '-' }> = {
  DEPOSIT:   { icon: 'fas fa-arrow-down',  label: 'Depósito',         colorClass: 'text-emerald-600', bgClass: 'bg-emerald-100', sign: '+' },
  PRIZE:     { icon: 'fas fa-trophy',      label: 'Premio ganado',     colorClass: 'text-amber-600',   bgClass: 'bg-amber-100',   sign: '+' },
  PURCHASE:  { icon: 'fas fa-ticket-alt',  label: 'Compra de cartón',  colorClass: 'text-rose-600',    bgClass: 'bg-rose-100',    sign: '-' },
  WITHDRAWAL:{ icon: 'fas fa-arrow-up',    label: 'Retiro',            colorClass: 'text-blue-600',    bgClass: 'bg-blue-100',    sign: '-' },
}

function formatDate(iso: string): string {
  return new Intl.DateTimeFormat('es-VE', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }).format(new Date(iso))
}

const hasMoreMovements = () =>
  movementsMeta.value !== null &&
  movementsMeta.value.current_page < movementsMeta.value.last_page
</script>

<template>
  <div class="min-h-screen bg-gray-50 py-6 px-4">
    <div class="mx-auto" style="max-width:720px">

      <!-- ── Encabezado ─────────────────────────────────────────── -->
      <div class="mb-6">
        <h4 class="text-xl font-bold text-gray-900 mb-0.5 flex items-center gap-2">
          <i class="fas fa-wallet text-indigo-600"></i>
          Mi Billetera
        </h4>
        <p class="text-sm text-gray-500">Gestiona tus depósitos, retiros y revisa tus movimientos</p>
      </div>

      <!-- ── Sin billeteras ─────────────────────────────────────── -->
      <div v-if="!wallets.length">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 py-16 text-center">
          <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-wallet text-2xl text-gray-400"></i>
          </div>
          <h6 class="font-semibold text-gray-700 mb-1">Sin billeteras activas</h6>
          <p class="text-sm text-gray-400">Contacta al soporte para activar tu billetera.</p>
        </div>
      </div>

      <template v-else>

        <!-- ── Selector de billetera (si hay varias) ──────────── -->
        <div v-if="wallets.length > 1" class="flex flex-wrap gap-2 mb-5">
          <button
            v-for="wallet in wallets"
            :key="wallet.id"
            type="button"
            class="px-4 py-1.5 rounded-full text-sm font-medium border transition-all"
            :class="activeWallet?.id === wallet.id
              ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm'
              : 'bg-white text-gray-600 border-gray-200 hover:border-indigo-400'"
            @click="selectWallet(wallet)"
          >
            {{ wallet.currency.symbol }} {{ wallet.currency.iso_code }}
          </button>
        </div>

        <!-- ── Tarjeta de saldo ───────────────────────────────── -->
        <div
          class="rounded-2xl p-6 mb-5 text-white shadow-lg relative overflow-hidden"
          style="background: linear-gradient(135deg, #312e81 0%, #1d4ed8 60%, #0ea5e9 100%)"
        >
          <!-- decoración de fondo -->
          <div class="absolute -top-8 -right-8 w-40 h-40 rounded-full opacity-10 bg-white"></div>
          <div class="absolute -bottom-12 -left-6 w-48 h-48 rounded-full opacity-10 bg-white"></div>

          <div class="relative z-10">
            <div class="flex items-start justify-between mb-4">
              <div>
                <p class="text-xs font-medium opacity-75 mb-1 uppercase tracking-wide">Saldo disponible</p>
                <p class="text-4xl font-bold tracking-tight">
                  {{ activeWallet?.currency.symbol }}&nbsp;{{ formattedBalance }}
                </p>
                <p class="text-sm opacity-70 mt-1">{{ activeWallet?.currency.name }}</p>
              </div>
              <div class="w-12 h-12 rounded-xl bg-white/20 flex items-center justify-center shadow-inner">
                <i class="fas fa-coins text-lg"></i>
              </div>
            </div>

            <!-- status badge -->
            <div class="flex items-center gap-2">
              <span
                class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full"
                :class="{
                  'bg-emerald-400/30 text-emerald-100': activeWallet?.status === 'ACTIVE',
                  'bg-yellow-400/30 text-yellow-100':   activeWallet?.status === 'INACTIVE',
                  'bg-red-400/30 text-red-100':         activeWallet?.status === 'BLOCKED',
                }"
              >
                <span
                  class="w-1.5 h-1.5 rounded-full"
                  :class="{
                    'bg-emerald-300': activeWallet?.status === 'ACTIVE',
                    'bg-yellow-300':  activeWallet?.status === 'INACTIVE',
                    'bg-red-300':     activeWallet?.status === 'BLOCKED',
                  }"
                ></span>
                {{ activeWallet?.status === 'ACTIVE' ? 'Activa' :
                   activeWallet?.status === 'INACTIVE' ? 'Inactiva' : 'Bloqueada' }}
              </span>
            </div>
          </div>
        </div>

        <!-- ── Acciones ───────────────────────────────────────── -->
        <div class="grid grid-cols-2 gap-3 mb-6">
          <button
            type="button"
            class="flex items-center justify-center gap-2 py-3 rounded-xl text-sm font-semibold bg-emerald-500 hover:bg-emerald-600 text-white transition-all shadow-sm active:scale-95"
            @click="openModal('deposit')"
          >
            <i class="fas fa-arrow-down"></i> Depositar
          </button>
          <button
            type="button"
            class="flex items-center justify-center gap-2 py-3 rounded-xl text-sm font-semibold transition-all shadow-sm active:scale-95"
            :class="canWithdraw
              ? 'bg-amber-500 hover:bg-amber-600 text-white'
              : 'bg-gray-100 text-gray-400 cursor-not-allowed'"
            :disabled="!canWithdraw"
            @click="openModal('withdraw')"
          >
            <i class="fas fa-arrow-up"></i> Retirar
          </button>
        </div>

        <!-- ── Leyenda de tipos de movimiento ─────────────────── -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-5">
          <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Tipos de movimiento</p>
          <div class="grid grid-cols-2 gap-2">
            <div
              v-for="(meta, key) in movementMeta"
              :key="key"
              class="flex items-center gap-2.5 px-3 py-2 rounded-xl"
              :class="meta.bgClass"
            >
              <div class="w-7 h-7 rounded-lg flex items-center justify-center bg-white/70">
                <i :class="[meta.icon, meta.colorClass, 'text-xs']"></i>
              </div>
              <div>
                <p class="text-xs font-semibold leading-tight" :class="meta.colorClass">{{ meta.label }}</p>
                <p class="text-xs opacity-70 leading-tight" :class="meta.colorClass">
                  {{ meta.sign === '+' ? 'Entrada' : 'Salida' }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- ── Historial de movimientos ───────────────────────── -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-50 flex items-center justify-between">
            <h6 class="font-semibold text-gray-800 mb-0 flex items-center gap-2">
              <i class="fas fa-history text-indigo-400 text-sm"></i>
              Movimientos recientes
            </h6>
            <span class="text-xs text-gray-400">sesión actual</span>
          </div>

          <!-- cargando -->
          <div v-if="movementsLoading && !movements.length" class="py-12 flex justify-center">
            <i class="fas fa-spinner fa-spin text-2xl text-gray-300"></i>
          </div>

          <!-- sin movimientos aún -->
          <div v-else-if="!movements.length" class="py-12 text-center">
            <div class="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3">
              <i class="fas fa-receipt text-gray-300 text-lg"></i>
            </div>
            <p class="text-sm text-gray-400">Aún no hay movimientos registrados.</p>
            <p class="text-xs text-gray-300 mt-1">Los depósitos, retiros, premios y compras aparecerán aquí.</p>
          </div>

          <!-- lista de movimientos -->
          <template v-else>
            <ul class="divide-y divide-gray-50">
              <li
                v-for="mov in movements"
                :key="mov.id"
                class="flex items-center gap-4 px-5 py-3.5 hover:bg-gray-50 transition-colors"
              >
                <!-- icono -->
                <div
                  class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                  :class="movementMeta[mov.type].bgClass"
                >
                  <i :class="[movementMeta[mov.type].icon, movementMeta[mov.type].colorClass, 'text-sm']"></i>
                </div>

                <!-- descripción -->
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-800 truncate">{{ movementMeta[mov.type].label }}</p>
                  <p class="text-xs text-gray-400">
                    {{ mov.description ?? movementMeta[mov.type].label }} · {{ formatDate(mov.created_at) }}
                  </p>
                </div>

                <!-- monto -->
                <div class="text-right flex-shrink-0">
                  <p
                    class="text-sm font-bold"
                    :class="movementMeta[mov.type].sign === '+' ? 'text-emerald-600' : 'text-rose-500'"
                  >
                    {{ movementMeta[mov.type].sign }}{{ activeWallet?.currency.symbol }}&nbsp;{{
                      new Intl.NumberFormat('es-VE', {
                        minimumFractionDigits: activeWallet?.currency.decimal_places ?? 2,
                        maximumFractionDigits: activeWallet?.currency.decimal_places ?? 2,
                      }).format(mov.amount)
                    }}
                  </p>
                  <p class="text-xs text-gray-400">
                    Saldo: {{ activeWallet?.currency.symbol }}&nbsp;{{
                      new Intl.NumberFormat('es-VE', {
                        minimumFractionDigits: activeWallet?.currency.decimal_places ?? 2,
                        maximumFractionDigits: activeWallet?.currency.decimal_places ?? 2,
                      }).format(mov.balance_after)
                    }}
                  </p>
                </div>
              </li>
            </ul>

            <!-- cargar más -->
            <div v-if="hasMoreMovements() || movementsLoading" class="px-5 py-4 border-t border-gray-50 text-center">
              <button
                type="button"
                class="text-sm text-indigo-600 hover:text-indigo-800 font-medium disabled:opacity-50"
                :disabled="movementsLoading"
                @click="loadMoreMovements"
              >
                <span v-if="movementsLoading"><i class="fas fa-spinner fa-spin mr-1"></i>Cargando...</span>
                <span v-else>Ver más movimientos</span>
              </button>
            </div>
          </template>
        </div>

      </template>
    </div>

    <!-- ══════════════════════════════════════════════════════════
         MODAL DEPÓSITO / RETIRO
         ══════════════════════════════════════════════════════════ -->
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
          <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeModal" />

          <Transition
            enter-active-class="duration-200 ease-out"
            enter-from-class="opacity-0 scale-95 translate-y-2"
            enter-to-class="opacity-100 scale-100 translate-y-0"
          >
            <div
              v-if="modalOpen"
              class="relative bg-white rounded-2xl shadow-2xl w-full overflow-hidden"
              style="max-width: 420px"
              @click.stop
            >
              <!-- Header -->
              <div
                class="flex items-center justify-between px-5 py-4"
                :class="operation === 'deposit'
                  ? 'bg-gradient-to-r from-emerald-50 to-green-50 border-b border-emerald-100'
                  : 'bg-gradient-to-r from-amber-50 to-yellow-50 border-b border-amber-100'"
              >
                <div class="flex items-center gap-3">
                  <div
                    class="w-9 h-9 rounded-xl flex items-center justify-center shadow-sm"
                    :class="operation === 'deposit' ? 'bg-emerald-500' : 'bg-amber-500'"
                  >
                    <i
                      class="text-white text-sm"
                      :class="operation === 'deposit' ? 'fas fa-arrow-down' : 'fas fa-arrow-up'"
                    />
                  </div>
                  <div>
                    <h6 class="font-bold text-gray-900 mb-0 text-sm">
                      {{ operation === 'deposit' ? 'Depositar fondos' : 'Retirar fondos' }}
                    </h6>
                    <p class="text-xs text-gray-500 mb-0">Billetera {{ activeWallet?.currency.iso_code }}</p>
                  </div>
                </div>
                <button
                  type="button"
                  class="w-8 h-8 flex items-center justify-center hover:bg-white/70 rounded-lg transition-colors text-gray-400 hover:text-gray-600"
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
                  <span class="font-semibold text-gray-900 text-sm">
                    {{ activeWallet?.currency.symbol }} {{ formattedBalance }}
                  </span>
                </div>

                <!-- Monto -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1.5">
                    Monto a {{ operation === 'deposit' ? 'depositar' : 'retirar' }}
                  </label>
                  <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-semibold text-sm select-none">
                      {{ activeWallet?.currency.symbol }}
                    </span>
                    <input
                      v-model="amount"
                      type="number"
                      min="0.01"
                      step="0.01"
                      placeholder="0.00"
                      class="w-full border border-gray-200 rounded-xl pl-9 pr-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:border-transparent transition"
                      :class="operation === 'deposit' ? 'focus:ring-emerald-300' : 'focus:ring-amber-300'"
                      :disabled="loading"
                      @keyup.enter="submit"
                    />
                  </div>
                </div>

                <!-- Mensajes -->
                <Transition
                  enter-active-class="duration-200 ease-out"
                  enter-from-class="opacity-0 -translate-y-1"
                  enter-to-class="opacity-100 translate-y-0"
                >
                  <div
                    v-if="errorMsg"
                    class="flex items-start gap-2 bg-red-50 border border-red-200 rounded-xl px-3 py-2.5 text-sm text-red-700"
                  >
                    <i class="fas fa-exclamation-circle mt-0.5 flex-shrink-0" />
                    <span>{{ errorMsg }}</span>
                  </div>
                </Transition>

                <Transition
                  enter-active-class="duration-200 ease-out"
                  enter-from-class="opacity-0 -translate-y-1"
                  enter-to-class="opacity-100 translate-y-0"
                >
                  <div
                    v-if="successMsg"
                    class="flex items-start gap-2 bg-emerald-50 border border-emerald-200 rounded-xl px-3 py-2.5 text-sm text-emerald-700"
                  >
                    <i class="fas fa-check-circle mt-0.5 flex-shrink-0" />
                    <span>{{ successMsg }}</span>
                  </div>
                </Transition>

              </div>

              <!-- Footer -->
              <div class="px-5 pb-5 flex gap-3">
                <button
                  type="button"
                  class="flex-1 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors"
                  :disabled="loading"
                  @click="closeModal"
                >
                  Cancelar
                </button>
                <button
                  type="button"
                  class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-white transition-all disabled:opacity-50 active:scale-95"
                  :class="operation === 'deposit' ? 'bg-emerald-500 hover:bg-emerald-600' : 'bg-amber-500 hover:bg-amber-600'"
                  :disabled="loading"
                  @click="submit"
                >
                  <span v-if="loading" class="flex items-center justify-center gap-1.5">
                    <i class="fas fa-spinner fa-spin" /> Procesando...
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
