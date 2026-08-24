<script setup lang="ts">
  import { ref, computed, watch } from 'vue'
  import Swal from 'sweetalert2'

  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'
  import PageHeader from '@/js/Components/Common/PageHeader/PageHeader.vue'
  import StatTile from '@/js/Components/Common/StatTile/StatTile.vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'

  import { useAdminKyc } from '@/js/composables/roles/admin/kyc/useAdminKyc'

  import type { KycPageProps } from '@/js/types/roles/admin/kyc'
  import type { TableFilterInterface } from '@/js/types/common/table/table-filter'
  import type { ServerQuery } from '@/js/composables/common/table/useTableController'
  import type { ExtraButton } from '@/js/types/common/table/extra-buttons'

  /* -------------------------------------------------------------------------- */
  /* Props                                                                       */
  /* -------------------------------------------------------------------------- */

  const props = defineProps<KycPageProps>()

  /* -------------------------------------------------------------------------- */
  /* Composable                                                                  */
  /* -------------------------------------------------------------------------- */

  const {
    loading, documents, stats, detail, detailLoading, approving, rejecting,
    setInitialDocuments, setInitialStats, lastQuery, onQueryChange, fetchDetail, clearDetail, approve, reject,
  } = useAdminKyc()

  setInitialDocuments(props.documents)
  setInitialStats(props.stats)

  /* -------------------------------------------------------------------------- */
  /* Tabla                                                                       */
  /* -------------------------------------------------------------------------- */

  const rows = computed(() => documents.value.rows)

  const DOCUMENT_TYPE_LABELS: Record<string, string> = {
    dni:               'Cédula',
    passport:          'Pasaporte',
    driving_license:   'Licencia de conducir',
    residence_permit:  'Permiso de residencia',
  }

  function documentTypeLabel(type: string): string {
    return DOCUMENT_TYPE_LABELS[type] ?? type
  }

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
    {
      key:   'document_type',
      label: 'Tipo de documento',
      options: Object.entries(DOCUMENT_TYPE_LABELS).map(([value, label]) => ({ label, value })),
    },
  ])

  async function handleQueryChange(query: ServerQuery): Promise<void> {
    await onQueryChange(query)
  }

  /* -------------------------------------------------------------------------- */
  /* Export CSV                                                                  */
  /* -------------------------------------------------------------------------- */

  function handleExport(): void {
    const params = new URLSearchParams()
    if (lastQuery.value.search) params.set('search', lastQuery.value.search)
    if (lastQuery.value.filters.status) params.set('status', lastQuery.value.filters.status)
    if (lastQuery.value.filters.document_type) params.set('document_type', lastQuery.value.filters.document_type)
    window.location.href = `/admin/kyc/export?${params.toString()}`
  }

  const extraButtons: ExtraButton[] = [
    {
      icon: 'fas fa-file-csv',
      class: 'btn-outline-secondary',
      title: 'Exportar documentos filtrados a CSV',
      onClick: handleExport,
    },
  ]

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

  const kycLevelLabel: Record<number, string> = {
    0: 'Sin verificar',
    1: 'Básico',
    2: 'Completo',
  }

  function imageUrl(documentId: string, type: 'front' | 'back' | 'selfie'): string {
    return `/admin/kyc/${documentId}/image/${type}`
  }

  /* -------------------------------------------------------------------------- */
  /* Lightbox de imágenes (zoom a pantalla completa)                             */
  /* -------------------------------------------------------------------------- */

  const lightboxUrl = ref<string | null>(null)
  const lightboxLabel = ref<string>('')

  function openLightbox(documentId: string, type: 'front' | 'back' | 'selfie', label: string): void {
    lightboxUrl.value = imageUrl(documentId, type)
    lightboxLabel.value = label
  }

  function closeLightbox(): void {
    lightboxUrl.value = null
  }
</script>

<template>
  <PageHeader
    icon="fa-id-card-clip"
    title="Verificación de identidad (KYC)"
    subtitle="Cola de documentos enviados por jugadores para verificación."
    :breadcrumb-items="[
      { label: 'Dashboard', route: '/dashboard', icon: 'fas fa-home' },
      { label: 'KYC' },
    ]"
  >
    <template #actions>
      <div class="kyc-stats">
        <StatTile label="Pendientes" :value="stats.pending" icon="fa-hourglass-half" :variant="stats.pending > 0 ? 'warning' : 'default'" />
        <StatTile label="Aprobados hoy" :value="stats.approved_today" icon="fa-check" variant="success" />
        <StatTile label="Rechazados hoy" :value="stats.rejected_today" icon="fa-xmark" variant="danger" />
        <StatTile label="Tasa de rechazo" :value="`${stats.rejection_rate}%`" icon="fa-chart-line" variant="info" />
      </div>
    </template>
  </PageHeader>

  <TableWithFilterBridge
    table-id="kyc-table"
    title="Verificación de identidad (KYC)"
    subtitle="Cola de documentos enviados por jugadores"
    search-placeholder="Buscar por nombre, username o email..."
    :columns="documents.columns"
    :rows="rows"
    :filters="filters"
    :extra-buttons="extraButtons"
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

    <!-- Columna: Tipo de documento -->
    <template #cell-document_type="{ value }">
      {{ documentTypeLabel(value) }}
    </template>

    <!-- Columna: Nivel KYC -->
    <template #cell-kyc_level="{ value }">
      <span class="badge bg-light text-dark border">{{ kycLevelLabel[value] ?? '—' }}</span>
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
          <span>{{ documentTypeLabel(detail.document_type) }}</span>
        </div>
        <div class="col-md-6">
          <small class="text-muted d-block">Número de documento</small>
          <span>{{ detail.document_number }}</span>
        </div>
        <div class="col-md-6">
          <small class="text-muted d-block">Nivel KYC actual</small>
          <span class="badge bg-light text-dark border">{{ kycLevelLabel[detail.user.kyc_level] ?? '—' }}</span>
        </div>
        <div class="col-md-6">
          <small class="text-muted d-block">Estado de esta solicitud</small>
          <span :class="statusBadgeClass[detail.status] ?? 'badge bg-secondary'">{{ statusLabel[detail.status] ?? detail.status }}</span>
        </div>
        <div class="col-md-6">
          <small class="text-muted d-block">Enviado</small>
          <span>{{ detail.created_at ?? '—' }}</span>
        </div>
        <div v-if="detail.reviewed_at" class="col-md-6">
          <small class="text-muted d-block">Revisado</small>
          <span>{{ detail.reviewed_at }}{{ detail.reviewed_by ? ` por ${detail.reviewed_by}` : '' }}</span>
        </div>
        <div v-if="detail.rejection_reason" class="col-12">
          <small class="text-muted d-block">Motivo de rechazo</small>
          <span class="text-danger">{{ detail.rejection_reason }}</span>
        </div>
      </div>

      <!-- Alerta de contexto: historial de envíos previos del mismo jugador -->
      <div v-if="detail.history.length > 0" class="kyc-history-alert mb-3">
        <div class="kyc-history-header">
          <i class="fas fa-clock-rotate-left me-2"></i>
          Este jugador tiene {{ detail.history.length }} envío{{ detail.history.length === 1 ? '' : 's' }} previo{{ detail.history.length === 1 ? '' : 's' }}
        </div>
        <ul class="kyc-history-list">
          <li v-for="entry in detail.history" :key="entry.id">
            <span :class="statusBadgeClass[entry.status] ?? 'badge bg-secondary'" class="kyc-history-badge">{{ statusLabel[entry.status] ?? entry.status }}</span>
            <span class="kyc-history-type">{{ documentTypeLabel(entry.document_type) }}</span>
            <span class="kyc-history-date">{{ entry.created_at }}</span>
            <span v-if="entry.rejection_reason" class="kyc-history-reason" :title="entry.rejection_reason">— {{ entry.rejection_reason }}</span>
          </li>
        </ul>
      </div>

      <div class="row g-3">
        <div v-if="detail.has_front" class="col-md-4">
          <small class="text-muted d-block mb-1">Frente</small>
          <button type="button" class="kyc-doc-image-btn" @click="openLightbox(detail.id, 'front', 'Frente del documento')">
            <img :src="imageUrl(detail.id, 'front')" class="kyc-doc-image" alt="Frente del documento" />
            <span class="kyc-doc-zoom-hint"><i class="fas fa-magnifying-glass-plus"></i></span>
          </button>
        </div>
        <div v-if="detail.has_back" class="col-md-4">
          <small class="text-muted d-block mb-1">Reverso</small>
          <button type="button" class="kyc-doc-image-btn" @click="openLightbox(detail.id, 'back', 'Reverso del documento')">
            <img :src="imageUrl(detail.id, 'back')" class="kyc-doc-image" alt="Reverso del documento" />
            <span class="kyc-doc-zoom-hint"><i class="fas fa-magnifying-glass-plus"></i></span>
          </button>
        </div>
        <div v-if="detail.has_selfie" class="col-md-4">
          <small class="text-muted d-block mb-1">Selfie</small>
          <button type="button" class="kyc-doc-image-btn" @click="openLightbox(detail.id, 'selfie', 'Selfie de verificación')">
            <img :src="imageUrl(detail.id, 'selfie')" class="kyc-doc-image" alt="Selfie de verificación" />
            <span class="kyc-doc-zoom-hint"><i class="fas fa-magnifying-glass-plus"></i></span>
          </button>
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

  <!-- Lightbox: zoom a pantalla completa de una imagen del documento -->
  <Teleport to="body">
    <div v-if="lightboxUrl" class="kyc-lightbox" @click.self="closeLightbox">
      <button type="button" class="kyc-lightbox-close" title="Cerrar" @click="closeLightbox">
        <i class="fas fa-times"></i>
      </button>
      <figure class="kyc-lightbox-figure">
        <img :src="lightboxUrl" :alt="lightboxLabel" class="kyc-lightbox-image" />
        <figcaption class="kyc-lightbox-caption">{{ lightboxLabel }}</figcaption>
      </figure>
    </div>
  </Teleport>
</template>

<style scoped>
.kyc-stats {
  display: grid;
  grid-template-columns: repeat(4, minmax(140px, 1fr));
  gap: 0.6rem;
}
@media (max-width: 992px) {
  .kyc-stats { grid-template-columns: repeat(2, minmax(140px, 1fr)); }
}

.kyc-doc-image-btn {
  position: relative;
  display: block;
  width: 100%;
  padding: 0;
  border: none;
  background: none;
  cursor: zoom-in;
}
.kyc-doc-image {
  width: 100%;
  border-radius: 8px;
  border: 1px solid var(--ds-border);
  object-fit: cover;
  max-height: 220px;
  display: block;
  transition: filter 0.15s;
}
.kyc-doc-image-btn:hover .kyc-doc-image { filter: brightness(0.85); }
.kyc-doc-zoom-hint {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 1.4rem;
  opacity: 0;
  transition: opacity 0.15s;
  pointer-events: none;
}
.kyc-doc-image-btn:hover .kyc-doc-zoom-hint { opacity: 1; }

.kyc-history-alert {
  background: var(--ds-surface-2);
  border: 1px solid var(--ds-border);
  border-radius: 10px;
  padding: 0.75rem 1rem;
}
.kyc-history-header { font-weight: 600; font-size: 0.85rem; color: var(--ds-text); margin-bottom: 0.5rem; }
.kyc-history-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 0.4rem; }
.kyc-history-list li { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; font-size: 0.78rem; }
.kyc-history-badge { font-size: 0.68rem; }
.kyc-history-type { color: var(--ds-text-2); }
.kyc-history-date { color: var(--ds-text-muted); }
.kyc-history-reason {
  color: #dc2626;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 260px;
}

.kyc-lightbox {
  position: fixed;
  inset: 0;
  z-index: 1200;
  background: rgba(0, 0, 0, 0.85);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  cursor: zoom-out;
}
.kyc-lightbox-close {
  position: absolute;
  top: 1.25rem;
  right: 1.25rem;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: none;
  background: rgba(255, 255, 255, 0.12);
  color: #fff;
  font-size: 1.1rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.15s;
}
.kyc-lightbox-close:hover { background: rgba(255, 255, 255, 0.22); }
.kyc-lightbox-figure { margin: 0; max-width: 90vw; max-height: 90vh; text-align: center; }
.kyc-lightbox-image { max-width: 100%; max-height: 80vh; border-radius: 8px; cursor: default; }
.kyc-lightbox-caption { color: #e5e7eb; font-size: 0.85rem; margin-top: 0.75rem; }
</style>
