<script setup lang="ts">
import { ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast } from '@/js/utils/toastNotify'

interface Props {
  show: boolean
  userName: string
  userId: string | number
  userLevel?: number
  userXp?: number
}

interface Emits {
  close: []
  success: []
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const amount = ref('')
const reason = ref('')
const quickAmounts = [25, 50, 100, 250, 500, 1000]

function closeModal() {
  emit('close')
  amount.value = ''
  reason.value = ''
}

function selectQuickAmount(value: number) {
  amount.value = value.toString()
}

async function grantXp() {
  const parsed = parseInt(amount.value, 10)
  if (!amount.value || parsed <= 0) {
    showToast('Debe ingresar una cantidad de XP válida mayor a 0', 'error')
    return
  }

  try {
    const { data } = await backendClient.put(`/admin/user/${props.userId}/grant-xp`, {
      amount: parsed,
      reason: reason.value || undefined,
    })

    showToast(data.message ?? `XP otorgado: ${amount.value}`, 'success')

    amount.value = ''
    reason.value = ''

    emit('success')
  } catch (error) {
    console.error(error)
    showToast('Error al otorgar XP', 'error')
  }
}
</script>

<template>
  <div v-if="show" class="modal fade show" tabindex="-1" style="display: block; background-color: rgba(0,0,0,.5);">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header xgm-header">
          <h5 class="modal-title mb-0">
            <i class="fas fa-star mr-2"></i>
            Otorgar XP
          </h5>
          <button type="button" class="btn-close text-white" @click="closeModal">
            <span>&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="bg-gray-50 rounded-lg p-4 mb-4">
            <div class="flex items-center justify-between mb-4">
              <div class="flex-1">
                <label class="form-label text-gray-700 mb-2">Usuario:</label>
                <input
                  type="text"
                  class="form-control bg-white"
                  :value="userName"
                  readonly
                />
              </div>
            </div>

            <div v-if="userLevel !== undefined" class="bg-white rounded-lg p-3 border border-gray-200">
              <div class="flex items-center justify-between">
                <div>
                  <label class="form-label text-gray-700 mb-1">Nivel actual:</label>
                  <div class="flex items-center gap-2">
                    <span class="text-2xl font-bold text-gray-900">{{ userLevel }}</span>
                    <span v-if="userXp !== undefined" class="text-sm text-gray-600">({{ userXp }} XP)</span>
                  </div>
                </div>
                <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                  <i class="fas fa-star text-indigo-600"></i>
                </div>
              </div>
            </div>

            <div class="mt-4">
              <label for="xp-amount" class="form-label text-gray-700">Cantidad de XP:</label>
              <input
                id="xp-amount"
                type="number"
                class="form-control"
                v-model="amount"
                placeholder="0"
                step="1"
                min="1"
                autofocus
              />
            </div>

            <div class="mt-4">
              <label class="form-label text-gray-700 mb-3">Cantidades rápidas:</label>
              <div class="grid grid-cols-3 gap-2">
                <button
                  v-for="quickAmount in quickAmounts"
                  :key="quickAmount"
                  type="button"
                  class="px-3 py-2 text-sm font-medium border rounded-lg transition-all transform hover:scale-105"
                  :class="amount === quickAmount.toString()
                    ? 'bg-indigo-500 text-white border-indigo-500 shadow-md'
                    : 'bg-white text-gray-600 border-gray-200 hover:border-indigo-300 hover:bg-indigo-50'"
                  @click="selectQuickAmount(quickAmount)"
                >
                  {{ quickAmount }} XP
                </button>
              </div>
            </div>

            <div class="mt-4">
              <label for="xp-reason" class="form-label text-gray-700">Motivo (opcional):</label>
              <textarea
                id="xp-reason"
                class="form-control"
                v-model="reason"
                rows="2"
                maxlength="255"
                placeholder="Ej. compensación por incidente, premio de evento..."
              />
            </div>
          </div>
        </div>
        <div class="modal-footer bg-gray-50">
          <button type="button" class="btn btn-secondary mr-2" @click="closeModal">
            <i class="fas fa-times mr-2"></i>
            Cerrar
          </button>
          <button
            type="button"
            class="btn btn-success"
            @click="grantXp"
            :disabled="!amount || parseInt(amount, 10) <= 0"
          >
            <i class="fas fa-star mr-2"></i>
            Otorgar XP
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 1050;
}

.modal-dialog {
  position: relative;
  width: auto;
  max-width: 500px;
  margin: 1.75rem auto;
}

.modal-content {
  position: relative;
  background-color: var(--ds-surface);
  border: none;
  border-radius: 0.5rem;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
  overflow: hidden;
}

.modal-header {
  padding: 1rem 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.xgm-header {
  background: linear-gradient(135deg, #f7971e, #ffd200);
  color: #fff;
}

.modal-title {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 500;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: white;
  opacity: 0.8;
  transition: opacity 0.2s;
}

.btn-close:hover {
  opacity: 1;
}

.modal-body {
  padding: 1.5rem;
}

.modal-footer {
  padding: 1rem 1.5rem;
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
}

.form-label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: var(--ds-text-2);
}

.form-control {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid var(--ds-border-2);
  border-radius: 0.375rem;
  font-size: 1rem;
  transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
  background: var(--ds-surface);
  color: var(--ds-text);
  font-family: inherit;
  resize: vertical;
}

.form-control:focus {
  border-color: #80bdff;
  outline: 0;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 0.375rem;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s ease-in-out;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-secondary {
  background-color: #6c757d;
  color: white;
}

.btn-secondary:hover {
  background-color: #5a6268;
}

.btn-success {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 25%, #f093fb 50%, #f5576c 75%, #4facfe 100%);
  color: white;
}

.btn-success:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.bg-gray-50 {
  background-color: var(--ds-surface-2);
}

.rounded-lg {
  border-radius: 0.5rem;
}

.text-gray-700 {
  color: var(--ds-text-2);
}

.text-gray-600 {
  color: var(--ds-text-2);
}

.text-gray-900 {
  color: var(--ds-text);
}

.bg-indigo-500 {
  background-color: #6366f1;
}

.bg-white {
  background-color: var(--ds-surface);
}

.border-gray-200 {
  border-color: var(--ds-border);
}

.hover\:border-indigo-300:hover {
  border-color: #818cf8;
}

.hover\:bg-indigo-50:hover {
  background-color: rgba(99,102,241,.15);
}

.shadow-md {
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.grid-cols-3 {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.5rem;
}

.w-10 { width: 2.5rem; }
.h-10 { height: 2.5rem; }
.flex { display: flex; }
.items-center { align-items: center; }
.justify-center { justify-content: center; }

.bg-indigo-100 { background-color: rgba(99,102,241,.16); }
.text-indigo-600 { color: #818cf8; }
</style>
