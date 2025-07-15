<template>
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <a v-if="showReturnBtn" :href="routeDashboard" title="Regresar" class="mr-2 mb-n1">
                    <i class="fas fa-arrow-circle-left" style="font-size: 20px;"></i>
                </a>

                <div>
                    <div :class="['d-flex', subtitle ? 'mb-n3' : 'mb-n2']">
                        <h5 v-html="displayTitle"></h5>
                        <a v-if="btnCardHeader" :href="btnCardHeader.route" class="text-muted ml-2 mb-n1">
                            <i :class="btnCardHeader.icon"></i>
                        </a>
                    </div>
                    <small v-if="subtitle">{{ subtitle }}</small>
                </div>
            </div>

            <div id="loader-sm" class="spinner-border spinner-border-sm text-primary" role="status"
                :style="{ display: loading ? 'inline-block' : 'none' }">
                <span class="sr-only">Loading...</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'

// Props que replican las variables de Blade
const props = defineProps({
    cardTitle: String,
    title: String,
    subtitle: String,
    btnCardHeader: Object,
    returnBtn: {
        type: Boolean,
        default: true
    }
})

// Computed para mostrar título correcto (cardTitle o title)
const displayTitle = computed(() => props.cardTitle ?? props.title ?? '')

// Controla si mostrar el botón de regresar
const showReturnBtn = computed(() => props.returnBtn !== false)

// Ruta al dashboard (ajusta si tienes Ziggy u otra forma)
const routeDashboard = '/dashboard'

// Controla visibilidad de spinner (puedes manejarlo desde afuera con emits o props)
const loading = ref(false)
</script>

<style scoped>
/* Mantén o agrega estilos según necesidad */
</style>
