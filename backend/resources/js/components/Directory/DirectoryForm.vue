<script setup lang="ts" name="DirectoryForm">
  import { computed } from 'vue'
  import { useDirectoryForm } from '@/js/composables/directory/useDirectoryForm'
  import type { Directory } from '@/js/types/directory'
  import type { BanksVEN } from '@/js/types/bank/banks-ven'

  /**
   * Props
   */
  const props = defineProps<{
    modelValue: Partial<Directory>
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
  const { form, errors, maskOptions, isEditMode, isValid, validate } = useDirectoryForm(
    props.modelValue,
    emit
  )

  const documentTypes = [
    { value: 'V', label: 'V' },
    { value: 'E', label: 'E' },
    { value: 'J', label: 'J' }
  ]

  /* =======================
   * Computed
   * ======================= */

  const banks = computed<BanksVEN[]>(() => {
    return props.modelValue?.banks ?? []
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
      <!-- CI / RIF -->
      <FormInput
        container
        label="CI / RIF"
        name="document"
        required
        input-group
        col="col-12 col-md-6"
      >
        <!-- Tipo documento -->
        <FormSelect
          name="document_type"
          :options="documentTypes"
          optionValue="value"
          optionLabel="label"
          required
          className="me-1"
          col="col-auto"
        />

        <!-- Número documento -->
        <FormInput name="document_val" type="text" maxlength="8" required />
      </FormInput>

      <!-- Banco -->
      <FormSelect
        container
        label="Banco"
        name="bank"
        :options="banks"
        optionValue="id"
        optionLabel="name"
        required
        col="col-12 col-md-6"
      />
    </div>
  </form>
</template>
