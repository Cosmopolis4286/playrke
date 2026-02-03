<script setup lang="ts" name="DirectoryForm">
  import { computed } from 'vue'

  import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'
  import FormInput from '@/js/Components/Common/Form/FormInput.vue'

  import { useDirectoryForm } from '@/js/composables/directory/useDirectoryForm'

  import type { DirectoryVEN } from '@/js/types/directory/directory-ven'
  import type { BanksVEN } from '@/js/types/bank/banks-ven'

  /**
   * Props
   */
  const props = defineProps<{
    modelValue: Partial<DirectoryVEN>
    banks?: BanksVEN[]
    documentTypes?: Array<{ id: string; code: string; description: string }>
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
  const { form, errors, isEditMode, isValid, validate } = useDirectoryForm(props.modelValue, emit)

  /* =======================
   * Computed
   * ======================= */

  const banksOptions = computed<BanksVEN[]>(() => {
    return props.banks ?? []
  })

  const documentTypesOptions = computed(() => {
    return props.documentTypes ?? []
  })

  /* =======================
   * Methods
   * ======================= */

  /**
   * Submit interno del formulario
   */
  const submit = (): boolean => {
    validate()
    if (!isValid.value) return false

    emit('submit')
    return true
  }

  /**
   * API pública para el modal
   */
  defineExpose({
    submit,
    isValid
  })
</script>
<template>
  <form @submit.prevent="submit" novalidate>
    <div class="row">
      <!-- Tipo de Documento -->
      <FormSelect
        container
        label="Tipo de Documento"
        name="document_type_id"
        v-model="form.document_type_id"
        :options="documentTypesOptions"
        optionValue="id"
        optionLabel="code"
        required
        :error="errors.document_type_id"
        col="col-12 col-md-6"
      />

      <!-- Número de Documento/Cédula -->
      <FormInput
        container
        label="Número de Documento"
        name="document_number"
        v-model="form.document_number"
        type="text"
        maxlength="8"
        placeholder="12345678"
        required
        :error="errors.document_number"
        col="col-12 col-md-6"
      />

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

      <!-- Banco -->
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
    </div>
  </form>
</template>
