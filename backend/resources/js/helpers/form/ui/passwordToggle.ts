// resources/js/helpers/ui/passwordToggle.ts

/**
 * Inicializa el toggle de visibilidad para inputs password
 */
export function initPasswordToggle(): void {
  document.addEventListener('click', event => {
    const btn = (event.target as HTMLElement)?.closest('.toggle-password')
    if (!btn) return

    const targetId = (btn as HTMLElement).dataset.target
    if (!targetId) return

    const input = document.getElementById(targetId) as HTMLInputElement | null
    const icon = btn.querySelector('i')

    if (!input || !icon) return

    const isVisible = input.type === 'text'
    input.type = isVisible ? 'password' : 'text'
    icon.className = isVisible ? 'fas fa-eye' : 'fas fa-eye-slash'
  })
}
