<template>
  <div class="row">
    <!-- Información de Seguridad -->
    <div class="col-md-8">
      <div class="card">
        <div class="card-header bg-light">
          <h6 class="mb-0">
            <i class="fas fa-shield-alt me-2"></i>Configuración de Seguridad
          </h6>
        </div>
        <div class="card-body">
          <!-- Cambio de Contraseña -->
          <div class="mb-4">
            <h6 class="mb-3">Contraseña</h6>
            <p class="text-muted mb-3">
              Cambia tu contraseña regularmente para mantener tu cuenta segura.
            </p>
            <button 
              @click="$emit('show-password-modal')"
              class="btn btn-warning"
            >
              <i class="fas fa-lock me-2"></i>Cambiar Contraseña
            </button>
          </div>

          <!-- Autenticación de Dos Factores -->
          <div class="mb-4">
            <h6 class="mb-3">Autenticación de Dos Factores (2FA)</h6>
            <p class="text-muted mb-3">
              Añade una capa adicional de seguridad a tu cuenta.
            </p>
            <div class="d-flex align-items-center">
              <div class="form-check form-switch me-3">
                <input 
                  class="form-check-input" 
                  type="checkbox" 
                  id="two_factor_enabled"
                  v-model="twoFactorEnabled"
                  @change="handleTwoFactorToggle"
                >
                <label class="form-check-label" for="two_factor_enabled">
                  Activar 2FA
                </label>
              </div>
              <small class="text-muted">
                {{ twoFactorEnabled ? 'Protegido con 2FA' : 'Sin protección adicional' }}
              </small>
            </div>
          </div>

          <!-- Sesiones Activas -->
          <div class="mb-4">
            <h6 class="mb-3">Sesiones Activas</h6>
            <p class="text-muted mb-3">
              Revisa y administra las sesiones activas en tus dispositivos.
            </p>
            <div class="list-group">
              <div class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                  <div class="fw-bold">Sesión Actual</div>
                  <small class="text-muted">{{ currentDeviceInfo }}</small>
                  <div class="text-success small">
                    <i class="fas fa-circle me-1"></i>Activa ahora
                  </div>
                </div>
                <span class="badge bg-success">Actual</span>
              </div>
              
              <div v-for="session in activeSessions" :key="session.id" class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                  <div class="fw-bold">{{ session.device }}</div>
                  <small class="text-muted">{{ session.location }}</small>
                  <div class="text-muted small">
                    Última actividad: {{ formatDate(session.last_activity) }}
                  </div>
                </div>
                <button 
                  @click="revokeSession(session.id)"
                  class="btn btn-sm btn-outline-danger"
                >
                  <i class="fas fa-sign-out-alt"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- Cerrar Todas las Sesiones -->
          <div class="mb-4">
            <h6 class="mb-3">Acciones de Seguridad</h6>
            <button 
              @click="revokeAllSessions"
              class="btn btn-outline-danger me-2"
            >
              <i class="fas fa-sign-out-alt me-2"></i>Cerrar Todas las Sesiones
            </button>
            <button 
              @click="downloadUserData"
              class="btn btn-outline-info"
            >
              <i class="fas fa-download me-2"></i>Descargar Mis Datos
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Información de Cuenta -->
    <div class="col-md-4">
      <!-- Estado de la Cuenta -->
      <div class="card bg-light mb-3">
        <div class="card-body">
          <h6 class="card-title mb-3">
            <i class="fas fa-user-shield me-2"></i>Estado de la Cuenta
          </h6>
          
          <div class="mb-3">
            <small class="text-muted">Estado:</small>
            <div>
              <span class="badge bg-success">Activa</span>
            </div>
          </div>
          
          <div class="mb-3">
            <small class="text-muted">Verificación de Email:</small>
            <div>
              <span v-if="user.is_verified" class="badge bg-success">
                <i class="fas fa-check-circle me-1"></i>Verificado
              </span>
              <span v-else class="badge bg-warning">
                <i class="fas fa-exclamation-triangle me-1"></i>No Verificado
              </span>
            </div>
          </div>
          
          <div class="mb-3">
            <small class="text-muted">Autenticación:</small>
            <div>
              <span v-if="user.is_verified" class="badge bg-info">
                <i class="fas fa-shield-alt me-1"></i>Segura
              </span>
              <span v-else class="badge bg-secondary">
                <i class="fas fa-unlock me-1"></i>Básica
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Recomendaciones de Seguridad -->
      <div class="card bg-light mb-3">
        <div class="card-body">
          <h6 class="card-title mb-3">
            <i class="fas fa-lightbulb me-2"></i>Recomendaciones
          </h6>
          
          <div class="mb-2">
            <div class="d-flex align-items-start">
              <i class="fas fa-check-circle text-success me-2 mt-1"></i>
              <small>Usa una contraseña única y fuerte</small>
            </div>
          </div>
          
          <div class="mb-2">
            <div class="d-flex align-items-start">
              <i class="fas fa-check-circle text-success me-2 mt-1"></i>
              <small>Activa la autenticación de dos factores</small>
            </div>
          </div>
          
          <div class="mb-2">
            <div class="d-flex align-items-start">
              <i class="fas fa-check-circle text-success me-2 mt-1"></i>
              <small>Revisa tus sesiones activas regularmente</small>
            </div>
          </div>
          
          <div class="mb-2">
            <div class="d-flex align-items-start">
              <i class="fas fa-check-circle text-success me-2 mt-1"></i>
              <small>Mantén tu email verificado</small>
            </div>
          </div>
          
          <div class="mb-2">
            <div class="d-flex align-items-start">
              <i class="fas fa-info-circle text-info me-2 mt-1"></i>
              <small>No compartas tus credenciales con nadie</small>
            </div>
          </div>
        </div>
      </div>

      <!-- Historial de Seguridad -->
      <div class="card bg-light">
        <div class="card-body">
          <h6 class="card-title mb-3">
            <i class="fas fa-history me-2"></i>Historial Reciente
          </h6>
          
          <div class="mb-2">
            <small class="text-muted">Último cambio de contraseña:</small>
            <div>Hace 30 días</div>
          </div>
          
          <div class="mb-2">
            <small class="text-muted">Último inicio de sesión:</small>
            <div>{{ formatDate(user.last_active) }}</div>
          </div>
          
          <div class="mb-2">
            <small class="text-muted">Dispositivo actual:</small>
            <div>{{ currentDeviceInfo }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { showToast } from '@js/utils/toastNotify'

const props = defineProps({
    user: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['show-password-modal'])

// Estado local
const twoFactorEnabled = ref(props.user.is_two_factor_enabled || false)
const activeSessions = ref([
    {
        id: 1,
        device: 'Chrome en Windows',
        location: 'Caracas, Venezuela',
        last_activity: new Date(Date.now() - 2 * 60 * 60 * 1000) // 2 horas atrás
    },
    {
        id: 2,
        device: 'Safari en iPhone',
        location: 'Valencia, Venezuela',
        last_activity: new Date(Date.now() - 24 * 60 * 60 * 1000) // 1 día atrás
    }
])

// Computadas
const currentDeviceInfo = computed(() => {
    return `${navigator.platform} - ${navigator.userAgent.split(' ')[0]}`
})

// Métodos
const handleTwoFactorToggle = () => {
    // Simulación - en una implementación real haría una llamada a la API
    showToast(
        twoFactorEnabled.value ? '2FA activado exitosamente' : '2FA desactivado',
        twoFactorEnabled.value ? 'success' : 'warning'
    )
}

const revokeSession = (sessionId) => {
    if (confirm('¿Estás seguro de que deseas cerrar esta sesión?')) {
        // Eliminar sesión del array
        const index = activeSessions.value.findIndex(s => s.id === sessionId)
        if (index > -1) {
            activeSessions.value.splice(index, 1)
            showToast('Sesión cerrada exitosamente', 'success')
        }
    }
}

const revokeAllSessions = () => {
    if (confirm('¿Estás seguro de que deseas cerrar todas las sesiones excepto la actual?')) {
        activeSessions.value = []
        showToast('Todas las sesiones han sido cerradas', 'success')
    }
}

const downloadUserData = () => {
    // Simulación de descarga de datos
    const userData = {
        name: props.user.name,
        email: props.user.email,
        created_at: props.user.created_at,
        last_active: props.user.last_active
    }
    
    const dataStr = JSON.stringify(userData, null, 2)
    const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr)
    
    const exportFileDefaultName = `user_data_${props.user.id}_${new Date().toISOString().split('T')[0]}.json`
    
    const linkElement = document.createElement('a')
    linkElement.setAttribute('href', dataUri)
    linkElement.setAttribute('download', exportFileDefaultName)
    linkElement.click()
    
    showToast('Datos descargados exitosamente', 'success')
}

const formatDate = (dateString) => {
    if (!dateString) return 'No disponible'
    const date = new Date(dateString)
    return date.toLocaleDateString('es-VE', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}
</script>

<style scoped>
.card {
    border-radius: 12px;
    border: none;
}

.card-header {
    border-radius: 12px 12px 0 0;
    border-bottom: 1px solid #dee2e6;
}

.list-group-item {
    border: none;
    border-bottom: 1px solid #dee2e6;
}

.list-group-item:last-child {
    border-bottom: none;
}

.btn {
    border-radius: 8px;
    font-weight: 500;
}

.form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.badge {
    font-size: 0.75rem;
    font-weight: 500;
}

.text-success {
    color: #28a745 !important;
}

.text-muted {
    color: #6c757d !important;
}

small {
    font-size: 0.875rem;
}
</style>
