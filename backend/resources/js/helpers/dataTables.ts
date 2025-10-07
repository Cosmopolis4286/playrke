// resources/js/helpers/dataTables.ts
import $ from 'jquery';
import 'datatables.net-bs4';

/**
 * Inicializa DataTables en tablas con clase `.dataTable`.
 */
export function loadDataTable(languageUrl: string): void {
    const $table = $('table.dataTable');
    if (!$table.length || typeof ($ as any).fn?.DataTable !== 'function') return;

    $table.DataTable({
        ordering: true,
        order: [],
        language: { url: languageUrl },
        columnDefs: [{ targets: '_all', className: 'text-left' }],
    });
}