/**
 * Plugin de DataTables para tablas interactivas
 * Proporciona funcionalidad avanzada de tablas en el admin
 */

export default defineNuxtPlugin(() => {
  // Configuración por defecto para tablas
  const defaultConfig = {
    pageSize: 10,
    pageSizes: [5, 10, 25, 50, 100],
    sortable: true,
    searchable: true,
    exportable: true
  }

  // Utilidad para crear configuración de tabla
  const createTableConfig = (options = {}) => {
    return {
      ...defaultConfig,
      ...options
    }
  }

  // Utilidad para exportar datos
  const exportData = (data: any[], format: 'csv' | 'excel' | 'pdf', filename: string) => {
    // TODO: Implementar exportación según formato
    console.log(`Exporting ${data.length} rows as ${format} to ${filename}`)
  }

  return {
    provide: {
      dataTables: {
        defaultConfig,
        createTableConfig,
        exportData
      }
    }
  }
})
