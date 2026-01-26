// resources/js/helpers/user/password.ts
import { submitForm } from '../form/services/formSubmitService'
import Swal from 'sweetalert2'
import { showToast } from '@js/utils/toastNotify'
import { initPasswordToggle } from '../form/ui/passwordToggle'

/**
 * Inicializa el módulo de cambio de contraseña:
 * - Abre modal
 * - Confirma cambio de contraseña
 */
export async function initChangePassw(): Promise<void> {
  initPasswordToggle()

  const modalChangePassw = document.getElementById('modal-change-password')

  document.addEventListener('click', event => {
    const target = event.target as HTMLElement

    // Abrir modal de cambio de contraseña
    if (target?.id === 'change-password' && modalChangePassw) {
      $(modalChangePassw).modal('show')
      return
    }
    // Confirmar cambio de contraseña desde el modal
    if (target?.id === 'btn-change-password') {
      submitForm('change-password-form')
      return
    }
  })
}

/**
 * Modal para mostrar contraseña generada con botón copiar
 */
export async function showPasswordModal(password: string) {
  await Swal.fire({
    title: 'Nueva Contraseña Generada',
    html: `
      <div style="text-align: left;">
        <p><strong>Contraseña:</strong></p>
        <div style="position: relative; display: flex; align-items: center; gap: 8px;">
          <div id="password-display" style="flex: 1; background-color: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 16px; word-break: break-all;">
            ${password}
          </div>
          <button id="copy-password-btn" type="button" style="padding: 10px 15px; background-color: #6366f1; color: white; border: none; border-radius: 5px; cursor: pointer; display: flex; align-items: center; gap: 5px; min-width: 80px; justify-content: center;" title="Copiar al portapapeles">
            <i class="fas fa-copy"></i>
            <span>Copiar</span>
          </button>
        </div>
        <p style="color: #6c757d; font-size: 14px; margin-top: 15px;">
          <i class="fas fa-info-circle"></i> Guarda esta contraseña en un lugar seguro. No podrás verla nuevamente.
        </p>
      </div>
    `,
    icon: 'success',
    confirmButtonText: 'Entendido',
    allowOutsideClick: false,
    allowEscapeKey: false,
    didOpen: () => {
      const copyBtn = document.getElementById('copy-password-btn')
      if (!copyBtn) return

      copyBtn.addEventListener('click', async () => {
        try {
          await navigator.clipboard.writeText(password)
          const btnText = copyBtn.querySelector('span')
          const btnIcon = copyBtn.querySelector('i')

          if (btnText && btnIcon) {
            btnIcon.className = 'fas fa-check'
            btnText.textContent = 'Copiado'
            copyBtn.style.backgroundColor = '#10b981'

            setTimeout(() => {
              btnIcon.className = 'fas fa-copy'
              btnText.textContent = 'Copiar'
              copyBtn.style.backgroundColor = '#6366f1'
            }, 2000)
          }
        } catch {
          showToast('Error al copiar la contraseña', 'error')
        }
      })
    }
  })
}
