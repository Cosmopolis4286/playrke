<script setup lang="ts">
import { ref, computed } from 'vue'
import Modal from '@js/Components/Common/Modal/Modal.vue'

defineProps<{
  modelValue: boolean
  uploading: boolean
  error: string | null
  success: string | null
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', v: boolean): void
  (e: 'submit', form: { document_type: string; document_number: string; front: File; back: File | null; selfie: File | null }): void
}>()

const docType   = ref('dni')
const docNumber = ref('')
const frontFile = ref<File | null>(null)
const backFile  = ref<File | null>(null)
const selfieFile= ref<File | null>(null)

const DOC_TYPES = [
  { value: 'dni',              label: 'Cédula / DNI' },
  { value: 'passport',         label: 'Pasaporte' },
  { value: 'driving_license',  label: 'Licencia de conducir' },
  { value: 'residence_permit', label: 'Permiso de residencia' },
]

const canSubmit = computed(() => docType.value && docNumber.value.trim() && frontFile.value)

function onFile(e: Event, target: 'front' | 'back' | 'selfie') {
  const file = (e.target as HTMLInputElement).files?.[0] ?? null
  if (target === 'front')  frontFile.value  = file
  if (target === 'back')   backFile.value   = file
  if (target === 'selfie') selfieFile.value = file
}

function handleSubmit() {
  if (!frontFile.value) return
  emit('submit', {
    document_type:   docType.value,
    document_number: docNumber.value.trim(),
    front:   frontFile.value,
    back:    backFile.value,
    selfie:  selfieFile.value,
  })
}
</script>

<template>
  <Modal
    :model-value="modelValue"
    title="Verificar identidad (KYC)"
    icon="fa-id-card"
    size="md"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <!-- Éxito -->
    <div v-if="success" class="kyc-upload-success">
      <i class="fas fa-check-circle kyc-upload-success__icon" />
      <p class="kyc-upload-success__title">¡Documentos enviados!</p>
      <p class="kyc-upload-success__sub">{{ success }}</p>
    </div>

    <div v-else class="kyc-upload-form">
      <p class="kyc-upload-intro">
        Sube tu documento de identidad para ampliar tus límites de retiro.
        Los documentos se almacenan de forma segura y solo los revisa nuestro equipo.
      </p>

      <!-- Tipo de documento -->
      <div class="kyc-field">
        <label class="kyc-label">Tipo de documento</label>
        <select v-model="docType" class="kyc-select">
          <option v-for="t in DOC_TYPES" :key="t.value" :value="t.value">{{ t.label }}</option>
        </select>
      </div>

      <!-- Número -->
      <div class="kyc-field">
        <label class="kyc-label">Número de documento</label>
        <input v-model="docNumber" type="text" class="kyc-input" placeholder="Ej: V-12345678" maxlength="50" />
      </div>

      <!-- Frente -->
      <div class="kyc-field">
        <label class="kyc-label">Frente del documento <span class="kyc-required">*</span></label>
        <label class="kyc-file-drop" :class="{ 'kyc-file-drop--ok': frontFile }">
          <input type="file" accept="image/jpeg,image/png,application/pdf" class="kyc-file-input" @change="onFile($event, 'front')" />
          <template v-if="frontFile">
            <i class="fas fa-file-check kyc-file-drop__icon kyc-file-drop__icon--ok" />
            <span class="kyc-file-drop__name">{{ frontFile.name }}</span>
          </template>
          <template v-else>
            <i class="fas fa-cloud-upload-alt kyc-file-drop__icon" />
            <span class="kyc-file-drop__name">Selecciona frente del documento</span>
            <span class="kyc-file-drop__hint">JPG, PNG o PDF · máx. 10 MB</span>
          </template>
        </label>
      </div>

      <!-- Dorso (opcional) -->
      <div class="kyc-field">
        <label class="kyc-label">Dorso del documento <span class="kyc-optional">(opcional)</span></label>
        <label class="kyc-file-drop" :class="{ 'kyc-file-drop--ok': backFile }">
          <input type="file" accept="image/jpeg,image/png,application/pdf" class="kyc-file-input" @change="onFile($event, 'back')" />
          <template v-if="backFile">
            <i class="fas fa-file-check kyc-file-drop__icon kyc-file-drop__icon--ok" />
            <span class="kyc-file-drop__name">{{ backFile.name }}</span>
          </template>
          <template v-else>
            <i class="fas fa-cloud-upload-alt kyc-file-drop__icon" />
            <span class="kyc-file-drop__name">Selecciona dorso (si aplica)</span>
          </template>
        </label>
      </div>

      <!-- Error -->
      <Transition name="msg-fade">
        <div v-if="error" class="kyc-error">
          <i class="fas fa-exclamation-triangle" /> {{ error }}
        </div>
      </Transition>
    </div>

    <template #footer>
      <template v-if="success">
        <button type="button" class="kyc-btn kyc-btn--ghost" @click="emit('update:modelValue', false)">
          <i class="fas fa-check" /> Entendido
        </button>
      </template>
      <template v-else>
        <button type="button" class="kyc-btn kyc-btn--ghost" @click="emit('update:modelValue', false)">Cancelar</button>
        <button
          type="button"
          class="kyc-btn kyc-btn--primary"
          :disabled="uploading || !canSubmit"
          @click="handleSubmit"
        >
          <span v-if="uploading" class="kyc-spinner" />
          <i v-else class="fas fa-paper-plane" />
          {{ uploading ? 'Enviando...' : 'Enviar documentos' }}
        </button>
      </template>
    </template>
  </Modal>
</template>

<style scoped>
.kyc-upload-intro { font-size:0.82rem;color:#64748b;line-height:1.5;margin:0 0 1rem; }
.kyc-upload-form  { display:flex;flex-direction:column;gap:0.9rem; }

.kyc-upload-success { display:flex;flex-direction:column;align-items:center;gap:0.75rem;padding:1rem 0;text-align:center; }
.kyc-upload-success__icon { font-size:2.5rem;color:#22c55e; }
.kyc-upload-success__title{ font-size:1rem;font-weight:700;color:#1e293b;margin:0; }
.kyc-upload-success__sub  { font-size:0.82rem;color:#64748b;margin:0; }

.kyc-field  { display:flex;flex-direction:column;gap:0.35rem; }
.kyc-label  { font-size:0.8rem;font-weight:600;color:#374151; }
.kyc-required{ color:#ef4444;margin-left:2px; }
.kyc-optional{ font-size:0.72rem;color:#94a3b8;font-weight:400; }

.kyc-input,.kyc-select {
  border:1.5px solid #e2e8f0;border-radius:10px;padding:9px 12px;
  font-size:0.85rem;color:#1e293b;background:#fff;width:100%;box-sizing:border-box;outline:none;
  transition:border-color 0.15s;
}
.kyc-input:focus,.kyc-select:focus { border-color:#667eea; }

.kyc-file-drop {
  display:flex;flex-direction:column;align-items:center;justify-content:center;gap:0.3rem;
  border:1.5px dashed #cbd5e1;border-radius:12px;padding:1rem;cursor:pointer;
  transition:border-color 0.15s,background 0.15s;background:#f8fafc;text-align:center;
}
.kyc-file-drop:hover          { border-color:#667eea;background:#f0f2ff; }
.kyc-file-drop--ok            { border-color:#22c55e;background:#f0fdf4; }
.kyc-file-input               { display:none; }
.kyc-file-drop__icon          { font-size:1.3rem;color:#94a3b8; }
.kyc-file-drop__icon--ok      { color:#22c55e; }
.kyc-file-drop__name          { font-size:0.8rem;color:#475569;font-weight:500; }
.kyc-file-drop__hint          { font-size:0.72rem;color:#94a3b8; }

.kyc-error { background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:0.6rem 0.8rem;font-size:0.82rem;color:#dc2626;display:flex;gap:0.4rem;align-items:center; }

.kyc-btn   { display:inline-flex;align-items:center;gap:0.4rem;padding:9px 20px;border-radius:10px;font-size:0.85rem;font-weight:600;cursor:pointer;border:none;transition:all 0.15s; }
.kyc-btn--ghost   { background:transparent;color:#64748b;border:1.5px solid #e2e8f0; }
.kyc-btn--ghost:hover { background:#f1f5f9; }
.kyc-btn--primary { background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;box-shadow:0 2px 8px rgba(102,126,234,0.3); }
.kyc-btn--primary:hover:not(:disabled) { filter:brightness(1.08);transform:translateY(-1px); }
.kyc-btn--primary:disabled { opacity:0.55;cursor:not-allowed;transform:none; }

.kyc-spinner { display:inline-block;width:13px;height:13px;border:2px solid rgba(255,255,255,0.4);border-top-color:#fff;border-radius:50%;animation:spin 0.6s linear infinite; }
@keyframes spin { to { transform:rotate(360deg); } }

.msg-fade-enter-active,.msg-fade-leave-active { transition:opacity 0.2s; }
.msg-fade-enter-from,.msg-fade-leave-to       { opacity:0; }
</style>
