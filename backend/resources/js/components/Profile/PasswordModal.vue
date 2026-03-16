<template>
  <div class="modal-backdrop" @click="$emit('close')">
    <div class="modal-dialog modal-dialog-centered" @click.stop>
      <div class="modal-content">
        <div class="modal-header border-0">
          <h5 class="modal-title">
            <i class="fas fa-lock me-2"></i>Cambiar Contraseña
          </h5>
          <button type="button" class="btn-close" @click="$emit('close')"></button>
        </div>
        
        <div class="modal-body">
          <form @submit.prevent="handleSubmit">
            <!-- Contraseña Actual -->
            <div class="mb-3">
              <label for="current_password" class="form-label">Contraseña Actual</label>
              <div class="input-group">
                <input 
                  :type="showCurrentPassword ? 'text' : 'password'"
                  id="current_password"
                  v-model="form.current_password"
                  class="form-control"
                  :class="{ 'is-invalid': form.errors.current_password }"
                  placeholder="Ingresa tu contraseña actual"
                  required
                >
                <button 
                  type="button" 
                  class="btn btn-outline-secondary"
                  @click="showCurrentPassword = !showCurrentPassword"
                >
                  <i :class="showCurrentPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                </button>
              </div>
              <div v-if="form.errors.current_password" class="invalid-feedback">
                {{ form.errors.current_password }}
              </div>
            </div>

            <!-- Nueva Contraseña -->
            <div class="mb-3">
              <label for="new_password" class="form-label">Nueva Contraseña</label>
              <div class="input-group">
                <input 
                  :type="showNewPassword ? 'text' : 'password'"
                  id="new_password"
                  v-model="form.new_password"
                  class="form-control"
                  :class="{ 'is-invalid': form.errors.new_password }"
                  placeholder="Ingresa tu nueva contraseña"
                  required
                >
                <button 
                  type="button" 
                  class="btn btn-outline-secondary"
                  @click="showNewPassword = !showNewPassword"
                >
                  <i :class="showNewPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                </button>
              </div>
              <div v-if="form.errors.new_password" class="invalid-feedback">
                {{ form.errors.new_password }}
              </div>
              
              <!-- Indicador de fuerza -->
              <div class="mt-2">
                <div class="progress" style="height: 5px;">
                  <div 
                    class="progress-bar" 
                    :class="passwordStrengthClass"
                    :style="{ width: passwordStrength + '%' }"
                  ></div>
                </div>
                <small class="text-muted">{{ passwordStrengthText }}</small>
              </div>
            </div>

            <!-- Confirmar Nueva Contraseña -->
            <div class="mb-3">
              <label for="new_password_confirmation" class="form-label">Confirmar Nueva Contraseña</label>
              <div class="input-group">
                <input 
                  :type="showConfirmPassword ? 'text' : 'password'"
                  id="new_password_confirmation"
                  v-model="form.new_password_confirmation"
                  class="form-control"
                  :class="{ 'is-invalid': form.errors.new_password_confirmation }"
                  placeholder="Confirma tu nueva contraseña"
                  required
                >
                <button 
                  type="button" 
                  class="btn btn-outline-secondary"
                  @click="showConfirmPassword = !showConfirmPassword"
                >
                  <i :class="showConfirmPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                </button>
              </div>
              <div v-if="form.errors.new_password_confirmation" class="invalid-feedback">
                {{ form.errors.new_password_confirmation }}
              </div>
            </div>

            <!-- Requisitos de contraseña -->
            <div class="alert alert-info">
              <h6 class="alert-heading">
                <i class="fas fa-info-circle me-2"></i>Requisitos de Contraseña:
              </h6>
              <ul class="mb-0 small">
                <li :class="{ 'text-success': hasMinLength, 'text-muted': !hasMinLength }">
                  <i :class="hasMinLength ? 'fas fa-check' : 'fas fa-times'"></i>
                  Mínimo 8 caracteres
                </li>
                <li :class="{ 'text-success': hasUppercase, 'text-muted': !hasUppercase }">
                  <i :class="hasUppercase ? 'fas fa-check' : 'fas fa-times'"></i>
                  Al menos una letra mayúscula
                </li>
                <li :class="{ 'text-success': hasLowercase, 'text-muted': !hasLowercase }">
                  <i :class="hasLowercase ? 'fas fa-check' : 'fas fa-times'"></i>
                  Al menos una letra minúscula
                </li>
                <li :class="{ 'text-success': hasNumber, 'text-muted': !hasNumber }">
                  <i :class="hasNumber ? 'fas fa-check' : 'fas fa-times'"></i>
                  Al menos un número
                </li>
                <li :class="{ 'text-success': hasSpecialChar, 'text-muted': !hasSpecialChar }">
                  <i :class="hasSpecialChar ? 'fas fa-check' : 'fas fa-times'"></i>
                  Al menos un carácter especial
                </li>
              </ul>
            </div>

            <!-- Botones -->
            <div class="d-flex justify-content-end gap-2">
              <button type="button" class="btn btn-secondary" @click="$emit('close')">
                <i class="fas fa-times me-2"></i>Cancelar
              </button>
              <button 
                type="submit" 
                class="btn btn-primary" 
                :disabled="form.processing || !isPasswordValid"
              >
                <i class="fas fa-save me-2"></i>
                {{ form.processing ? 'Cambiando...' : 'Cambiar Contraseña' }}
              </button>
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

const emit = defineEmits(['close', 'password-changed'])

// Estado local
const showCurrentPassword = ref(false)
const showNewPassword = ref(false)
const showConfirmPassword = ref(false)

// Formulario
const form = useForm({
    current_password: '',
    new_password: '',
    new_password_confirmation: ''
})

// Computadas para validación de contraseña
const hasMinLength = computed(() => form.new_password.length >= 8)
const hasUppercase = computed(() => /[A-Z]/.test(form.new_password))
const hasLowercase = computed(() => /[a-z]/.test(form.new_password))
const hasNumber = computed(() => /\d/.test(form.new_password))
const hasSpecialChar = computed(() => /[!@#$%^&*(),.?":{}|<>]/.test(form.new_password))

const isPasswordValid = computed(() => {
    return hasMinLength.value && 
           hasUppercase.value && 
           hasLowercase.value && 
           hasNumber.value && 
           hasSpecialChar.value &&
           form.new_password === form.new_password_confirmation
})

const passwordStrength = computed(() => {
    let strength = 0
    if (hasMinLength.value) strength += 20
    if (hasUppercase.value) strength += 20
    if (hasLowercase.value) strength += 20
    if (hasNumber.value) strength += 20
    if (hasSpecialChar.value) strength += 20
    return strength
})

const passwordStrengthClass = computed(() => {
    const strength = passwordStrength.value
    if (strength <= 20) return 'bg-danger'
    if (strength <= 40) return 'bg-warning'
    if (strength <= 60) return 'bg-info'
    if (strength <= 80) return 'bg-primary'
    return 'bg-success'
})

const passwordStrengthText = computed(() => {
    const strength = passwordStrength.value
    if (strength <= 20) return 'Muy débil'
    if (strength <= 40) return 'Débil'
    if (strength <= 60) return 'Regular'
    if (strength <= 80) return 'Fuerte'
    return 'Muy fuerte'
})

// Métodos
const handleSubmit = () => {
    form.post('/profile/api/password', {
        onSuccess: () => {
            showToast('Contraseña actualizada exitosamente', 'success')
            emit('password-changed')
            emit('close')
        },
        onError: (errors) => {
            showToast('Error al cambiar la contraseña', 'error')
            console.error('Password change errors:', errors)
        }
    })
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
    max-width: 500px;
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

.form-control, .btn {
    border-radius: 8px;
}

.form-control:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.progress {
    border-radius: 10px;
    overflow: hidden;
}

.progress-bar {
    transition: width 0.3s ease;
}

.alert {
    border-radius: 8px;
    border: none;
}

.text-success {
    color: #28a745 !important;
}

.text-muted {
    color: #6c757d !important;
}

ul {
    padding-left: 1.2rem;
}

li {
    margin-bottom: 0.25rem;
}
</style>
