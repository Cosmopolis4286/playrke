<script setup lang="ts">
/**
   * D1: cuentas de la plataforma donde los jugadores depositan, por moneda.
   * El jugador las ve en el paso de depósito y elige a cuál pagó; el depósito
   * guarda esa cuenta para que quien lo revisa sepa dónde verificar.
   */
import { computed, reactive, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import Swal from 'sweetalert2'

import PageHeader from '@/js/Components/Common/PageHeader/PageHeader.vue'
import Modal from '@/js/Components/Common/Modal/Modal.vue'
import backendClient from '@/js/plugins/axios'
import { showToast } from '@/js/utils/toastNotify'

interface AccountRow {
  id: string
  currency_id: string
  currency_code: string | null
  method: string
  method_label: string
  label: string
  bank_name: string | null
  holder_name: string
  holder_document: string | null
  account_identifier: string
  instructions: string | null
  status: 'ACTIVE' | 'INACTIVE'
  sort_order: number
}

const props = defineProps<{
  accounts: AccountRow[]
  currencies: { id: string, iso_code: string, name: string }[]
  methods: { value: string, label: string }[]
}>()

const showForm = ref(false)
const editingId = ref<string | null>(null)
const errors = ref<Record<string, string>>({})

const emptyForm = () => ({
  currency_id: props.currencies[0]?.id ?? '',
  method: 'pago_movil',
  label: '',
  bank_name: '',
  holder_name: '',
  holder_document: '',
  account_identifier: '',
  instructions: '',
  status: 'ACTIVE' as 'ACTIVE' | 'INACTIVE',
  sort_order: 0
})
const form = reactive(emptyForm())

const isValid = computed(() => !!form.currency_id && !!form.label.trim() && !!form.holder_name.trim() && !!form.account_identifier.trim())

function openCreate() {
  Object.assign(form, emptyForm())
  editingId.value = null
  errors.value = {}
  showForm.value = true
}

function openEdit(row: AccountRow) {
  Object.assign(form, {
    currency_id: row.currency_id,
    method: row.method,
    label: row.label,
    bank_name: row.bank_name ?? '',
    holder_name: row.holder_name,
    holder_document: row.holder_document ?? '',
    account_identifier: row.account_identifier,
    instructions: row.instructions ?? '',
    status: row.status,
    sort_order: row.sort_order
  })
  editingId.value = row.id
  errors.value = {}
  showForm.value = true
}

async function save(): Promise<boolean> {
  errors.value = {}
  try {
    const payload = { ...form, bank_name: form.bank_name || null, holder_document: form.holder_document || null, instructions: form.instructions || null }
    const { data } = editingId.value
      ? await backendClient.put(`/deposit-accounts/${editingId.value}`, payload)
      : await backendClient.post('/deposit-accounts', payload)
    showToast(data.message ?? 'Cuenta guardada.', 'success')
    router.reload({ only: ['accounts'] })

    return true
  } catch (e) {
    const res = (e as { response?: { data?: { message?: string, errors?: Record<string, string[]> } } })?.response?.data
    errors.value = Object.fromEntries(Object.entries(res?.errors ?? {}).map(([k, v]) => [k, v[0]]))
    showToast(res?.message ?? 'No se pudo guardar la cuenta.', 'error')

    return false
  }
}

async function remove(row: AccountRow) {
  const { isConfirmed } = await Swal.fire({
    title: 'Eliminar cuenta',
    text: `Los jugadores dejarán de ver "${row.label}". Los depósitos ya hechos conservan los datos de la cuenta.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Eliminar',
    confirmButtonColor: '#dc2626',
    cancelButtonText: 'Cancelar',
    reverseButtons: true
  })
  if (!isConfirmed) return
  try {
    await backendClient.delete(`/deposit-accounts/${row.id}`)
    showToast('Cuenta eliminada.', 'success')
    router.reload({ only: ['accounts'] })
  } catch {
    showToast('No se pudo eliminar la cuenta.', 'error')
  }
}
</script>

<template>
  <PageHeader
    icon="fa-building-columns"
    title="Cuentas para Depósitos"
    subtitle="Cuentas de la plataforma donde los jugadores pagan sus depósitos, por moneda."
    :breadcrumb-items="[
      { label: 'Dashboard', route: '/dashboard', icon: 'fas fa-home' },
      { label: 'Cuentas para Depósitos' },
    ]"
  />

  <div class="card">
    <div class="card-header d-flex align-items-center">
      <h3 class="card-title mb-0">
        Cuentas
      </h3>
      <button type="button" class="btn btn-sm btn-primary ml-auto" @click="openCreate">
        <i class="fas fa-plus mr-1" />Nueva cuenta
      </button>
    </div>
    <div class="card-body p-0 table-responsive">
      <table class="table table-sm table-hover mb-0">
        <thead>
          <tr>
            <th>Moneda</th>
            <th>Cuenta</th>
            <th>Titular</th>
            <th>Datos de pago</th>
            <th>Estado</th>
            <th class="text-right">
              Acciones
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!accounts.length">
            <td colspan="6" class="text-center text-muted py-4">
              Sin cuentas: los jugadores no ven dónde depositar. Crea al menos una por moneda.
            </td>
          </tr>
          <tr v-for="row in accounts" :key="row.id">
            <td>{{ row.currency_code ?? '—' }}</td>
            <td>
              <strong>{{ row.label }}</strong>
              <small class="d-block text-muted">{{ row.method_label }}<span v-if="row.bank_name"> · {{ row.bank_name }}</span></small>
            </td>
            <td>{{ row.holder_name }}<small v-if="row.holder_document" class="d-block text-muted">{{ row.holder_document }}</small></td>
            <td class="text-break">
              {{ row.account_identifier }}
            </td>
            <td>
              <span class="badge" :class="row.status === 'ACTIVE' ? 'badge-success' : 'badge-secondary'">
                {{ row.status === 'ACTIVE' ? 'Activa' : 'Inactiva' }}
              </span>
            </td>
            <td class="text-right text-nowrap">
              <button type="button" class="btn btn-xs btn-outline-primary mr-1" @click="openEdit(row)">
                <i class="fas fa-pen" />
              </button>
              <button type="button" class="btn btn-xs btn-outline-danger" @click="remove(row)">
                <i class="fas fa-trash" />
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <Modal
    v-model="showForm"
    :title="editingId ? 'Editar cuenta' : 'Nueva cuenta'"
    icon="fa-building-columns"
    static
    show-confirm
    confirm-text="Guardar"
    :confirm-disabled="!isValid"
    :on-submit="save"
  >
    <div class="form-row">
      <div class="form-group col-md-6">
        <label>Moneda *</label>
        <select v-model="form.currency_id" class="form-control">
          <option v-for="c in currencies" :key="c.id" :value="c.id">
            {{ c.iso_code }} — {{ c.name }}
          </option>
        </select>
        <small v-if="errors.currency_id" class="text-danger">{{ errors.currency_id }}</small>
      </div>
      <div class="form-group col-md-6">
        <label>Método *</label>
        <select v-model="form.method" class="form-control">
          <option v-for="m in methods" :key="m.value" :value="m.value">
            {{ m.label }}
          </option>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label>Nombre visible *</label>
      <input
        v-model="form.label"
        type="text"
        maxlength="80"
        class="form-control"
        placeholder="Ej.: Banesco — Pago móvil"
      >
      <small v-if="errors.label" class="text-danger">{{ errors.label }}</small>
    </div>
    <div class="form-row">
      <div class="form-group col-md-6">
        <label>Banco</label>
        <input
          v-model="form.bank_name"
          type="text"
          maxlength="80"
          class="form-control"
        >
      </div>
      <div class="form-group col-md-6">
        <label>Cédula / RIF del titular</label>
        <input
          v-model="form.holder_document"
          type="text"
          maxlength="40"
          class="form-control"
        >
      </div>
    </div>
    <div class="form-group">
      <label>Titular *</label>
      <input
        v-model="form.holder_name"
        type="text"
        maxlength="120"
        class="form-control"
      >
      <small v-if="errors.holder_name" class="text-danger">{{ errors.holder_name }}</small>
    </div>
    <div class="form-group">
      <label>Número de cuenta, teléfono, correo o ID de pago *</label>
      <input
        v-model="form.account_identifier"
        type="text"
        maxlength="120"
        class="form-control"
      >
      <small v-if="errors.account_identifier" class="text-danger">{{ errors.account_identifier }}</small>
    </div>
    <div class="form-group">
      <label>Instrucciones para el jugador</label>
      <textarea
        v-model="form.instructions"
        rows="2"
        maxlength="500"
        class="form-control"
        placeholder="Ej.: indica tu usuario en el concepto"
      />
    </div>
    <div class="form-row">
      <div class="form-group col-md-6">
        <label>Estado</label>
        <select v-model="form.status" class="form-control">
          <option value="ACTIVE">
            Activa
          </option>
          <option value="INACTIVE">
            Inactiva
          </option>
        </select>
      </div>
      <div class="form-group col-md-6">
        <label>Orden</label>
        <input
          v-model.number="form.sort_order"
          type="number"
          min="0"
          max="999"
          class="form-control"
        >
      </div>
    </div>
  </Modal>
</template>
