<template>
  <div class="row">
    <!-- Configuración General -->
    <div class="col-md-6">
      <div class="card">
        <div class="card-header bg-light">
          <h6 class="mb-0">
            <i class="fas fa-cog me-2"></i>Configuración General
          </h6>
        </div>
        <div class="card-body">
          <form @submit.prevent="handleGeneralSubmit">
            <!-- Idioma -->
            <div class="mb-3">
              <label for="language" class="form-label">Idioma</label>
              <select 
                id="language"
                v-model="form.language"
                class="form-select"
                :class="{ 'is-invalid': form.errors.language }"
              >
                <option value="es">Español</option>
                <option value="en">English</option>
                <option value="pt">Português</option>
                <option value="fr">Français</option>
              </select>
              <div v-if="form.errors.language" class="invalid-feedback">
                {{ form.errors.language }}
              </div>
            </div>

            <!-- Zona Horaria -->
            <div class="mb-3">
              <label for="timezone" class="form-label">Zona Horaria</label>
              <select 
                id="timezone"
                v-model="form.timezone"
                class="form-select"
                :class="{ 'is-invalid': form.errors.timezone }"
              >
                <option value="America/Caracas">Caracas (GMT-4)</option>
                <option value="America/Bogota">Bogotá (GMT-5)</option>
                <option value="America/Sao_Paulo">São Paulo (GMT-3)</option>
                <option value="America/Buenos_Aires">Buenos Aires (GMT-3)</option>
                <option value="America/Mexico_City">Ciudad de México (GMT-6)</option>
                <option value="America/New_York">Nueva York (GMT-5)</option>
                <option value="America/Los_Angeles">Los Ángeles (GMT-8)</option>
                <option value="Europe/Madrid">Madrid (GMT+1)</option>
                <option value="Europe/Paris">París (GMT+1)</option>
                <option value="Europe/London">Londres (GMT+0)</option>
              </select>
              <div v-if="form.errors.timezone" class="invalid-feedback">
                {{ form.errors.timezone }}
              </div>
            </div>

            <button type="submit" class="btn btn-primary" :disabled="form.processing">
              <i class="fas fa-save me-2"></i>
              {{ form.processing ? 'Guardando...' : 'Guardar Configuración' }}
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- Notificaciones -->
    <div class="col-md-6">
      <div class="card">
        <div class="card-header bg-light">
          <h6 class="mb-0">
            <i class="fas fa-bell me-2"></i>Notificaciones
          </h6>
        </div>
        <div class="card-body">
          <form @submit.prevent="handleNotificationsSubmit">
            <!-- Email -->
            <div class="mb-3">
              <div class="form-check form-switch">
                <input 
                  class="form-check-input" 
                  type="checkbox" 
                  id="email_notifications"
                  v-model="form.notifications.email"
                >
                <label class="form-check-label" for="email_notifications">
                  <i class="fas fa-envelope me-2"></i>Notificaciones por Email
                </label>
                <small class="form-text text-muted d-block">
                  Recibe actualizaciones importantes en tu correo
                </small>
              </div>
            </div>

            <!-- Push -->
            <div class="mb-3">
              <div class="form-check form-switch">
                <input 
                  class="form-check-input" 
                  type="checkbox" 
                  id="push_notifications"
                  v-model="form.notifications.push"
                >
                <label class="form-check-label" for="push_notifications">
                  <i class="fas fa-mobile-alt me-2"></i>Notificaciones Push
                </label>
                <small class="form-text text-muted d-block">
                  Notificaciones en tiempo real en tu dispositivo
                </small>
              </div>
            </div>

            <!-- SMS -->
            <div class="mb-3">
              <div class="form-check form-switch">
                <input 
                  class="form-check-input" 
                  type="checkbox" 
                  id="sms_notifications"
                  v-model="form.notifications.sms"
                >
                <label class="form-check-label" for="sms_notifications">
                  <i class="fas fa-sms me-2"></i>Notificaciones SMS
                </label>
                <small class="form-text text-muted d-block">
                  Alertas importantes por mensaje de texto
                </small>
              </div>
            </div>

            <button type="submit" class="btn btn-primary" :disabled="form.processing">
              <i class="fas fa-save me-2"></i>
              {{ form.processing ? 'Guardando...' : 'Guardar Preferencias' }}
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- Privacidad -->
    <div class="col-md-6">
      <div class="card">
        <div class="card-header bg-light">
          <h6 class="mb-0">
            <i class="fas fa-shield-alt me-2"></i>Privacidad
          </h6>
        </div>
        <div class="card-body">
          <form @submit.prevent="handlePrivacySubmit">
            <!-- Perfil Visible -->
            <div class="mb-3">
              <div class="form-check form-switch">
                <input 
                  class="form-check-input" 
                  type="checkbox" 
                  id="profile_visible"
                  v-model="form.privacy.profile_visible"
                >
                <label class="form-check-label" for="profile_visible">
                  <i class="fas fa-eye me-2"></i>Perfil Público
                </label>
                <small class="form-text text-muted d-block">
                  Permite que otros usuarios vean tu perfil
                </small>
              </div>
            </div>

            <!-- Mostrar Email -->
            <div class="mb-3">
              <div class="form-check form-switch">
                <input 
                  class="form-check-input" 
                  type="checkbox" 
                  id="show_email"
                  v-model="form.privacy.show_email"
                >
                <label class="form-check-label" for="show_email">
                  <i class="fas fa-envelope-open me-2"></i>Mostrar Email
                </label>
                <small class="form-text text-muted d-block">
                  Muestra tu correo en tu perfil público
                </small>
              </div>
            </div>

            <!-- Mostrar Teléfono -->
            <div class="mb-3">
              <div class="form-check form-switch">
                <input 
                  class="form-check-input" 
                  type="checkbox" 
                  id="show_phone"
                  v-model="form.privacy.show_phone"
                >
                <label class="form-check-label" for="show_phone">
                  <i class="fas fa-phone me-2"></i>Mostrar Teléfono
                </label>
                <small class="form-text text-muted d-block">
                  Muestra tu número de teléfono en tu perfil
                </small>
              </div>
            </div>

            <button type="submit" class="btn btn-primary" :disabled="form.processing">
              <i class="fas fa-save me-2"></i>
              {{ form.processing ? 'Guardando...' : 'Guardar Privacidad' }}
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- Configuración de Juego -->
    <div class="col-md-6">
      <div class="card">
        <div class="card-header bg-light">
          <h6 class="mb-0">
            <i class="fas fa-gamepad me-2"></i>Configuración de Juego
          </h6>
        </div>
        <div class="card-body">
          <form @submit.prevent="handleGameSettingsSubmit">
            <!-- Sonido -->
            <div class="mb-3">
              <div class="form-check form-switch">
                <input 
                  class="form-check-input" 
                  type="checkbox" 
                  id="sound_enabled"
                  v-model="form.game_settings.sound_enabled"
                >
                <label class="form-check-label" for="sound_enabled">
                  <i class="fas fa-volume-up me-2"></i>Activar Sonido
                </label>
                <small class="form-text text-muted d-block">
                  Efectos de sonido durante el juego
                </small>
              </div>
            </div>

            <!-- Música -->
            <div class="mb-3">
              <div class="form-check form-switch">
                <input 
                  class="form-check-input" 
                  type="checkbox" 
                  id="music_enabled"
                  v-model="form.game_settings.music_enabled"
                >
                <label class="form-check-label" for="music_enabled">
                  <i class="fas fa-music me-2"></i>Activar Música
                </label>
                <small class="form-text text-muted d-block">
                  Música de fondo durante el juego
                </small>
              </div>
            </div>

            <!-- Auto Dauber -->
            <div class="mb-3">
              <div class="form-check form-switch">
                <input 
                  class="form-check-input" 
                  type="checkbox" 
                  id="auto_dauber"
                  v-model="form.game_settings.auto_dauber"
                >
                <label class="form-check-label" for="auto_dauber">
                  <i class="fas fa-magic me-2"></i>Auto Dauber
                </label>
                <small class="form-text text-muted d-block">
                  Marca automáticamente los números en tus cartones
                </small>
              </div>
            </div>

            <!-- Animaciones -->
            <div class="mb-3">
              <div class="form-check form-switch">
                <input 
                  class="form-check-input" 
                  type="checkbox" 
                  id="card_animation"
                  v-model="form.game_settings.card_animation"
                >
                <label class="form-check-label" for="card_animation">
                  <i class="fas fa-film me-2"></i>Animaciones de Cartón
                </label>
                <small class="form-text text-muted d-block">
                  Efectos visuales en los cartones de bingo
                </small>
              </div>
            </div>

            <button type="submit" class="btn btn-primary" :disabled="form.processing">
              <i class="fas fa-save me-2"></i>
              {{ form.processing ? 'Guardando...' : 'Guardar Configuración' }}
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
    preferences: {
        type: Object,
        default: () => ({})
    }
})

const emit = defineEmits(['update-preferences'])

const form = useForm({
    language: props.preferences.language || 'es',
    timezone: props.preferences.timezone || 'America/Caracas',
    notifications: {
        email: props.preferences.notifications?.email ?? true,
        push: props.preferences.notifications?.push ?? true,
        sms: props.preferences.notifications?.sms ?? false,
    },
    privacy: {
        profile_visible: props.preferences.privacy?.profile_visible ?? true,
        show_email: props.preferences.privacy?.show_email ?? false,
        show_phone: props.preferences.privacy?.show_phone ?? false,
    },
    game_settings: {
        sound_enabled: props.preferences.game_settings?.sound_enabled ?? true,
        music_enabled: props.preferences.game_settings?.music_enabled ?? true,
        auto_dauber: props.preferences.game_settings?.auto_dauber ?? false,
        card_animation: props.preferences.game_settings?.card_animation ?? true,
    }
})

const handleGeneralSubmit = () => {
    emit('update-preferences', {
        language: form.language,
        timezone: form.timezone
    })
}

const handleNotificationsSubmit = () => {
    emit('update-preferences', {
        notifications: form.notifications
    })
}

const handlePrivacySubmit = () => {
    emit('update-preferences', {
        privacy: form.privacy
    })
}

const handleGameSettingsSubmit = () => {
    emit('update-preferences', {
        game_settings: form.game_settings
    })
}
</script>

<style scoped>
.card {
    border-radius: 12px;
    border: none;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.card-header {
    border-radius: 12px 12px 0 0;
    border-bottom: 1px solid #dee2e6;
}

.form-control, .form-select {
    border-radius: 8px;
    border: 1px solid #dee2e6;
}

.form-control:focus, .form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.form-check-input:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.btn {
    border-radius: 8px;
    font-weight: 500;
}

.form-text {
    font-size: 0.875rem;
}

.form-check {
    padding-left: 2.5rem;
}

.form-check-label {
    font-weight: 500;
    margin-bottom: 0.25rem;
}
</style>
