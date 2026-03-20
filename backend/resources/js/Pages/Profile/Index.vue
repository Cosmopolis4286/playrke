<template>
  <div class="container-fluid py-4">
    <!-- Header del Perfil -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="card border-0 shadow-lg bg-gradient-primary">
          <div class="card-body p-4">
            <div class="row align-items-center">
              <!-- Avatar y Info Principal -->
              <div class="col-md-3 text-center text-md-start">
                <div class="position-relative d-inline-block">
                  <img 
                    :src="user.avatar || '/img/fav.ico'" 
                    :alt="user.name"
                    class="rounded-circle border-4 border-white shadow-lg"
                    style="width: 120px; height: 120px; object-fit: cover;"
                  >
                  <button 
                    @click="showAvatarUpload = true"
                    class="btn btn-primary btn-sm position-absolute bottom-0 end-0 rounded-circle shadow-lg"
                    style="width: 36px; height: 36px; padding: 0;"
                    title="Cambiar Avatar"
                  >
                    <i class="fas fa-camera"></i>
                  </button>
                </div>
                <div class="mt-3">
                  <h4 class="text-white mb-1">{{ user.name }}</h4>
                  <p class="text-white-50 mb-2">@{{ user.username }}</p>
                  <div class="d-flex justify-content-center justify-content-md-start gap-2">
                    <span v-if="user.is_verified" class="badge bg-success">
                      <i class="fas fa-check-circle me-1"></i>Verificado
                    </span>
                    <span v-for="role in user.roles" :key="role.id" class="badge bg-info">
                      <i class="fas fa-user-tag me-1"></i>{{ role.name }}
                    </span>
                  </div>
                </div>
              </div>
              
              <!-- Estadísticas Rápidas -->
              <div class="col-md-6">
                <div class="row g-3">
                  <div class="col-6 col-md-3">
                    <div class="text-center">
                      <div class="text-white fs-2 fw-bold">{{ stats.games_played || 0 }}</div>
                      <div class="text-white-50 small">Partidas</div>
                    </div>
                  </div>
                  <div class="col-6 col-md-3">
                    <div class="text-center">
                      <div class="text-white fs-2 fw-bold">{{ stats.games_won || 0 }}</div>
                      <div class="text-white-50 small">Victorias</div>
                    </div>
                  </div>
                  <div class="col-6 col-md-3">
                    <div class="text-center">
                      <div class="text-white fs-2 fw-bold">{{ stats.level || 1 }}</div>
                      <div class="text-white-50 small">Nivel</div>
                    </div>
                  </div>
                  <div class="col-6 col-md-3">
                    <div class="text-center">
                      <div class="text-white fs-2 fw-bold">{{ stats.achievements_count || 0 }}</div>
                      <div class="text-white-50 small">Logros</div>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Acciones Rápidas -->
              <div class="col-md-3 text-center text-md-end">
                <div class="d-flex flex-column gap-2">
                  <button 
                    @click="editMode = !editMode"
                    :class="['btn', editMode ? 'btn-success' : 'btn-light']"
                  >
                    <i :class="['fas', editMode ? 'fa-save' : 'fa-edit']"></i>
                    {{ editMode ? 'Guardar Cambios' : 'Editar Perfil' }}
                  </button>
                  <button 
                    @click="showPasswordModal = true"
                    class="btn btn-outline-light"
                  >
                    <i class="fas fa-lock"></i>
                    Cambiar Contraseña
                  </button>
                  <div v-if="user.wallet" class="text-white">
                    <small class="d-block">Saldo Disponible</small>
                    <div class="fs-4 fw-bold">
                      {{ formatCurrency(user.wallet.balance, user.wallet.currency) }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabs de Información -->
    <div class="row">
      <div class="col-12">
        <div class="card border-0 shadow-lg">
            <div class="card-header bg-white border-0 p-0 overflow-hidden">
                <div class="nav-tabs-container">
                    <ul class="nav nav-tabs card-header-tabs border-0 flex-nowrap" role="tablist">
                    <li class="nav-item">
                        <button 
                            :class="['nav-link', 'rounded-0', 'border-0', { 'active': activeTab === 'personal-info' }]" 
                            @click="activeTab = 'personal-info'"
                            type="button"
                        >
                            <i class="fas fa-user me-2"></i>
                            <span class="d-none d-md-inline">Información Personal</span>
                            <span class="d-md-none">Perfil</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button 
                            :class="['nav-link', 'rounded-0', 'border-0', { 'active': activeTab === 'game-stats' }]" 
                            @click="activeTab = 'game-stats'"
                            type="button"
                        >
                            <i class="fas fa-chart-bar me-2"></i>
                            <span class="d-none d-md-inline">Estadísticas</span>
                            <span class="d-md-none">Stats</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button 
                            :class="['nav-link', 'rounded-0', 'border-0', { 'active': activeTab === 'preferences' }]" 
                            @click="activeTab = 'preferences'"
                            type="button"
                        >
                            <i class="fas fa-cog me-2"></i>
                            <span class="d-none d-md-inline">Preferencias</span>
                            <span class="d-md-none">Prefs</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button 
                            :class="['nav-link', 'rounded-0', 'border-0', { 'active': activeTab === 'security' }]" 
                            @click="activeTab = 'security'"
                            type="button"
                        >
                            <i class="fas fa-shield-alt me-2"></i>
                            <span class="d-none d-md-inline">Seguridad</span>
                            <span class="d-md-none">Seg</span>
                        </button>
                    </li>
                </ul>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="tab-content">
                    <!-- Información Personal -->
                    <div :class="['tab-pane', 'fade', { 'show active': activeTab === 'personal-info' }]" style="padding: 2rem;">
                        <PersonalInfoTab 
                            :user="user"
                            :edit-mode="editMode"
                            @update-user="updateUser"
                        />
                    </div>
                    
                    <!-- Estadísticas -->
                    <div :class="['tab-pane', 'fade', { 'show active': activeTab === 'game-stats' }]" style="padding: 2rem;">
                        <GameStatsTab :stats="stats" />
                    </div>
                    
                    <!-- Preferencias -->
                    <div :class="['tab-pane', 'fade', { 'show active': activeTab === 'preferences' }]" style="padding: 2rem;">
                        <PreferencesTab 
                            :preferences="preferences"
                            @update-preferences="updatePreferences"
                        />
                    </div>
                    
                    <!-- Seguridad -->
                    <div :class="['tab-pane', 'fade', { 'show active': activeTab === 'security' }]" style="padding: 2rem;">
                        <SecurityTab 
                            :user="user"
                            @show-password-modal="showPasswordModal = true"
                        />
                    </div>
                </div>
            </div>
        </div>
      </div>
    </div>

    <!-- Modal de Cambiar Contraseña -->
    <PasswordModal 
        v-if="showPasswordModal"
        @close="showPasswordModal = false"
        @password-changed="handlePasswordChanged"
    />

    <!-- Modal de Subir Avatar -->
    <AvatarUploadModal 
        v-if="showAvatarUpload"
        :current-avatar="user.avatar"
        @close="showAvatarUpload = false"
        @avatar-updated="handleAvatarUpdated"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { showToast } from '@js/utils/toastNotify'
import PersonalInfoTab from '../../Components/Profile/PersonalInfoTab.vue'
import GameStatsTab from '../../Components/Profile/GameStatsTab.vue'
import PreferencesTab from '../../Components/Profile/PreferencesTab.vue'
import SecurityTab from '../../Components/Profile/SecurityTab.vue'
import PasswordModal from '../../Components/Profile/PasswordModal.vue'
import AvatarUploadModal from '../../Components/Profile/AvatarUploadModal.vue'

// Props del controlador
const props = defineProps({
    user: {
        type: Object,
        required: true
    },
    stats: {
        type: Object,
        default: () => ({})
    },
    preferences: {
        type: Object,
        default: () => ({})
    }
})

// Estado local
const editMode = ref(false)
const showPasswordModal = ref(false)
const showAvatarUpload = ref(false)
const activeTab = ref('personal-info')

// Formulario para actualizar perfil
const profileForm = useForm({
    name: props.user.name,
    username: props.user.username,
    email: props.user.email,
    phone: props.user.phone,
    birth_date: props.user.birth_date ? new Date(props.user.birth_date).toISOString().split('T')[0] : '',
    country: props.user.country
})

// Formulario para preferencias
const preferencesForm = useForm({
    language: props.preferences.language || 'es',
    timezone: props.preferences.timezone || 'America/Caracas',
    notifications: props.preferences.notifications || {},
    privacy: props.preferences.privacy || {},
    game_settings: props.preferences.game_settings || {}
})

// Métodos
const updateUser = async (userData) => {
    try {
        profileForm.put('/profile/api/profile', {
            onSuccess: () => {
                showToast('Perfil actualizado exitosamente', 'success')
                editMode.value = false
            },
            onError: (errors) => {
                showToast('Error al actualizar el perfil', 'error')
                console.error('Profile update errors:', errors)
            }
        })
    } catch (error) {
        showToast('Error al actualizar el perfil', 'error')
        console.error('Profile update error:', error)
    }
}

const updatePreferences = async (newPreferences) => {
    try {
        await fetch('/profile/api/preferences', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(newPreferences)
        })
        .then(response => response.json())
        .then(response => {
            if (response.success) {
                showToast('Preferencias actualizadas exitosamente', 'success')
            } else {
                showToast('Error al actualizar preferencias', 'error')
            }
        })
        .catch(error => {
            showToast('Error al actualizar preferencias', 'error')
            console.error('Preferences update error:', error)
        })
    } catch (error) {
        showToast('Error al actualizar preferencias', 'error')
        console.error('Preferences update error:', error)
    }
}

const handlePasswordChanged = (success) => {
    if (success) {
        showToast('Contraseña cambiada exitosamente', 'success')
        showPasswordModal.value = false
    }
}

const handleAvatarUpdated = (newAvatar) => {
    if (newAvatar !== undefined) {
        showToast('Avatar actualizado exitosamente', 'success')
        props.user.avatar = newAvatar
        showAvatarUpload.value = false
    }
}

const formatCurrency = (amount, currency) => {
    if (!amount) return '$0.00'
    
    // Asegurar que currency sea un string
    const currencyCode = typeof currency === 'string' ? currency : 'USD'
    
    return new Intl.NumberFormat('es-VE', {
        style: 'currency',
        currency: currencyCode
    }).format(amount)
}

// Ciclo de vida
onMounted(() => {
    console.log('Profile page mounted with user:', props.user)
})
</script>

<style scoped>
.card {
    border-radius: 12px;
}

.nav-tabs .nav-link {
    border: none;
    background: #f8f9fa;
    color: #6c757d;
}

.nav-tabs .nav-link:hover {
    background: #e9ecef;
    color: #495057;
    transform: translateY(-1px);
}

.nav-tabs .nav-link.active {
    background: white;
    color: #667eea;
    border-bottom: 3px solid #667eea;
    font-weight: 600;
}

/* Avatar mejorado */
.rounded-circle {
    transition: all 0.3s ease;
}

.rounded-circle:hover {
    transform: scale(1.05);
    box-shadow: 0 8px 25px rgba(0,0,0,0.2) !important;
}

/* Botones mejorados */
.btn {
    border-radius: 12px;
    font-weight: 500;
    padding: 10px 20px;
    transition: all 0.3s ease;
    border: none;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.btn-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
}

.btn-outline-light:hover {
    background: rgba(255,255,255,0.2);
    border-color: rgba(255,255,255,0.5);
}

/* Estadísticas */
.text-white.fs-2 {
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    font-weight: 700;
}

.text-white-50 {
    text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
}

/* Badges mejorados */
.badge {
    font-size: 0.75rem;
    font-weight: 500;
    padding: 6px 12px;
    border-radius: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

/* Animaciones */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.tab-pane {
    animation: fadeInUp 0.5s ease;
}

/* Responsive */
@media (max-width: 768px) {
    .container-fluid {
        padding-left: 15px;
        padding-right: 15px;
    }
    
    .nav-tabs .nav-link {
        padding: 8px 12px;
        font-size: 0.875rem;
    }
    
    .btn {
        padding: 8px 16px;
        font-size: 0.875rem;
    }
}

/* Efectos hover para cards */
.card-body {
    position: relative;
    overflow: hidden;
}

.card-body::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
    transition: left 0.5s;
}

.card:hover .card-body::before {
    left: 100%;
}

/* Loading states */
.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none !important;
}

.btn:disabled:hover {
    transform: none !important;
    box-shadow: none !important;
}
</style>
