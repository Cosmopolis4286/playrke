<script setup lang="ts">
  import { computed, onMounted } from 'vue'

  // Components
  import AdministratorTable from '@/js/Components/Roles/Admin/AdministratorTable.vue'

  // Composables
  import { useAdminAdministrator } from '@/js/composables/roles/admin/administrator/useAdminAdministrator'

  // Types
  import type { AdminAdministratorPageProps } from '@/js/types/roles/admin/administrator-page'

  // =========================================================================
  // Props (Inertia)
  // =========================================================================
  const props = defineProps<AdminAdministratorPageProps>()

  // =========================================================================
  // Lógica de negocio
  // =========================================================================
  const {
    fetchadmins,
    toggleStatus,
    resetPassword,
    remove,
    destroy
  } = useAdminAdministrator()

  // =========================================================================
  // Event listeners for table CRUD events
  // =========================================================================
  onMounted(() => {
    window.addEventListener('table:crud', handleTableCrudEvent)
  })

  const handleTableCrudEvent = (event: CustomEvent) => {
    const { action, encryptIds, tableId } = event.detail

    if (tableId !== 'admins-table') return

    switch (action) {
      case 'toggleStatus':
        encryptIds.forEach((id: string) => toggleStatus(id))
        break
      case 'resetPassword':
        encryptIds.forEach((id: string) => resetPassword(id))
        break
      case 'delete':
        remove(encryptIds)
        break
      case 'destroy':
        destroy(encryptIds)
        break
    }
  }

  // =========================================================================
  // Computed
  // =========================================================================
  const pageTitle = computed(() => 'Administradores')
</script>

<template>
  <div>
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0 text-dark">{{ pageTitle }}</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item">
                <a href="/dashboard">Admin</a>
              </li>
              <li class="breadcrumb-item active">{{ pageTitle }}</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <section class="content">
      <div class="container-fluid">
        <AdministratorTable
          :admins="props.admins"
        />
      </div>
    </section>
  </div>
</template>
