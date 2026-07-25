import { useToast } from 'vue-toastification'

export type ToastIcon = 'success' | 'error' | 'warning' | 'info'

/**
 * Fuente de verdad única para toasts efímeros de toda la app, sobre
 * vue-toastification (ya registrado globalmente en inertia.ts). Antes
 * existían dos wrappers de sweetalert2 con nombres de función colisionantes
 * (utils/toastNotify.ts y helpers/form/ui/crudModalUI.ts, ambos exportando
 * showToast con firmas distintas) — este módulo los reemplaza a ambos.
 *
 * useToast() de vue-toastification no depende de contexto de componente
 * (usa un event bus interno), así que es seguro llamarlo desde archivos
 * .ts planos como este.
 */
const toast = useToast()

const VALID_ICONS: ToastIcon[] = ['success', 'error', 'warning', 'info']

/**
 * Muestra un toast efímero. `icon` acepta también 'question' por
 * compatibilidad con el mixin de sweetalert2 que reemplaza — se degrada a
 * 'info' porque vue-toastification no tiene un tipo equivalente.
 */
export function showToast(text: string, icon: ToastIcon | 'question' = 'info'): void {
    const type: ToastIcon = VALID_ICONS.includes(icon as ToastIcon) ? (icon as ToastIcon) : 'info'
    toast[type](text)
}

/**
 * Texto pluralizado según cantidad de registros afectados — mismo
 * comportamiento que crudModalUI.ts::showSuccess() antes de esta migración.
 */
export function showSuccess(count: number): void {
    const text = count > 1 ? `Acción aplicada a ${count} registros` : 'Acción completada'
    showToast(text, 'success')
}
