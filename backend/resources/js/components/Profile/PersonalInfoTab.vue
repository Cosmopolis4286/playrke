<template>
  <div class="row">
    <div class="col-md-8">
      <form @submit.prevent="handleSubmit">
        <div class="row">
          <!-- Nombre -->
          <FormInput
            id="name"
            name="name"
            label="Nombre Completo"
            v-model="form.name"
            :error="form.errors.name"
            :disabled="!editMode"
            placeholder="Tu nombre completo"
            col="col-md-6"
          />

          <!-- Username -->
          <FormInput
            id="username"
            name="username"
            label="Nombre de Usuario"
            v-model="form.username"
            :error="form.errors.username"
            :disabled="!editMode"
            placeholder="username"
            col="col-md-6"
          />
        </div>

        <div class="row">
          <!-- Email -->
          <FormInput
            id="email"
            name="email"
            label="Correo Electrónico"
            type="email"
            v-model="form.email"
            :error="form.errors.email"
            :disabled="!editMode"
            placeholder="correo@ejemplo.com"
            col="col-md-6"
          />

          <!-- Teléfono -->
          <FormInput
            id="phone"
            name="phone"
            label="Teléfono"
            type="tel"
            v-model="form.phone"
            :error="form.errors.phone"
            :disabled="!editMode"
            placeholder="+58 412 1234567"
            col="col-md-6"
          />
        </div>

        <div class="row">
          <!-- Fecha de Nacimiento -->
          <FormInput
            id="birth_date"
            name="birth_date"
            label="Fecha de Nacimiento"
            type="date"
            v-model="form.birth_date"
            :error="form.errors.birth_date"
            :disabled="!editMode"
            col="col-md-6"
          />

          <!-- País -->
          <FormSelect
            id="country"
            name="country"
            label="País"
            v-model="form.country"
            :options="countryOptions"
            :error="form.errors.country"
            :disabled="!editMode"
            placeholder="Selecciona tu país"
            col="col-md-6"
          />
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
import FormInput from '../Common/Form/FormInput.vue'
import FormSelect from '../Common/Form/FormSelect.vue'

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

// Opciones de países para el FormSelect
const countryOptions = [
    { value: 'VE', label: 'Venezuela' },
    { value: 'CO', label: 'Colombia' },
    { value: 'BR', label: 'Brasil' },
    { value: 'AR', label: 'Argentina' },
    { value: 'MX', label: 'México' },
    { value: 'PE', label: 'Perú' },
    { value: 'CL', label: 'Chile' },
    { value: 'EC', label: 'Ecuador' },
    { value: 'BO', label: 'Bolivia' },
    { value: 'PY', label: 'Paraguay' },
    { value: 'UY', label: 'Uruguay' }
]

const form = useForm({
    name: props.user.name,
    username: props.user.username,
    email: props.user.email,
    phone: props.user.phone,
    birth_date: props.user.birth_date ? new Date(props.user.birth_date).toISOString().split('T')[0] : '',
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
