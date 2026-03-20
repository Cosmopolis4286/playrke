<template>
  <div class="row">
    <div class="col-md-8">
      <form @submit.prevent="handleSubmit">
        <div class="row">
          <!-- Nombre -->
          <div class="col-md-6 mb-3">
            <label for="name" class="form-label">Nombre Completo</label>
            <input 
              type="text" 
              id="name"
              v-model="form.name"
              class="form-control"
              :class="{ 'is-invalid': form.errors.name }"
              :disabled="!editMode"
              placeholder="Tu nombre completo"
            >
            <div v-if="form.errors.name" class="invalid-feedback">
              {{ form.errors.name }}
            </div>
          </div>

          <!-- Username -->
          <div class="col-md-6 mb-3">
            <label for="username" class="form-label">Nombre de Usuario</label>
            <div class="input-group">
              <span class="input-group-text">@</span>
              <input 
                type="text" 
                id="username"
                v-model="form.username"
                class="form-control"
                :class="{ 'is-invalid': form.errors.username }"
                :disabled="!editMode"
                placeholder="username"
              >
            </div>
            <div v-if="form.errors.username" class="invalid-feedback">
              {{ form.errors.username }}
            </div>
          </div>
        </div>

        <div class="row">
          <!-- Email -->
          <div class="col-md-6 mb-3">
            <label for="email" class="form-label">Correo Electrónico</label>
            <input 
              type="email" 
              id="email"
              v-model="form.email"
              class="form-control"
              :class="{ 'is-invalid': form.errors.email }"
              :disabled="!editMode"
              placeholder="correo@ejemplo.com"
            >
            <div v-if="form.errors.email" class="invalid-feedback">
              {{ form.errors.email }}
            </div>
          </div>

          <!-- Teléfono -->
          <div class="col-md-6 mb-3">
            <label for="phone" class="form-label">Teléfono</label>
            <input 
              type="tel" 
              id="phone"
              v-model="form.phone"
              class="form-control"
              :class="{ 'is-invalid': form.errors.phone }"
              :disabled="!editMode"
              placeholder="+58 412 1234567"
            >
            <div v-if="form.errors.phone" class="invalid-feedback">
              {{ form.errors.phone }}
            </div>
          </div>
        </div>

        <div class="row">
          <!-- Fecha de Nacimiento -->
          <div class="col-md-6 mb-3">
            <label for="birth_date" class="form-label">Fecha de Nacimiento</label>
            <input 
              type="date" 
              id="birth_date"
              v-model="form.birth_date"
              class="form-control"
              :class="{ 'is-invalid': form.errors.birth_date }"
              :disabled="!editMode"
            >
            <div v-if="form.errors.birth_date" class="invalid-feedback">
              {{ form.errors.birth_date }}
            </div>
          </div>

          <!-- País -->
          <div class="col-md-6 mb-3">
            <label for="country" class="form-label">País</label>
            <select 
              id="country"
              v-model="form.country"
              class="form-select"
              :class="{ 'is-invalid': form.errors.country }"
              :disabled="!editMode"
            >
              <option value="">Selecciona tu país</option>
              <option value="VE">Venezuela</option>
              <option value="CO">Colombia</option>
              <option value="BR">Brasil</option>
              <option value="AR">Argentina</option>
              <option value="MX">México</option>
              <option value="PE">Perú</option>
              <option value="CL">Chile</option>
              <option value="EC">Ecuador</option>
              <option value="BO">Bolivia</option>
              <option value="PY">Paraguay</option>
              <option value="UY">Uruguay</option>
            </select>
            <div v-if="form.errors.country" class="invalid-feedback">
              {{ form.errors.country }}
            </div>
          </div>
        </div>


        <!-- Botones de acción -->
        <div v-if="editMode" class="d-flex gap-2">
          <button type="submit" class="btn btn-primary" :disabled="form.processing">
            <i class="fas fa-save me-2"></i>
            {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
          </button>
          <button type="button" @click="$emit('cancel')" class="btn btn-outline-secondary">
            <i class="fas fa-times me-2"></i>
            Cancelar
          </button>
        </div>
      </form>
    </div>

    <div class="col-md-4">
      <!-- Información de Cuenta -->
      <div class="card bg-light">
        <div class="card-body">
          <h6 class="card-title mb-3">
            <i class="fas fa-info-circle me-2"></i>Información de Cuenta
          </h6>
          
          <div class="mb-2">
            <small class="text-muted">ID de Usuario:</small>
            <div class="font-monospace">{{ user.id }}</div>
          </div>
          
          <div class="mb-2">
            <small class="text-muted">Miembro desde:</small>
            <div>{{ formatDate(user.created_at) }}</div>
          </div>
          
          <div class="mb-2">
            <small class="text-muted">Última actividad:</small>
            <div>{{ formatDate(user.last_active) }}</div>
          </div>
          
          <div class="mb-2">
            <small class="text-muted">Estado de verificación:</small>
            <div>
              <span v-if="user.is_verified" class="badge bg-success">
                <i class="fas fa-check-circle me-1"></i>Verificado
              </span>
              <span v-else class="badge bg-warning">
                <i class="fas fa-exclamation-triangle me-1"></i>No Verificado
              </span>
            </div>
          </div>

          <div class="mb-2" v-if="user.wallet">
            <small class="text-muted">Saldo de Billetera:</small>
            <div class="fw-bold text-success">
              {{ formatCurrency(user.wallet.balance, user.wallet.currency) }}
            </div>
          </div>
        </div>
      </div>

      <!-- Roles -->
      <div class="card bg-light mt-3">
        <div class="card-body">
          <h6 class="card-title mb-3">
            <i class="fas fa-user-tag me-2"></i>Roles y Permisos
          </h6>
          
          <div v-for="role in user.roles" :key="role.id" class="mb-2">
            <span class="badge bg-primary me-1">{{ role.name }}</span>
            <small class="text-muted">{{ role.description || 'Usuario del sistema' }}</small>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    user: {
        type: Object,
        required: true
    },
    editMode: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['update-user', 'cancel'])

const form = useForm({
    name: props.user.name,
    username: props.user.username,
    email: props.user.email,
    phone: props.user.phone,
    birth_date: props.user.birth_date,
    country: props.user.country || 'VE'
})

const handleSubmit = () => {
    emit('update-user', form.data())
}

const formatDate = (dateString) => {
    if (!dateString) return 'No disponible'
    return new Date(dateString).toLocaleDateString('es-VE', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
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
</script>

<style scoped>
.form-control, .form-select {
    border-radius: 8px;
    border: 1px solid #dee2e6;
}

.form-control:focus, .form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.card {
    border-radius: 12px;
    border: none;
}

.badge {
    font-size: 0.75rem;
    font-weight: 500;
}
</style>
