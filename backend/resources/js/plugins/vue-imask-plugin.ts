import { App, Directive } from 'vue'
import IMask from 'imask'

/**
 * Directiva personalizada `v-mask` para Vue con IMask.
 * Inicializa, actualiza y destruye la máscara en el elemento.
 */
const maskDirective: Directive = {
    mounted(el, binding) {
        el._imask = IMask(el, binding.value)
    },
    updated(el, binding) {
        if (binding.value !== binding.oldValue && el._imask) {
            el._imask.updateOptions(binding.value)
        }
    },
    unmounted(el) {
        if (el._imask) {
            el._imask.destroy()
            delete el._imask
        }
    }
}

/**
 * Plugin Vue para registrar la directiva `v-mask` globalmente.
 */
export default {
    install(app: App) {
        app.directive('mask', maskDirective)
    }
}
