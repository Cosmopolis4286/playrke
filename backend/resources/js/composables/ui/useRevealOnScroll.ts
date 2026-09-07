import { onMounted, onUnmounted, type Ref } from 'vue'

/**
 * Agrega una clase al elemento cuando entra en viewport, vía
 * IntersectionObserver — no hay forma de lograr esto con CSS puro (a
 * diferencia del resto del responsive del proyecto, que sí usa solo media
 * queries). Respeta prefers-reduced-motion mostrando el contenido de
 * inmediato, sin observar nada.
 */
export function useRevealOnScroll(targets: Ref<HTMLElement[]>, visibleClass = 'is-visible') {
  let observer: IntersectionObserver | null = null

  onMounted(() => {
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches

    if (prefersReducedMotion) {
      targets.value.forEach(el => el.classList.add(visibleClass))
      return
    }

    observer = new IntersectionObserver(
      (entries) => {
        for (const entry of entries) {
          if (entry.isIntersecting) {
            entry.target.classList.add(visibleClass)
            observer?.unobserve(entry.target)
          }
        }
      },
      { threshold: 0.15 }
    )

    targets.value.forEach(el => observer?.observe(el))
  })

  onUnmounted(() => observer?.disconnect())
}
