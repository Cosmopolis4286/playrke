import Swal from 'sweetalert2'
import { showToast } from '@/js/utils/toastNotify'

export interface BulkResult {
    updated: number
    skipped: { id: string; name: string; reason: string }[]
}

/**
 * Construye el HTML con la lista de nombres de los elementos seleccionados,
 * usado en los diálogos de confirmación de acciones bulk (toggle status,
 * eliminar) — mismo bloque visual repetido antes en cada composable admin
 * (useAdminPlayer.ts, useAdminCommerce.ts, ...).
 */
export function buildBulkItemListHtml(names: string[]): string {
    return `
        <div style="max-height:150px;overflow-y:auto;text-align:left;margin-top:10px;padding:10px;background:#f8f9fa;border-radius:6px;">
            <ul style="margin:0;padding-left:20px;">
                ${names.map(name => `<li style="padding:4px 0;">${name}</li>`).join('')}
            </ul>
        </div>
    `
}

/**
 * Muestra el resultado de una operación bulk transaccional. Si hubo
 * omitidos (ej. registros ya eliminados), detalla cada uno con su razón en
 * vez de un toast genérico — el backend ya aplicó lo que pudo en una sola
 * transacción (ver AdminPlayerCrudService/AdminCommerceCrudService
 * toggleStatusBulk()/deleteBulk()).
 */
export async function reportBulkResult(result: BulkResult, entityLabel: string, successVerb: string): Promise<void> {
    if (result.skipped.length === 0) {
        showToast(`${result.updated} ${entityLabel} ${successVerb} correctamente`, 'success')
        return
    }

    const skippedHtml = result.skipped
        .map(s => `<li style="padding:4px 0;"><b>${s.name}</b>: ${s.reason}</li>`)
        .join('')

    await Swal.fire({
        icon: result.updated > 0 ? 'warning' : 'error',
        title: result.updated > 0 ? 'Completado parcialmente' : 'No se pudo completar',
        html: `
            <p style="margin-bottom:8px;">${result.updated} ${entityLabel} ${successVerb} correctamente.</p>
            <p style="margin-bottom:4px;">${result.skipped.length} omitido(s):</p>
            <div style="max-height:150px;overflow-y:auto;text-align:left;padding:10px;background:#f8f9fa;border-radius:6px;">
                <ul style="margin:0;padding-left:20px;">${skippedHtml}</ul>
            </div>
        `,
        confirmButtonText: 'Entendido',
    })
}
