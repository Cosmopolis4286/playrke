// resources/js/helpers/dataTables.ts
import $ from 'jquery';
import 'datatables.net-bs4';

/**
 * Inicializa DataTables en tablas con clase `.dataTable`.
 */
function loadDataTable(languageUrl: string): void {
    const $table = $('table.dataTable');
    if (!$table.length || typeof ($ as any).fn?.DataTable !== 'function') return;

    $table.DataTable({
        ordering: true,
        order: [],
        language: { url: languageUrl },
        columnDefs: [{ targets: '_all', className: 'text-left' }],
    });
}

/**
 * Inicializa DataTables en todas las tablas con clase .data-table
 */
export async function initDataTables(): Promise<void> {
    const tables = document.querySelectorAll('table.data-table');
    if (!tables.length) return;

    try {
        const languageUrl = '/lang/datatables/es.json';
        loadDataTable(languageUrl);
        if (import.meta.env.DEV) {
            console.log(`✅ DataTables inicializadas (${tables.length} tablas)`);
        }
    } catch (err) {
        console.error('❌ Error inicializando DataTables:', err);
    }
}
