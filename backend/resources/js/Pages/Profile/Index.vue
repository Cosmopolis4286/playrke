<template>
  <div class="container-fluid py-4">
    <!-- Header del Perfil -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="position-relative me-3">
                        <img 
                            :src="user.avatar || '/img/fav.ico'" 
                            :alt="user.username"
                            class="rounded-circle border border-3 border-primary"
                            style="width: 80px; height: 80px; object-fit: cover;"
                        >
                        <button 
                            @click="showAvatarUpload = true"
                            class="btn btn-sm btn-primary position-absolute bottom-0 end-0 rounded-circle"
                            style="width: 28px; height: 28px; padding: 0;"
                            title="Cambiar avatar"
                        >
                            <i class="fas fa-camera fa-xs"></i>
                        </button>
                    </div>
                    <div>
                        <h4 class="mb-1">{{ user.name || user.username }}</h4>
                        <p class="text-muted mb-0">@{{ user.username }}</p>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <span v-if="user.is_verified" class="badge bg-success">
                                <i class="fas fa-check-circle me-1"></i>Verificado
                            </span>
                            <span class="badge bg-primary">{{ user.roles?.[0]?.name || 'Usuario' }}</span>
                        </div>
                    </div>
                </div>
                <div class="text-end">
                    <button @click="editMode = !editMode" class="btn btn-outline-primary me-2">
                        <i class="fas fa-edit me-1"></i>
                        {{ editMode ? 'Cancelar' : 'Editar Perfil' }}
                    </button>
                    <button @click="showPasswordModal = true" class="btn btn-outline-warning">
                        <i class="fas fa-lock me-1"></i>
                        Cambiar Contraseña
                    </button>
                </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabs de Información -->
    <div class="row">
      <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0">
                <ul class="nav nav-tabs card-header-tabs" role="tablist">
                    <li class="nav-item">
                        <button 
                            :class="['nav-link', { 'active': activeTab === 'personal-info' }]" 
                            @click="activeTab = 'personal-info'"
                            type="button"
                        >
                            <i class="fas fa-user me-2"></i>Información Personal
                        </button>
                    </li>
                    <li class="nav-item">
                        <button 
                            :class="['nav-link', { 'active': activeTab === 'game-stats' }]" 
                            @click="activeTab = 'game-stats'"
                            type="button"
                        >
                            <i class="fas fa-chart-bar me-2"></i>Estadísticas
                        </button>
                    </li>
                    <li class="nav-item">
                        <button 
                            :class="['nav-link', { 'active': activeTab === 'preferences' }]" 
                            @click="activeTab = 'preferences'"
                            type="button"
                        >
                            <i class="fas fa-cog me-2"></i>Preferencias
                        </button>
                    </li>
                    <li class="nav-item">
                        <button 
                            :class="['nav-link', { 'active': activeTab === 'security' }]" 
                            @click="activeTab = 'security'"
                            type="button"
                        >
                            <i class="fas fa-shield-alt me-2"></i>Seguridad
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <!-- Información Personal -->
                    <div :class="['tab-pane', 'fade', { 'show active': activeTab === 'personal-info' }]">
                        <PersonalInfoTab 
                            :user="user"
                            :edit-mode="editMode"
                            @update-user="updateUser"
                        />
                    </div>
                    
                    <!-- Estadísticas -->
                    <div :class="['tab-pane', 'fade', { 'show active': activeTab === 'game-stats' }]">
                        <GameStatsTab :stats="stats" />
                    </div>
                    
                    <!-- Preferencias -->
                    <div :class="['tab-pane', 'fade', { 'show active': activeTab === 'preferences' }]">
                        <PreferencesTab 
                            :preferences="preferences"
                            @update-preferences="updatePreferences"
                        />
                    </div>
                    
                    <!-- Seguridad -->
                    <div :class="['tab-pane', 'fade', { 'show active': activeTab === 'security' }]">
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
    birth_date: props.user.birth_date,
    country: props.user.country,
    bio: props.user.bio
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
                editMode.value = false
                showToast('Perfil actualizado exitosamente', 'success')
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
        preferencesForm.put('/profile/api/preferences', {
            onSuccess: () => {
                showToast('Preferencias actualizadas exitosamente', 'success')
            },
            onError: (errors) => {
                showToast('Error al actualizar preferencias', 'error')
                console.error('Preferences update errors:', errors)
            }
        })
    } catch (error) {
        showToast('Error al actualizar preferencias', 'error')
        console.error('Preferences update error:', error)
    }
}

const handlePasswordChanged = () => {
    showPasswordModal.value = false
    showToast('Contraseña actualizada exitosamente', 'success')
}

const handleAvatarUpdated = (newAvatar) => {
    showAvatarUpload.value = false
    props.user.avatar = newAvatar
    showToast('Avatar actualizado exitosamente', 'success')
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
    color: #6c757d;
    font-weight: 500;
    padding: 12px 20px;
}

.nav-tabs .nav-link.active {
    color: #0d6efd;
    background-color: transparent;
    border-bottom: 3px solid #0d6efd;
}

.nav-tabs .nav-link:hover {
    color: #0d6efd;
    background-color: rgba(13, 110, 253, 0.05);
}

.btn {
    border-radius: 8px;
    font-weight: 500;
}

.badge {
    font-size: 0.75rem;
    font-weight: 500;
}

.position-relative {
    position: relative;
}

.bottom-0 {
    bottom: 0;
}

.end-0 {
    right: 0;
}

.rounded-circle {
    border-radius: 50%;
}
</style>
