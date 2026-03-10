<script setup lang="ts">
  import { ref, computed, watch } from 'vue'

  import type { Currency } from '@js/types/currency'

  interface Props {
    modelValue: Partial<Currency>
    currencies: Currency[]
  }

  interface Emits {
    (e: 'update:modelValue', value: Partial<Currency>): void
  }

  const props = defineProps<Props>()
  const emit = defineEmits<Emits>()

  const currency = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
  })

  const errors = ref<Record<string, string[]>>({})

  interface ValidationRule {
    required?: boolean
    minLength?: number
    maxLength?: number
    pattern?: RegExp
    min?: number
    max?: number
  }

  const validationRules: Record<string, ValidationRule> = {
    name: { required: true, minLength: 2, maxLength: 50 },
    description: { required: false, maxLength: 255 },
    iso_code: { required: true, minLength: 3, maxLength: 10, pattern: /^[A-Z0-9]{3,10}$/ },
    symbol: { required: false, maxLength: 12 },
    type: { required: true },
    country_id: { required: false },
    decimal_places: { required: true, min: 0, max: 10 }
  }

  const currencyTypes = [
    { value: 'FIAT', label: 'Fiat' },
    { value: 'CRYPTO', label: 'Criptomoneda' },
    { value: 'TOKEN', label: 'Token' }
  ]

  // Simulated countries data - in real app this would come from props or API
  const countries = [
    { id: '1', name: 'Venezuela', iso_code: 'VEN' },
    { id: '2', name: 'Estados Unidos', iso_code: 'USA' },
    { id: '3', name: 'Colombia', iso_code: 'COL' },
    { id: '4', name: 'Argentina', iso_code: 'ARG' },
    { id: '5', name: 'España', iso_code: 'ESP' },
    { id: '6', name: 'México', iso_code: 'MEX' }
  ]

  const isValid = computed(() => {
    const requiredFields = ['name', 'iso_code', 'type', 'decimal_places'] as const
    
    for (const field of requiredFields) {
      if (!currency.value[field] || currency.value[field] === '') {
        return false
      }
    }

    // Validate ISO code format
    if (currency.value.iso_code && !/^[A-Z0-9]{3,10}$/.test(currency.value.iso_code)) {
      return false
    }

    // Validate decimal_places is non-negative
    if (typeof currency.value.decimal_places === 'string' && Number(currency.value.decimal_places) < 0) {
      return false
    }

    return true
  })

  function validateField(field: string, value: any): string[] {
    const fieldErrors: string[] = []
    const rules = validationRules[field]

    if (!rules) return fieldErrors

    if (rules.required && (!value || value === '')) {
      fieldErrors.push('Este campo es requerido')
    }

    if (rules.minLength && value && value.length < rules.minLength) {
      fieldErrors.push(`Debe tener al menos ${rules.minLength} caracteres`)
    }

    if (rules.maxLength && value && value.length > rules.maxLength) {
      fieldErrors.push(`No puede exceder ${rules.maxLength} caracteres`)
    }

    if (rules.pattern && value && !rules.pattern.test(value)) {
      fieldErrors.push('Formato inválido')
    }

    if (rules.min && value && value < rules.min) {
      fieldErrors.push(`Debe ser mayor que ${rules.min - 1}`)
    }

    return fieldErrors
  }

  function validateFieldRealtime(field: string) {
    const value = currency.value[field as keyof Partial<Currency>]
    const fieldErrors = validateField(field, value)
    
    if (fieldErrors.length > 0) {
      errors.value[field] = fieldErrors
    } else {
      delete errors.value[field]
    }
  }

  function updateField(field: string, value: any) {
    if (field === 'decimal_places' && typeof value === 'string') {
      value = Number(value)
    }
    if (field === 'country_id' && value === '') {
      value = null
    }
    currency.value = { ...currency.value, [field]: value }
    validateFieldRealtime(field)
  }

  function formatIsoCode(value: string) {
    return value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 10)
  }

  function handleIsoCodeInput(event: Event) {
    const target = event.target as HTMLInputElement
    const formatted = formatIsoCode(target.value)
    target.value = formatted
    updateField('iso_code', formatted)
  }

  // Watch for external changes to clear errors
  watch(() => currency.value, (newValue) => {
    if (newValue) {
      Object.keys(errors.value).forEach(field => {
        validateFieldRealtime(field)
      })
    }
  }, { deep: true })

  // Initialize validation on mount
  watch(() => props.modelValue, (newValue) => {
    if (newValue) {
      Object.keys(validationRules).forEach(field => {
        validateFieldRealtime(field)
      })
    }
  }, { immediate: true })
</script>

<template>
  <div class="row g-3">
    <!-- Name -->
    <div class="col-md-6">
      <label for="name" class="form-label">
        Nombre <span class="text-danger">*</span>
      </label>
      <input
        id="name"
        type="text"
        class="form-control"
        :class="{ 'is-invalid': errors.name }"
        :value="currency.name"
        @input="updateField('name', ($event.target as HTMLInputElement).value)"
        placeholder="Ej: Dólar Americano"
        maxlength="50"
      />
      <div v-if="errors.name" class="invalid-feedback">
        <span v-for="error in errors.name" :key="error">{{ error }}</span>
      </div>
    </div>

    <!-- ISO Code -->
    <div class="col-md-6">
      <label for="iso_code" class="form-label">
        Código ISO <span class="text-danger">*</span>
      </label>
      <input
        id="iso_code"
        type="text"
        class="form-control text-uppercase"
        :class="{ 'is-invalid': errors.iso_code }"
        :value="currency.iso_code"
        @input="handleIsoCodeInput"
        placeholder="Ej: USD"
        maxlength="10"
      />
      <div class="form-text">Código de 3-10 caracteres alfanuméricos</div>
      <div v-if="errors.iso_code" class="invalid-feedback">
        <span v-for="error in errors.iso_code" :key="error">{{ error }}</span>
      </div>
    </div>

    <!-- Description -->
    <div class="col-12">
      <label for="description" class="form-label">
        Descripción
      </label>
      <textarea
        id="description"
        class="form-control"
        :class="{ 'is-invalid': errors.description }"
        :value="currency.description || ''"
        @input="updateField('description', ($event.target as HTMLTextAreaElement).value)"
        placeholder="Descripción detallada de la moneda"
        rows="3"
        maxlength="255"
      ></textarea>
      <div v-if="errors.description" class="invalid-feedback">
        <span v-for="error in errors.description" :key="error">{{ error }}</span>
      </div>
    </div>

    <!-- Symbol -->
    <div class="col-md-3">
      <label for="symbol" class="form-label">
        Símbolo
      </label>
      <input
        id="symbol"
        type="text"
        class="form-control"
        :class="{ 'is-invalid': errors.symbol }"
        :value="currency.symbol || ''"
        @input="updateField('symbol', ($event.target as HTMLInputElement).value)"
        placeholder="Ej: $"
        maxlength="12"
      />
      <div v-if="errors.symbol" class="invalid-feedback">
        <span v-for="error in errors.symbol" :key="error">{{ error }}</span>
      </div>
    </div>

    <!-- Type -->
    <div class="col-md-3">
      <label for="type" class="form-label">
        Tipo <span class="text-danger">*</span>
      </label>
      <select
        id="type"
        class="form-select"
        :class="{ 'is-invalid': errors.type }"
        :value="currency.type"
        @change="updateField('type', ($event.target as HTMLSelectElement).value)"
      >
        <option value="">Seleccionar tipo</option>
        <option
          v-for="type in currencyTypes"
          :key="type.value"
          :value="type.value"
        >
          {{ type.label }}
        </option>
      </select>
      <div v-if="errors.type" class="invalid-feedback">
        <span v-for="error in errors.type" :key="error">{{ error }}</span>
      </div>
    </div>

    <!-- Decimal Places -->
    <div class="col-md-3">
      <label for="decimal_places" class="form-label">
        Decimales <span class="text-danger">*</span>
      </label>
      <input
        id="decimal_places"
        type="number"
        class="form-control"
        :class="{ 'is-invalid': errors.decimal_places }"
        :value="currency.decimal_places"
        @input="updateField('decimal_places', ($event.target as HTMLInputElement).value)"
        min="0"
        max="10"
      />
      <div class="form-text">Cantidad de decimales (0-10)</div>
      <div v-if="errors.decimal_places" class="invalid-feedback">
        <span v-for="error in errors.decimal_places" :key="error">{{ error }}</span>
      </div>
    </div>

    <!-- Country -->
    <div class="col-md-3">
      <label for="country_id" class="form-label">
        País
      </label>
      <select
        id="country_id"
        class="form-select"
        :class="{ 'is-invalid': errors.country_id }"
        :value="currency.country_id || ''"
        @change="updateField('country_id', ($event.target as HTMLSelectElement).value)"
      >
        <option value="">Seleccionar país</option>
        <option
          v-for="country in countries"
          :key="country.id"
          :value="country.id"
        >
          {{ country.name }}
        </option>
      </select>
      <div v-if="errors.country_id" class="invalid-feedback">
        <span v-for="error in errors.country_id" :key="error">{{ error }}</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.form-label {
  font-weight: 500;
}

.text-uppercase {
  text-transform: uppercase;
}

.invalid-feedback span {
  display: block;
}
</style>
