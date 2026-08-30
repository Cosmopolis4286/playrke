import { useToast, type ToastOptions } from 'vue-toastification'
import ToastDetail from '@/js/Components/Common/Toast/ToastDetail.vue'

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
 *
 * `options` es opcional y retrocompatible (todos los +30 llamadores
 * existentes siguen funcionando igual sin tocarlos) — permite, por ejemplo,
 * alargar el `timeout` de un toast con más contenido que el texto corto
 * habitual, sin cambiar la duración global configurada en inertia.ts.
 */
export function showToast(text: string, icon: ToastIcon | 'question' = 'info', options?: ToastOptions): void {
    const type: ToastIcon = VALID_ICONS.includes(icon as ToastIcon) ? (icon as ToastIcon) : 'info'
    toast[type](text, options)
}

/**
 * Toast con título + detalle en dos líneas (en vez de una sola frase
 * genérica) — pensado para acciones donde el resultado real importa más que
 * "listo/error" (ej. "Ejecutar ahora" en /system/jobs: qué comando corrió y
 * qué devolvió, no solo "Comando ejecutado correctamente"). El detalle se
 * trunca a 180 caracteres: el toast es un aviso rápido, no el lugar para
 * pegar un output completo — para eso ya existe el historial/modal de detalle.
 *
 * Renderiza vía un Component real (ToastDetail.vue, con :text de Vue, no
 * innerHTML) en vez de construir HTML a mano — evita cualquier superficie de
 * XSS si algún día `detail` incluye output de un comando con caracteres
 * "interesantes".
 */
export function showDetailedToast(title: string, detail: string | null, icon: ToastIcon, timeoutMs?: number): void {
    const truncated = detail ? (detail.length > 180 ? `${detail.slice(0, 180)}…` : detail) : null

    toast[icon](
        { component: ToastDetail, props: { title, detail: truncated } },
        { timeout: timeoutMs ?? (icon === 'error' ? 7000 : 5000) }
    )
}

/**
 * Texto pluralizado según cantidad de registros afectados — mismo
 * comportamiento que crudModalUI.ts::showSuccess() antes de esta migración.
 */
export function showSuccess(count: number): void {
    const text = count > 1 ? `Acción aplicada a ${count} registros` : 'Acción completada'
    showToast(text, 'success')
}
