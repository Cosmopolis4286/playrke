<script setup lang="ts">
  import { ref, computed, watch } from 'vue'
  import Swal from 'sweetalert2'

  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'

  import { useAdminKyc } from '@/js/composables/roles/admin/kyc/useAdminKyc'

  import type { KycPageProps } from '@/js/types/roles/admin/kyc'
  import type { TableFilterInterface } from '@/js/types/common/table/table-filter'
  import type { ServerQuery } from '@/js/composables/common/table/useTableController'

  /* -------------------------------------------------------------------------- */
  /* Props                                                                       */
  /* -------------------------------------------------------------------------- */

  const props = defineProps<KycPageProps>()

  /* -------------------------------------------------------------------------- */
  /* Composable                                                                  */
  /* -------------------------------------------------------------------------- */

  const {
    loading, documents, detail, detailLoading, approving, rejecting,
    setInitialDocuments, onQueryChange, fetchDetail, clearDetail, approve, reject,
  } = useAdminKyc()

  setInitialDocuments(props.documents)

  /* -------------------------------------------------------------------------- */
  /* Tabla                                                                       */
  /* -------------------------------------------------------------------------- */

  const rows = computed(() => documents.value.rows)

  const filters = computed<readonly TableFilterInterface[]>(() => [
    {
      key:   'status',
      label: 'Estado',
      options: [
        { label: 'Pendientes',  value: 'pending' },
        { label: 'Aprobados',   value: 'approved' },
        { label: 'Rechazados',  value: 'rejected' },
        { label: 'Todos',       value: 'all' },
      ],
    },
  ])

  async function handleQueryChange(query: ServerQuery): Promise<void> {
    await onQueryChange(query)
  }

  /* -------------------------------------------------------------------------- */
  /* Detalle / revisión                                                          */
  /* -------------------------------------------------------------------------- */

  const showDetail = ref(false)

  async function openDetail(id: string): Promise<void> {
    const ok = await fetchDetail(id)
    if (ok) showDetail.value = true
  }

  watch(showDetail, isOpen => {
    if (!isOpen) clearDetail()
  })

  async function handleApprove(): Promise<void> {
    if (!detail.value) return
    const confirmed = await Swal.fire({
      title: '¿Aprobar documento?',
      html: `Se marcará a <b>${detail.value.user.full_name}</b> como verificado.`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, aprobar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#16a34a',
      reverseButtons: true,
    })
    if (!confirmed.isConfirmed) return

    const ok = await approve(detail.value.id)
    if (ok) showDetail.value = false
  }

  async function handleReject(): Promise<void> {
    if (!detail.value) return
    const result = await Swal.fire({
      title: 'Rechazar documento',
      input: 'textarea',
      inputLabel: 'Motivo del rechazo',
      inputPlaceholder: 'Explica por qué se rechaza este documento...',
      inputValidator: (value) => (!value || !value.trim() ? 'El motivo es obligatorio' : undefined),
      showCancelButton: true,
      confirmButtonText: 'Rechazar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#dc2626',
      reverseButtons: true,
    })
    if (!result.isConfirmed || !result.value) return

    const ok = await reject(detail.value.id, result.value.trim())
    if (ok) showDetail.value = false
  }

  const statusBadgeClass: Record<string, string> = {
    pending:  'badge bg-warning text-dark',
    approved: 'badge bg-success',
    rejected: 'badge bg-danger',
  }

  const statusLabel: Record<string, string> = {
    pending:  'Pendiente',
    approved: 'Aprobado',
    rejected: 'Rechazado',
  }

  function imageUrl(documentId: string, type: 'front' | 'back' | 'selfie'): string {
    return `/admin/kyc/${documentId}/image/${type}`
  }
</script>

<template>
  <TableWithFilterBridge
    table-id="kyc-table"
    title="Verificación de identidad (KYC)"
    subtitle="Cola de documentos enviados por jugadores"
    :columns="documents.columns"
    :rows="rows"
    :filters="filters"
    empty-message="No hay documentos en esta categoría"
    :loading="loading"
    server-side
    :server-total-pages="documents.last_page ?? 1"
    :show-create-button="false"
    :show-edit-button="false"
    :show-delete-button="false"
    @query-change="handleQueryChange"
  >
    <!-- Columna: Jugador -->
    <template #cell-player_name="{ row }">
      <div>
        <div class="fw-semibold">{{ row.player_name }}</div>
        <div class="text-muted small">@{{ row.player_username }}</div>
      </div>
    </template>

    <!-- Columna: Estado -->
    <template #cell-status="{ value }">
      <span :class="statusBadgeClass[value] ?? 'badge bg-secondary'">{{ statusLabel[value] ?? value }}</span>
    </template>

    <!-- Columna: Acciones -->
    <template #cell-submitted_at="{ row, value }">
      <div class="d-flex align-items-center justify-content-between gap-2">
        <span>{{ value ?? '—' }}</span>
        <button class="btn btn-xs btn-outline-info" title="Revisar documento" @click="openDetail(row.id)">
          <i class="fas fa-eye"></i>
        </button>
      </div>
    </template>
  </TableWithFilterBridge>

  <!-- Modal: Revisión de documento -->
  <Modal v-model="showDetail" title="Revisión de documento KYC" icon="fa-id-card-clip" size="lg">
    <template v-if="detailLoading">
      <div class="text-center py-4">
        <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
      </div>
    </template>
    <template v-else-if="detail">
      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <small class="text-muted d-block">Jugador</small>
          <span>{{ detail.user.full_name }} (@{{ detail.user.username }})</span>
        </div>
        <div class="col-md-6">
          <small class="text-muted d-block">Email</small>
          <span>{{ detail.user.email }}</span>
        </div>
        <div class="col-md-6">
          <small class="text-muted d-block">Tipo de documento</small>
          <span>{{ detail.document_type }}</span>
        </div>
        <div class="col-md-6">
          <small class="text-muted d-block">Número de documento</small>
          <span>{{ detail.document_number }}</span>
        </div>
        <div class="col-md-6">
          <small class="text-muted d-block">Estado actual</small>
          <span :class="statusBadgeClass[detail.status] ?? 'badge bg-secondary'">{{ statusLabel[detail.status] ?? detail.status }}</span>
        </div>
        <div class="col-md-6">
          <small class="text-muted d-block">Enviado</small>
          <span>{{ detail.created_at ?? '—' }}</span>
        </div>
        <div v-if="detail.rejection_reason" class="col-12">
          <small class="text-muted d-block">Motivo de rechazo previo</small>
          <span class="text-danger">{{ detail.rejection_reason }}</span>
        </div>
      </div>

      <div class="row g-3">
        <div v-if="detail.has_front" class="col-md-4">
          <small class="text-muted d-block mb-1">Frente</small>
          <img :src="imageUrl(detail.id, 'front')" class="kyc-doc-image" alt="Frente del documento" />
        </div>
        <div v-if="detail.has_back" class="col-md-4">
          <small class="text-muted d-block mb-1">Reverso</small>
          <img :src="imageUrl(detail.id, 'back')" class="kyc-doc-image" alt="Reverso del documento" />
        </div>
        <div v-if="detail.has_selfie" class="col-md-4">
          <small class="text-muted d-block mb-1">Selfie</small>
          <img :src="imageUrl(detail.id, 'selfie')" class="kyc-doc-image" alt="Selfie de verificación" />
        </div>
      </div>
    </template>

    <template v-if="detail?.status === 'pending'" #footer>
      <button type="button" class="btn btn-outline-secondary" @click="showDetail = false">Cerrar</button>
      <button type="button" class="btn btn-danger" :disabled="rejecting || approving" @click="handleReject">
        <span v-if="rejecting" class="spinner-border spinner-border-sm me-1"></span>
        <i v-else class="fas fa-times me-1"></i>Rechazar
      </button>
      <button type="button" class="btn btn-success" :disabled="approving || rejecting" @click="handleApprove">
        <span v-if="approving" class="spinner-border spinner-border-sm me-1"></span>
        <i v-else class="fas fa-check me-1"></i>Aprobar
      </button>
    </template>
  </Modal>
</template>

<style scoped>
.kyc-doc-image {
  width: 100%;
  border-radius: 8px;
  border: 1px solid var(--ds-border);
  object-fit: cover;
  max-height: 220px;
}
</style>
