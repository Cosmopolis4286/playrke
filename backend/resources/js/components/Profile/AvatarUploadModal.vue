<template>
  <div class="modal-backdrop" @click="$emit('close')">
    <div class="modal-dialog modal-dialog-centered" @click.stop>
      <div class="modal-content">
        <div class="modal-header border-0">
          <h5 class="modal-title">
            <i class="fas fa-camera me-2"></i>Actualizar Avatar
          </h5>
          <button type="button" class="btn-close" @click="$emit('close')"></button>
        </div>
        
        <div class="modal-body">
          <!-- Preview del Avatar -->
          <div class="text-center mb-4">
            <div class="avatar-preview">
              <img 
                :src="previewUrl || currentAvatar || '/img/fav.ico'" 
                alt="Avatar Preview"
                class="rounded-circle"
                :class="{ 'avatar-uploaded': selectedFile }"
              >
              <div v-if="selectedFile" class="avatar-upload-indicator">
                <i class="fas fa-check"></i>
              </div>
            </div>
            <h6 class="mt-3 mb-1">
              {{ selectedFile ? 'Nuevo Avatar' : 'Avatar Actual' }}
            </h6>
            <small class="text-muted">
              {{ selectedFile ? selectedFile.name : 'Sin cambios' }}
            </small>
          </div>

          <!-- Formulario de Subida -->
          <form @submit.prevent="handleSubmit">
            <!-- Input de Archivo -->
            <div class="mb-3">
              <label for="avatar" class="form-label">Seleccionar Imagen</label>
              <input 
                type="file" 
                id="avatar"
                ref="fileInput"
                @change="handleFileChange"
                class="form-control"
                :class="{ 'is-invalid': errors.file }"
                accept="image/jpeg,image/png,image/jpg,image/gif"
                required
              >
              <div v-if="errors.file" class="invalid-feedback">
                {{ errors.file }}
              </div>
              <small class="form-text text-muted">
                Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 2MB
              </small>
            </div>

            <!-- Información del archivo -->
            <div v-if="selectedFile" class="alert alert-info">
              <h6 class="alert-heading">
                <i class="fas fa-info-circle me-2"></i>Información del Archivo:
              </h6>
              <div class="row small">
                <div class="col-6">
                  <strong>Nombre:</strong> {{ selectedFile.name }}
                </div>
                <div class="col-6">
                  <strong>Tamaño:</strong> {{ formatFileSize(selectedFile.size) }}
                </div>
                <div class="col-6">
                  <strong>Tipo:</strong> {{ selectedFile.type }}
                </div>
                <div class="col-6">
                  <strong>Dimensiones:</strong> {{ imageDimensions || 'No disponible' }}
                </div>
              </div>
            </div>

            <!-- Opciones de Edición -->
            <div v-if="selectedFile && imageDimensions" class="mb-3">
              <label class="form-label">Opciones de Edición</label>
              <div class="row">
                <div class="col-md-6 mb-2">
                  <div class="form-check">
                    <input 
                      class="form-check-input" 
                      type="checkbox" 
                      id="auto_resize"
                      v-model="autoResize"
                    >
                    <label class="form-check-label" for="auto_resize">
                      Redimensionar automáticamente (400x400px)
                    </label>
                  </div>
                </div>
                <div class="col-md-6 mb-2">
                  <div class="form-check">
                    <input 
                      class="form-check-input" 
                      type="checkbox" 
                      id="compress"
                      v-model="compress"
                    >
                    <label class="form-check-label" for="compress">
                      Comprimir imagen
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <!-- Botones de Acción -->
            <div class="d-flex justify-content-between">
              <button 
                v-if="currentAvatar" 
                type="button" 
                class="btn btn-outline-danger"
                @click="handleDeleteAvatar"
                :disabled="deleting"
              >
                <i class="fas fa-trash me-2"></i>
                {{ deleting ? 'Eliminando...' : 'Eliminar Avatar' }}
              </button>
              
              <div class="d-flex gap-2 ms-auto">
                <button type="button" class="btn btn-secondary" @click="$emit('close')">
                  <i class="fas fa-times me-2"></i>Cancelar
                </button>
                <button 
                  type="submit" 
                  class="btn btn-primary" 
                  :disabled="!selectedFile || uploading"
                >
                  <i class="fas fa-upload me-2"></i>
                  {{ uploading ? 'Subiendo...' : 'Subir Avatar' }}
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { showToast } from '@js/utils/toastNotify'

const props = defineProps({
    currentAvatar: {
        type: String,
        default: null
    }
})

const emit = defineEmits(['close', 'avatar-updated'])

// Estado local
const fileInput = ref<HTMLInputElement | null>(null)
const selectedFile = ref<File | null>(null)
const previewUrl = ref<string>('')
const imageDimensions = ref<string>('')
const errors = ref<Record<string, string>>({})
const uploading = ref<boolean>(false)
const deleting = ref<boolean>(false)
const autoResize = ref<boolean>(true)
const compress = ref<boolean>(true)

// Formulario
const form = useForm({
    avatar: null as File | null,
    auto_resize: true,
    compress: true
})

// Computadas
const isValidFile = computed((): boolean => {
    if (!selectedFile.value) return false
    
    const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif']
    const maxSize = 2 * 1024 * 1024 // 2MB
    
    return validTypes.includes(selectedFile.value.type) && 
           selectedFile.value.size <= maxSize
})

// Métodos
const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement
    const file = target.files?.[0]
    
    if (!file) {
        selectedFile.value = null
        previewUrl.value = ''
        imageDimensions.value = ''
        return
    }
    
    // Validar archivo
    const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif']
    const maxSize = 2 * 1024 * 1024 // 2MB
    
    if (!validTypes.includes(file.type)) {
        errors.value.file = 'Formato de archivo no válido. Usa JPG, PNG o GIF.'
        return
    }
    
    if (file.size > maxSize) {
        errors.value.file = 'El archivo es demasiado grande. Máximo 2MB.'
        return
    }
    
    // Limpiar errores
    errors.value = {}
    
    selectedFile.value = file
    
    // Crear preview
    const reader = new FileReader()
    reader.onload = (e) => {
        const result = e.target?.result
        if (typeof result === 'string') {
            previewUrl.value = result
            
            // Obtener dimensiones de la imagen
            const img = new Image()
            img.onload = () => {
                imageDimensions.value = `${img.width}x${img.height}px`
            }
            img.src = result
        }
    }
    reader.readAsDataURL(file)
}

const handleSubmit = () => {
    if (!selectedFile.value || !isValidFile.value) {
        showToast('Por favor selecciona un archivo válido', 'error')
        return
    }
    
    uploading.value = true
    
    const formData = new FormData()
    formData.append('avatar', selectedFile.value)
    formData.append('auto_resize', autoResize.value.toString())
    formData.append('compress', compress.value.toString())
    
    // Usar fetch para subir
    fetch('/profile/api/avatar', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(response => {
        if (response.success) {
            showToast('Avatar actualizado exitosamente', 'success')
            emit('avatar-updated', response.data.avatar)
            emit('close')
        } else {
            showToast('Error al actualizar el avatar', 'error')
        }
    })
    .catch(error => {
        console.error('Avatar upload error:', error)
        showToast('Error al subir el avatar', 'error')
    })
    .finally(() => {
        uploading.value = false
    })
}

const handleDeleteAvatar = () => {
    if (!confirm('¿Estás seguro de que deseas eliminar tu avatar actual?')) {
        return
    }
    
    deleting.value = true
    
    fetch('/profile/api/avatar', {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(response => {
        if (response.success) {
            showToast('Avatar eliminado exitosamente', 'success')
            emit('avatar-updated', null)
            emit('close')
        } else {
            showToast('Error al eliminar el avatar', 'error')
        }
    })
    .catch(error => {
        console.error('Avatar delete error:', error)
        showToast('Error al eliminar el avatar', 'error')
    })
    .finally(() => {
        deleting.value = false
    })
}

const formatFileSize = (bytes: number): string => {
    if (bytes === 0) return '0 Bytes'
    
    const k = 1024
    const sizes = ['Bytes', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}
</script>

<style scoped>
.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1050;
}

.modal-dialog {
    max-width: 600px;
    margin: 0;
}

.modal-content {
    border-radius: 12px;
    border: none;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.modal-header {
    border-radius: 12px 12px 0 0;
}

.avatar-preview {
    position: relative;
    display: inline-block;
}

.avatar-preview img {
    width: 120px;
    height: 120px;
    object-fit: cover;
    border: 4px solid #0d6efd;
    transition: all 0.3s ease;
}

.avatar-preview img.avatar-uploaded {
    border-color: #28a745;
    transform: scale(1.05);
}

.avatar-upload-indicator {
    position: absolute;
    bottom: 5px;
    right: 5px;
    width: 30px;
    height: 30px;
    background-color: #28a745;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    border: 3px solid white;
}

.form-control, .btn {
    border-radius: 8px;
}

.form-control:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.alert {
    border-radius: 8px;
    border: none;
}

.form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}
</style>
