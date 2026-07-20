<template>
  <Modal
    v-model="open"
    title="Actualizar Avatar"
    icon="fa-camera"
    size="md"
    :static="true"
    :show-confirm="false"
    @update:model-value="v => !v && $emit('close')"
  >
    <!-- ── Preview ── -->
    <div class="avatar-preview-wrap">
      <div class="avatar-ring" :class="{ 'avatar-ring--ready': !!selectedFile }">
        <img
          :src="previewUrl || currentAvatar || '/img/fav.ico'"
          alt="Avatar"
          class="avatar-preview-img"
        />
        <div v-if="selectedFile" class="avatar-check">
          <i class="fas fa-check"></i>
        </div>
      </div>
      <p class="avatar-status">
        {{ selectedFile ? selectedFile.name : (currentAvatar ? 'Avatar actual' : 'Sin avatar') }}
      </p>
    </div>

    <!-- ── Drop zone ── -->
    <div
      class="dropzone"
      :class="{ 'dropzone--over': isDragging, 'dropzone--error': fileError }"
      @dragover.prevent="isDragging = true"
      @dragleave.prevent="isDragging = false"
      @drop.prevent="onDrop"
      @click="fileInputRef?.click()"
    >
      <input
        ref="fileInputRef"
        type="file"
        class="sr-only"
        accept="image/jpeg,image/png,image/jpg,image/gif"
        @change="onFileChange"
      />
      <div class="dropzone-inner">
        <i class="fas fa-cloud-upload-alt dropzone-icon"></i>
        <p class="dropzone-text">
          <span class="dropzone-link">Haz clic para seleccionar</span>
          &nbsp;o arrastra la imagen aquí
        </p>
        <p class="dropzone-hint">JPG, PNG, GIF · Máx. 5 MB · Se comprime automáticamente</p>
      </div>
    </div>

    <!-- ── Error ── -->
    <p v-if="fileError" class="upload-error">
      <i class="fas fa-exclamation-circle me-1"></i>{{ fileError }}
    </p>

    <!-- ── File info ── -->
    <div v-if="selectedFile && !fileError" class="file-info">
      <div class="file-info-row">
        <span class="file-info-key">Archivo</span>
        <span class="file-info-val">{{ selectedFile.name }}</span>
      </div>
      <div class="file-info-row">
        <span class="file-info-key">Tamaño</span>
        <span class="file-info-val">{{ formatSize(selectedFile.size) }}</span>
      </div>
      <div v-if="imageDimensions" class="file-info-row">
        <span class="file-info-key">Dimensiones</span>
        <span class="file-info-val">{{ imageDimensions }}</span>
      </div>
    </div>

    <template #footer>
      <button
        v-if="currentAvatar"
        type="button"
        class="action-btn action-btn--danger me-auto"
        :disabled="deleting"
        @click="handleDelete"
      >
        <i class="fas fa-trash me-1"></i>
        {{ deleting ? 'Eliminando...' : 'Eliminar' }}
      </button>

      <button type="button" class="action-btn action-btn--ghost" @click="$emit('close')">
        Cancelar
      </button>
      <button
        type="button"
        class="action-btn action-btn--primary"
        :disabled="!selectedFile || !!fileError || uploading"
        @click="handleUpload"
      >
        <i class="fas fa-upload me-1"></i>
        {{ uploading ? 'Subiendo...' : 'Subir avatar' }}
      </button>
    </template>

  </Modal>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { showToast } from '@js/utils/toastNotify'
import Modal from '@js/Components/Common/Modal/Modal.vue'

const props = defineProps<{ currentAvatar?: string | null }>()
const emit  = defineEmits<{ close: []; 'avatar-updated': [avatar: string | null] }>()

const open          = ref(true)
const fileInputRef  = ref<HTMLInputElement | null>(null)
const selectedFile  = ref<File | null>(null)
const previewUrl    = ref('')
const imageDimensions = ref('')
const fileError     = ref('')
const isDragging    = ref(false)
const uploading     = ref(false)
const deleting      = ref(false)

// ── Helpers ────────────────────────────────────────────────────────────────
const VALID_TYPES = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif']
const MAX_SIZE    = 5 * 1024 * 1024  // 5 MB — el backend comprime antes de guardar

function formatSize(bytes: number) {
    if (bytes < 1024) return bytes + ' B'
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
    return (bytes / (1024 * 1024)).toFixed(2) + ' MB'
}

function csrf() {
    return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? ''
}

function validateAndSet(file: File) {
    fileError.value = ''
    if (!VALID_TYPES.includes(file.type)) {
        fileError.value = 'Formato no válido. Usa JPG, PNG o GIF.'
        return
    }
    if (file.size > MAX_SIZE) {
        fileError.value = 'El archivo supera el límite de 2 MB.'
        return
    }
    selectedFile.value = file

    const reader = new FileReader()
    reader.onload = e => {
        const src = e.target?.result as string
        previewUrl.value = src
        const img = new Image()
        img.onload = () => { imageDimensions.value = `${img.width} × ${img.height} px` }
        img.src = src
    }
    reader.readAsDataURL(file)
}

function onFileChange(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0]
    if (file) validateAndSet(file)
}

function onDrop(e: DragEvent) {
    isDragging.value = false
    const file = e.dataTransfer?.files?.[0]
    if (file) validateAndSet(file)
}

// ── Upload ─────────────────────────────────────────────────────────────────
async function handleUpload() {
    if (!selectedFile.value || fileError.value) return
    uploading.value = true
    const body = new FormData()
    body.append('avatar', selectedFile.value)
    try {
        const res  = await fetch('/profile/avatar', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
            body,
        })
        const data = await res.json()
        if (data.success) {
            showToast('Avatar actualizado', 'success')
            emit('avatar-updated', data.data.avatar)
            emit('close')
        } else {
            showToast('Error al subir el avatar', 'error')
        }
    } catch {
        showToast('Error de conexión', 'error')
    } finally {
        uploading.value = false
    }
}

// ── Delete ─────────────────────────────────────────────────────────────────
async function handleDelete() {
    if (!confirm('¿Eliminar tu avatar actual?')) return
    deleting.value = true
    try {
        const res  = await fetch('/profile/avatar', {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
        })
        const data = await res.json()
        if (data.success) {
            showToast('Avatar eliminado', 'success')
            emit('avatar-updated', null)
            emit('close')
        } else {
            showToast('Error al eliminar el avatar', 'error')
        }
    } catch {
        showToast('Error de conexión', 'error')
    } finally {
        deleting.value = false
    }
}
</script>

<style scoped>
.sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0,0,0,0); }

/* ── Preview ── */
.avatar-preview-wrap {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 1.25rem;
}
.avatar-ring {
    position: relative;
    width: 96px;
    height: 96px;
    border-radius: 50%;
    padding: 3px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    transition: background 0.3s;
}
.avatar-ring--ready {
    background: linear-gradient(135deg, #10b981, #059669);
}
.avatar-preview-img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #fff;
    display: block;
}
.avatar-check {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #10b981;
    border: 2px solid #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.6rem;
    color: #fff;
}
.avatar-status {
    margin-top: 8px;
    font-size: 0.75rem;
    color: var(--ds-text-2);
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    text-align: center;
}

/* ── Dropzone ── */
.dropzone {
    border: 2px dashed var(--ds-border-2);
    border-radius: 12px;
    padding: 1.5rem 1rem;
    text-align: center;
    cursor: pointer;
    transition: border-color 0.2s, background 0.2s;
    background: var(--ds-surface-2);
    margin-bottom: 0.75rem;
}
.dropzone:hover,
.dropzone--over {
    border-color: #667eea;
    background: rgba(102,126,234,0.08);
}
.dropzone--error {
    border-color: #ef4444;
    background: #fff5f5;
}
.dropzone-inner { pointer-events: none; }
.dropzone-icon {
    font-size: 1.75rem;
    color: #667eea;
    margin-bottom: 8px;
    display: block;
}
.dropzone-text {
    font-size: 0.82rem;
    color: var(--ds-text-2);
    margin: 0 0 4px;
}
.dropzone-link {
    color: #667eea;
    font-weight: 600;
    text-decoration: underline;
}
.dropzone-hint {
    font-size: 0.72rem;
    color: var(--ds-text-muted);
    margin: 0;
}

/* ── Error ── */
.upload-error {
    font-size: 0.75rem;
    color: #ef4444;
    margin: 0 0 0.75rem;
}

/* ── File info ── */
.file-info {
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    border-radius: 10px;
    padding: 0.65rem 0.9rem;
    margin-bottom: 1.1rem;
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.file-info-row {
    display: flex;
    justify-content: space-between;
    gap: 8px;
    font-size: 0.75rem;
}
.file-info-key { color: var(--ds-text-2); font-weight: 600; }
.file-info-val { color: var(--ds-text); word-break: break-all; text-align: right; }

/* ── Actions ── */
.modal-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    flex-wrap: wrap;
}
.actions-right { display: flex; gap: 8px; margin-left: auto; }

.action-btn {
    display: inline-flex;
    align-items: center;
    padding: 9px 22px;
    border-radius: 10px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: all 0.15s;
    line-height: 1.4;
    white-space: nowrap;
}
.action-btn--ghost {
    background: transparent;
    color: var(--ds-text-2);
    border: 1.5px solid var(--ds-border-2);
}
.action-btn--ghost:hover { background: var(--ds-surface-2); }
.action-btn--primary {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: #fff;
    box-shadow: 0 2px 8px rgba(102,126,234,0.28);
}
.action-btn--primary:hover:not(:disabled) { filter: brightness(1.08); transform: translateY(-1px); }
.action-btn--primary:disabled { opacity: 0.55; cursor: not-allowed; transform: none; }
.action-btn--danger {
    background: #fff5f5;
    color: #ef4444;
    border: 1.5px solid #fca5a5;
}
.action-btn--danger:hover:not(:disabled) { background: #ef4444; color: #fff; }
.action-btn--danger:disabled { opacity: 0.5; cursor: not-allowed; }
</style>
