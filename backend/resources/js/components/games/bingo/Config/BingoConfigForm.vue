<script setup lang="ts" name="BingoConfigForm">
  import { ref, computed } from 'vue'
  import FormTextArea from '@/js/Components/Common/Form/FormTextArea.vue'

  const props = defineProps<{
    initialRules?: string
    currentImage?: string | null
    loadingRules?: boolean
    loadingImage?: boolean
  }>()

  const emit = defineEmits<{
    (e: 'save-rules', rules: string): void
    (e: 'save-image', image: File): void
  }>()

  /* ── Imagen ── */
  const selectedFile  = ref<File | null>(null)
  const imagePreview  = ref<string | null>(null)
  const imageError    = ref<string | null>(null)

  function onFileChange(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null
    imageError.value = null

    if (!file) {
      selectedFile.value = null
      imagePreview.value = null
      return
    }

    if (!['image/jpeg', 'image/png'].includes(file.type)) {
      imageError.value = 'Solo se aceptan imágenes JPEG o PNG.'
      return
    }

    if (file.size > 2 * 1024 * 1024) {
      imageError.value = 'La imagen no puede superar los 2 MB.'
      return
    }

    if (imagePreview.value) URL.revokeObjectURL(imagePreview.value)

    selectedFile.value = file
    imagePreview.value = URL.createObjectURL(file)
  }

  function submitImage(): void {
    if (!selectedFile.value) {
      imageError.value = 'Selecciona una imagen primero.'
      return
    }
    emit('save-image', selectedFile.value)
  }

  const currentImageSrc = computed(() =>
    imagePreview.value
      ?? (props.currentImage ? `/storage/game/bingo/${props.currentImage}` : null)
  )

  /* ── Reglamento ── */
  const rules      = ref(props.initialRules ?? '')
  const rulesError = computed<string | null>(() => {
    if (!rules.value.trim()) return 'El reglamento es obligatorio.'
    if (rules.value.trim().length < 10) return 'Debe tener al menos 10 caracteres.'
    return null
  })

  function submitRules(): void {
    if (rulesError.value) return
    emit('save-rules', rules.value)
  }
</script>

<template>
  <div class="row g-4">

    <!-- ══════════════════════════════════
         SECCIÓN: IMAGEN
    ══════════════════════════════════ -->
    <div class="col-12 col-lg-5">
      <div class="card h-100 shadow-sm border-0">
        <div class="card-header bg-white border-bottom py-3">
          <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
            <i class="fa-regular fa-image text-primary" />
            Imagen del reglamento
          </h6>
        </div>

        <div class="card-body d-flex flex-column gap-3">

          <!-- Preview actual o placeholder -->
          <div
            class="bingo-image-preview rounded border d-flex align-items-center justify-content-center overflow-hidden"
            :class="currentImageSrc ? 'border-light' : 'border-dashed bg-light'"
          >
            <img
              v-if="currentImageSrc"
              :src="currentImageSrc"
              alt="Imagen del reglamento"
              class="img-fluid w-100 h-100 object-fit-cover"
            />
            <div v-else class="text-center text-muted p-4">
              <i class="fa-regular fa-image fa-3x mb-2 d-block opacity-25" />
              <small>Sin imagen cargada</small>
            </div>
          </div>

          <!-- File input estilizado -->
          <div class="form-group mb-0">
            <label for="bingo-image" class="form-label fw-semibold mb-1">
              Seleccionar archivo
            </label>
            <input
              id="bingo-image"
              type="file"
              class="form-control form-control-sm"
              :class="{ 'is-invalid': imageError }"
              accept="image/jpeg,image/png"
              @change="onFileChange"
            />
            <div v-if="imageError" class="invalid-feedback">{{ imageError }}</div>
            <small v-else class="text-muted">PNG o JPEG · máx. 2 MB</small>
          </div>

          <!-- Nombre del archivo seleccionado -->
          <div v-if="selectedFile" class="d-flex align-items-center gap-2 text-success small">
            <i class="fa-solid fa-circle-check" />
            <span class="text-truncate">{{ selectedFile.name }}</span>
          </div>

        </div>

        <div class="card-footer bg-white border-top pt-3 pb-3 d-flex justify-content-end">
          <button
            type="button"
            class="btn btn-primary btn-sm px-4"
            :disabled="loadingImage || !selectedFile"
            @click="submitImage"
          >
            <span v-if="loadingImage" class="spinner-border spinner-border-sm me-1" aria-hidden="true" />
            <i v-else class="fa-solid fa-cloud-arrow-up me-1" />
            {{ loadingImage ? 'Guardando…' : 'Guardar imagen' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════
         SECCIÓN: REGLAMENTO
    ══════════════════════════════════ -->
    <div class="col-12 col-lg-7">
      <div class="card h-100 shadow-sm border-0">
        <div class="card-header bg-white border-bottom py-3">
          <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
            <i class="fa-regular fa-file-lines text-primary" />
            Texto del reglamento
          </h6>
        </div>

        <div class="card-body">
          <FormTextArea
            name="rules"
            col="col-12"
            placeholder="Escribe aquí el reglamento del bingo…"
            :rows="12"
            :required="true"
            :error="rulesError ?? undefined"
            :modelValue="rules"
            resize="vertical"
            @update:modelValue="rules = $event"
          />
        </div>

        <div class="card-footer bg-white border-top pt-3 pb-3 d-flex justify-content-between align-items-center">
          <small class="text-muted">
            {{ rules.trim().length }} / 10.000 caracteres
          </small>
          <button
            type="button"
            class="btn btn-primary btn-sm px-4"
            :disabled="loadingRules || !!rulesError"
            @click="submitRules"
          >
            <span v-if="loadingRules" class="spinner-border spinner-border-sm me-1" aria-hidden="true" />
            <i v-else class="fa-solid fa-floppy-disk me-1" />
            {{ loadingRules ? 'Guardando…' : 'Guardar reglamento' }}
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
.bingo-image-preview {
  width: 100%;
  height: 220px;
}

.border-dashed {
  border-style: dashed !important;
}

.object-fit-cover {
  object-fit: cover;
}
</style>
