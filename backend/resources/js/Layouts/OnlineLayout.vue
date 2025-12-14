<template>
    <div
        class="d-flex flex-column min-vh-100 hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
        <!-- Loader visible mientras la app se carga -->
        <AppLoader v-if="loading" />

        <!-- Contenedor principal, oculto hasta que la app esté lista -->
        <div id="app-wrapper" :class="{ 'd-none': loading }" class="flex-grow-1">
            <!-- Barra de navegación -->
            <Navbar />

            <!-- Sidebar selector -->
            <SidebarSelector />

            <!-- Alertas SweetAlert -->
            <SweetAlert />

            <!-- Contenido principal -->
            <main class="content-wrapper">
                <div class="content-header">
                    <div class="container-fluid">
                        <slot />
                    </div>
                </div>
            </main>

            <!-- Modal para cambiar de contraseña -->
            <ChangePasswordModal id="change-password" title="Cambiar Contraseña" />

            <!-- Pie de página -->
            <Footer />
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import AppLoader from '@/components/shared/AppLoader.vue'
import Navbar from '@/components/template/Navbar.vue'
import SidebarSelector from '@/components/template/sidebar/SidebarSelector.vue'
import SweetAlert from '@/components/sweetalert/SweetAlert.vue' // O el wrapper que uses para sweetalert
import ChangePasswordModal from '@/components/shared/modal/ChangePasswordModal.vue'
import Footer from '@/components/template/Footer.vue'

// Control simple para loader
const loading = ref(true)

onMounted(() => {
    // Simula que la app ya cargó, oculta el loader y muestra contenido
    loading.value = false
})
</script>

<style scoped>
/* Aquí puedes poner estilos scoped o dejar que vengan de tu CSS global */
</style>
