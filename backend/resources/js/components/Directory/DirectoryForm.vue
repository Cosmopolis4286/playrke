<script setup lang="ts">
  import { computed } from 'vue'

  import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'
  import FormInput from '@/js/Components/Common/Form/FormInput.vue'

  defineOptions({ name: 'DirectoryForm' })

  import { useDirectoryForm } from '@/js/composables/directory/useDirectoryForm'

  import type { DirectoryVEN } from '@/js/types/directory/directory-ven'

  const PIX_KEY_TYPES = [
    { id: 'CPF',    name: 'CPF' },
    { id: 'CNPJ',   name: 'CNPJ' },
    { id: 'EMAIL',  name: 'E-mail' },
    { id: 'PHONE',  name: 'Teléfono' },
    { id: 'RANDOM', name: 'Clave Aleatoria' },
  ]

  /**
   * Props
   */
  const props = defineProps<{
    modelValue: Partial<DirectoryVEN> & { country_code?: string; holder_name?: string; pix_key_type?: string; pix_key_value?: string }
    banks?: any[]
    documentTypes?: Array<{ id: string; code: string; description: string }>
    countryCode?: string
  }>()

  /**
   * Emits
   */
  const emit = defineEmits<{
    (e: 'submit'): void
    (e: 'update:modelValue', value: any): void
  }>()

  /**
   * Composable de formulario
   */
  const { form, errors, isEditMode, isValid, pixFieldMeta, validate, reset } = useDirectoryForm(props.modelValue, emit)

  /* =======================
   * Computed
   * ======================= */

  const isVE = computed(() => !props.countryCode || props.countryCode === 'VE')
  const isBR = computed(() => props.countryCode === 'BR')

  const banksOptions = computed(() => props.banks ?? [])

  const documentTypesOptions = computed(() => props.documentTypes ?? [])

  /* =======================
   * Methods
   * ======================= */

  const submit = (): boolean => {
    validate()
    if (!isValid.value) return false

    emit('submit')
    return true
  }

  defineExpose({
    submit,
    isValid,
    reset
  })
</script>
<template>
  <form @submit.prevent="submit" novalidate>
    <div class="row">

      <!-- ============================================================ -->
      <!-- FORMULARIO VENEZUELA (Pago Móvil)                            -->
      <!-- ============================================================ -->
      <template v-if="isVE">
        <!-- Nombre del Titular -->
        <FormInput
          container
          label="Titular"
          name="holder_name"
          v-model="form.holder_name"
          type="text"
          maxlength="100"
          placeholder="Nombre del titular"
          required
          :error="errors.holder_name"
          col="col-12"
        />

        <!-- Tipo + Número de Documento (input-group) -->
        <FormInput
          container
          label="Documento"
          name="document_number"
          v-model="form.document_number"
          type="text"
          maxlength="8"
          placeholder="12345678"
          required
          input-group
          col="col-12 col-md-6"
        >
          <template #prepend>
            <select
              class="custom-select"
              v-model="form.document_type_id"
              required
            >
              <option value="" disabled>Tipo</option>
              <option
                v-for="dt in documentTypesOptions"
                :key="dt.id"
                :value="dt.id"
              >
                {{ dt.code }}
              </option>
            </select>
          </template>
        </FormInput>

        <!-- Teléfono -->
        <FormInput
          container
          label="Teléfono"
          name="phone"
          v-model="form.phone"
          type="text"
          maxlength="11"
          placeholder="04241234567"
          required
          :error="errors.phone"
          col="col-12 col-md-6"
        />

        <!-- Banco Venezuela -->
        <FormSelect
          container
          label="Banco"
          name="bank_id"
          v-model="form.bank_id"
          :options="banksOptions"
          optionValue="id"
          optionLabel="name"
          required
          :error="errors.bank_id"
          col="col-12 col-md-6"
        />
      </template>

      <!-- ============================================================ -->
      <!-- FORMULARIO BRASIL (PIX)                                      -->
      <!-- ============================================================ -->
      <template v-else-if="isBR">
        <!-- Nombre -->
        <FormInput
          container
          label="Nombre"
          name="holder_name"
          v-model="form.holder_name"
          type="text"
          maxlength="35"
          placeholder="Nombre del titular"
          required
          :error="errors.holder_name"
          col="col-12"
        />

        <!-- Tipo de Clave PIX -->
        <FormSelect
          container
          label="Tipo PIX"
          name="pix_key_type"
          v-model="form.pix_key_type"
          :options="PIX_KEY_TYPES"
          optionValue="id"
          optionLabel="name"
          required
          :error="errors.pix_key_type"
          col="col-12 col-md-6"
        />

        <!-- Clave PIX — placeholder y maxlength dinámicos según tipo -->
        <FormInput
          container
          label="Clave PIX"
          name="pix_key_value"
          v-model="form.pix_key_value"
          type="text"
          :maxlength="pixFieldMeta.maxlength"
          :placeholder="pixFieldMeta.placeholder"
          required
          :error="errors.pix_key_value"
          col="col-12 col-md-6"
        />

        <!-- Banco Brasil -->
        <FormSelect
          container
          label="Banco"
          name="bank_id"
          v-model="form.bank_id"
          :options="banksOptions"
          optionValue="id"
          optionLabel="name"
          required
          :error="errors.bank_id"
          col="col-12 col-md-6"
        />
      </template>

    </div>
  </form>
</template>
