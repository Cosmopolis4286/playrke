<script setup lang="ts">
  /* ==========================================================================
   * Imports
   * ========================================================================== */

  import { ref, watch } from 'vue'

  // UI
  import CardHeader from '@js/Components/Common/Card/CardHeader.vue'
  import CountryTabs from '@/js/Components/Directory/CountryTabs.vue'
  import DirectoryButtons from '@js/Components/Directory/DirectoryButtons.vue'
  import DirectoryForm from '@/js/Components/Directory/DirectoryForm.vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'

  // Composables
  import { useDirectory } from '@/js/composables/directory/useDirectory'
  import { useModalForm } from '@/js/composables/common/useModalForm'

  // Types
  import type { DirectoryPageProps, UsedCurrency } from '@/js/types/directory'

  /* ==========================================================================
   * Props
   * ========================================================================== */

  /**
   * Props provenientes de Inertia
   */
  const props = defineProps<DirectoryPageProps>()

  /* ==========================================================================
   * Lógica de negocio
   * ========================================================================== */

  const { directoryData, directories, setInitialDirectories, create, edit, submit } = useDirectory()

  // setInitialDirectories(props.directories)

  /* ==========================================================================
   * Modal + Form handler
   * ========================================================================== */
  const {
    showModalForm,
    modalTitle,
    formRef: directoryFormRef,
    handleSubmit
  } = useModalForm(directoryData, submit, {
    createTitle: 'Registrar Directorio',
    editTitle: 'Editar Directorio'
  })

  /**
   * Al cerrar el modal:
   * - resetea el formulario
   * - vuelve al modo creación
   */
  watch(showModalForm, isOpen => {
    if (!isOpen) {
      directoryFormRef.value?.reset?.()
      create()
    }
  })

  /* ==========================================================================
   * Estado local
   * ========================================================================== */

  /**
   * Tabs por país
   */
  const countries = [
    { id: 1, iso_code_2: 'US' },
    { id: 2, iso_code_2: 'BR' },
    { id: 3, iso_code_2: 'FR' }
  ]

  const selectedCode = ref('US')
  const title = ref(`Directorio ${selectedCode.value}`)
  const subtitle = ref('Método: PagoMovil')

  /* ==========================================================================
   * Watchers
   * ========================================================================== */

  /* ==========================================================================
   * Handlers
   * ========================================================================== */

  /**
   * Abre el modal en modo creación
   */
  function handleCreate(): void {
    create()
    showModalForm.value = true
  }

  /**
   * Abre el modal en modo edición
   *
   * @param id Identificador del registro
   */
  async function handleEdit(id: string | number): Promise<void> {
    await edit(id)
    showModalForm.value = true
  }

  /**
   * Maneja el cambio de tab por país
   *
   * @param code ISO code del país
   */
  function onTabChange(code: string): void {
    selectedCode.value = code
    title.value = `Directorio ${code}`

    // Aquí puedes:
    // - filtrar data
    // - hacer router.push
    // - llamar API
  }
</script>
<template>
  <div class="card">
    <CardHeader :title="title" :subtitle="subtitle" />

    <div class="card-body">
      <CountryTabs
        class="mt-n2"
        :items="countries"
        v-model:activeCode="selectedCode"
        @tabSelected="onTabChange"
      />

      <DirectoryButtons @create="handleCreate" />

      <!-- Tabla pendiente de integrar -->
      <!--
      <DirectoryTable
        :columns="columns"
        @edit="handleEdit"
      />
      -->
    </div>
  </div>

  <Modal
    v-model="showModalForm"
    :title="modalTitle"
    static
    show-confirm
    :on-submit="handleSubmit"
    :confirm-disabled="!(directoryFormRef?.isValid ?? false)"
  >
    <DirectoryForm ref="directoryFormRef" v-model="directoryData" />
  </Modal>
</template>
