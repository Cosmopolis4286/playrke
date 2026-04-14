<template>
    <Transition name="scroll-top">
        <button
            v-if="visible"
            class="scroll-to-top"
            type="button"
            title="Ir al inicio"
            aria-label="Volver al inicio del contenido"
            @click="scrollToTop"
        >
            <i class="fas fa-chevron-up" />
        </button>
    </Transition>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'

// ID del contenedor scrollable del layout (section.content)
const SCROLL_CONTAINER_ID = 'content-scrollable'
const THRESHOLD = 300 // px desde el top para mostrar el botón

const visible = ref(false)
let container: HTMLElement | Window = window

function getContainer(): HTMLElement | Window {
    return document.getElementById(SCROLL_CONTAINER_ID) ?? window
}

function getScrollTop(): number {
    if (container instanceof Window) return window.scrollY
    return (container as HTMLElement).scrollTop
}

function onScroll() {
    visible.value = getScrollTop() > THRESHOLD
}

function scrollToTop() {
    if (container instanceof Window) {
        window.scrollTo({ top: 0, behavior: 'smooth' })
    } else {
        ;(container as HTMLElement).scrollTo({ top: 0, behavior: 'smooth' })
    }
}

onMounted(() => {
    container = getContainer()
    container.addEventListener('scroll', onScroll, { passive: true })
})

onUnmounted(() => {
    container.removeEventListener('scroll', onScroll)
})
</script>

<style scoped>
.scroll-to-top {
    position: fixed;
    bottom: 1.5rem;
    right: 1.5rem;
    z-index: 1050;

    width: 2.75rem;
    height: 2.75rem;
    border-radius: 50%;
    border: none;
    cursor: pointer;

    background-color: #c0392b;
    color: #fff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);

    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;

    transition: background-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
}

.scroll-to-top:hover {
    background-color: #a93226;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.3);
}

.scroll-to-top:active {
    transform: translateY(0);
}

/* Transition */
.scroll-top-enter-active,
.scroll-top-leave-active {
    transition: opacity 0.25s ease, transform 0.25s ease;
}

.scroll-top-enter-from,
.scroll-top-leave-to {
    opacity: 0;
    transform: translateY(0.75rem);
}
</style>
