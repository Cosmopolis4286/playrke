<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import backendClient from '@/js/plugins/axios'
import { showToast } from '@/js/utils/toastNotify'
import BaseTable from '@/js/Components/Common/Table/BaseTable.vue'
import PageHeader from '@/js/Components/Common/PageHeader/PageHeader.vue'
import type { Column } from '@/js/types/common/table/column-row'

interface WinEventRow {
  id: string
  winner_name: string
  game_name: string
  game_slug: string
  prize_kind: 'money' | 'item' | 'service'
  prize_label: string
  amount_usd: number | null
  source_type: string
  reported_by: string | null
  created_at: string
}

const columns: Column[] = [
  { key: 'winner_name', label: 'Ganador' },
  { key: 'game_name', label: 'Origen' },
  { key: 'prize', label: 'Premio' },
  { key: 'source_label', label: 'Fuente' },
  { key: 'created_label', label: 'Fecha' },
]

const PRIZE_KIND_LABELS: Record<string, string> = {
  money: 'Dinero',
  item: 'Objeto',
  service: 'Servicio',
}

const SOURCE_TYPE_LABELS: Record<string, string> = {
  game_engine: 'Motor de juego',
  admin_manual: 'Registro manual',
  external_api: 'API externa',
}

// ── Formulario de registro manual ──
const form = reactive({
  user_id: '',
  winner_name: '',
  game_slug: '',
  game_name: '',
  prize_kind: 'money' as 'money' | 'item' | 'service',
  prize_label: '',
  amount_usd: '',
  amount_local: '',
  currency_id: '',
  notes: '',
})

const submitting = ref(false)
const isMoney = computed(() => form.prize_kind === 'money')

async function submitForm(): Promise<void> {
  if (submitting.value) return

  try {
    submitting.value = true

    await backendClient.post('/admin/win-event', {
      user_id: form.user_id || null,
      winner_name: form.winner_name || null,
      game_slug: form.game_slug,
      game_name: form.game_name,
      prize_kind: form.prize_kind,
      prize_label: form.prize_label || null,
      amount_usd: isMoney.value ? Number(form.amount_usd) : null,
      amount_local: isMoney.value && form.amount_local ? Number(form.amount_local) : null,
      currency_id: form.currency_id || null,
      notes: form.notes || null,
    })

    showToast('Evento de ganancia registrado', 'success')
    resetForm()
    await fetchList()
  } catch (err: any) {
    const message = err?.response?.data?.message ?? 'Error al registrar el evento'
    showToast(message, 'error')
  } finally {
    submitting.value = false
  }
}

function resetForm(): void {
  form.user_id = ''
  form.winner_name = ''
  form.game_slug = ''
  form.game_name = ''
  form.prize_kind = 'money'
  form.prize_label = ''
  form.amount_usd = ''
  form.amount_local = ''
  form.currency_id = ''
  form.notes = ''
}

// ── Listado ──
const rows = ref<WinEventRow[]>([])
const loading = ref(false)

/** BaseTable espera Row[] (con `id`) — se reusa el WinEventRow tal cual, ya lo trae. */
const tableRows = computed(() => rows.value)

async function fetchList(): Promise<void> {
  try {
    loading.value = true
    const { data } = await backendClient.get('/admin/win-event/fetch-list')
    rows.value = data.data?.data ?? data.data ?? []
  } catch {
    showToast('Error al cargar el listado', 'error')
  } finally {
    loading.value = false
  }
}

onMounted(fetchList)
</script>

<template>
  <PageHeader
    icon="fa-gift"
    title="Eventos de Ganancia"
    subtitle="Registra premios físicos, objetos o servicios entregados fuera de la plataforma."
    :breadcrumb-items="[
      { label: 'Dashboard', route: '/dashboard', icon: 'fas fa-home' },
      { label: 'Eventos de Ganancia' },
    ]"
  />

  <div class="container-fluid py-4">
    <div class="row g-4">
      <!-- ── Formulario de registro manual ── -->
      <div class="col-12 col-lg-5">
        <div class="card">
          <div class="card-header bg-transparent border-bottom">
            <h6 class="mb-0 fw-semibold">
              <i class="fas fa-gift text-muted me-2"></i>Registrar Ganancia Manual
            </h6>
            <small class="text-muted">Premios físicos, objetos o servicios entregados fuera de la plataforma</small>
          </div>
          <div class="card-body">
            <form @submit.prevent="submitForm">
              <div class="mb-3">
                <label class="form-label small">ID de usuario (opcional)</label>
                <input v-model="form.user_id" type="text" class="form-control" placeholder="Si el ganador tiene cuenta en la plataforma" />
              </div>

              <div class="mb-3">
                <label class="form-label small">Nombre del ganador</label>
                <input v-model="form.winner_name" type="text" class="form-control" placeholder="Requerido si no indicaste un ID de usuario" />
              </div>

              <div class="row g-2 mb-3">
                <div class="col-6">
                  <label class="form-label small">Identificador del origen</label>
                  <input v-model="form.game_slug" type="text" class="form-control" placeholder="ej. physical-raffle" required />
                </div>
                <div class="col-6">
                  <label class="form-label small">Nombre del origen</label>
                  <input v-model="form.game_name" type="text" class="form-control" placeholder="ej. Rifa Presencial" required />
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label small">Tipo de premio</label>
                <select v-model="form.prize_kind" class="form-select">
                  <option value="money">Dinero</option>
                  <option value="item">Objeto</option>
                  <option value="service">Servicio</option>
                </select>
              </div>

              <template v-if="isMoney">
                <div class="row g-2 mb-3">
                  <div class="col-6">
                    <label class="form-label small">Monto USD</label>
                    <input v-model="form.amount_usd" type="number" min="0" step="0.01" class="form-control" required />
                  </div>
                  <div class="col-6">
                    <label class="form-label small">Monto local (opcional)</label>
                    <input v-model="form.amount_local" type="number" min="0" step="0.0001" class="form-control" />
                  </div>
                </div>
                <div class="mb-3">
                  <label class="form-label small">ID de moneda (opcional)</label>
                  <input v-model="form.currency_id" type="text" class="form-control" placeholder="Si el monto local es en otra moneda" />
                </div>
              </template>

              <template v-else>
                <div class="mb-3">
                  <label class="form-label small">Descripción del premio</label>
                  <input v-model="form.prize_label" type="text" class="form-control" placeholder="ej. PlayStation 5, Cena para 2" required />
                </div>
              </template>

              <div class="mb-3">
                <label class="form-label small">Notas (opcional)</label>
                <textarea v-model="form.notes" class="form-control" rows="2"></textarea>
              </div>

              <button type="submit" class="btn btn-primary w-100" :disabled="submitting">
                <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                {{ submitting ? 'Registrando...' : 'Registrar Ganancia' }}
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- ── Listado ── -->
      <div class="col-12 col-lg-7">
        <div class="card">
          <div class="card-header bg-transparent border-bottom d-flex align-items-center justify-content-between">
            <h6 class="mb-0 fw-semibold">
              <i class="fas fa-history text-muted me-2"></i>Eventos Recientes
            </h6>
            <button class="btn btn-sm btn-outline-secondary" @click="fetchList" :disabled="loading">
              <i class="fas fa-sync-alt" :class="{ 'fa-spin': loading }"></i>
            </button>
          </div>
          <div class="card-body p-0">
            <BaseTable
              table-id="win-event-table"
              :columns="columns"
              :rows="tableRows"
              empty-message="Sin eventos registrados"
            >
              <template #cell-winner_name="{ row }">
                <Link :href="`/admin/win-event/${row.id}/show`" class="fw-semibold text-decoration-none">
                  {{ row.winner_name }}
                </Link>
              </template>

              <template #cell-prize="{ row }">
                <span class="badge bg-light text-dark border me-1">{{ PRIZE_KIND_LABELS[row.prize_kind] }}</span>
                <span v-if="row.amount_usd !== null">${{ row.amount_usd.toFixed(2) }}</span>
                <span v-else>{{ row.prize_label }}</span>
              </template>

              <template #cell-source_label="{ row }">
                <small class="text-muted">{{ SOURCE_TYPE_LABELS[row.source_type] ?? row.source_type }}</small>
              </template>

              <template #cell-created_label="{ row }">
                <small class="text-muted">{{ new Date(row.created_at).toLocaleString() }}</small>
              </template>
            </BaseTable>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
