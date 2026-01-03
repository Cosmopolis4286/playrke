{{-- resources/views/components/shared/table-with-filter-vue.blade.php --}}
@props([
    'title' => '',
    'moduleId' => '',
    'columns' => [],
    'rows' => collect(),
    'rowKeys' => [],
    'context' => '',
    'tableId' => '',
    'hashedIds' => [],
    'filters' => [],
    'searchPlaceholder' => 'Buscar en la tabla...',
    'showCreateButton' => true,
    'showEditButton' => true,
    'showDeleteButton' => true,
])

@php
    // Transformar rows a formato Vue
    $vueRows = $rows->map(function ($row, $index) use ($hashedIds, $rowKeys) {
        $transformed = [
            'id' => $hashedIds[$index] ?? $row->id,
            'status' => $row->status ?? '',
        ];

        // Agregar todas las columnas usando rowKeys
        foreach ($rowKeys as $key) {
            // Manejar nested keys (ej: 'User.email')
            if (str_contains($key, '.')) {
                $parts = explode('.', $key);
                $value = $row;
                foreach ($parts as $part) {
                    $value = $value->{$part} ?? null;
                }
                // Usar la última parte como key
                $transformed[str_replace('.', '_', $key)] = $value;
            } else {
                $transformed[$key] = data_get($row, $key);
            }
        }

        return $transformed;
    })->toArray();

    // Transformar columns a formato Vue
    $vueColumns = [];
    foreach ($columns as $index => $label) {
        $key = $rowKeys[$index] ?? "col_$index";
        // Convertir nested keys (User.email -> User_email)
        $vueKey = str_replace('.', '_', $key);

        $vueColumns[] = [
            'key' => $vueKey,
            'label' => $label,
            'html' => $key === 'status_label', // Permitir HTML solo para status_label
        ];
    }
@endphp

<div id="table-{{ $tableId }}-vue-wrapper">
    <table-with-filter-bridge
        title="{{ $title }}"
        :columns='@json($vueColumns)'
        :rows='@json($vueRows)'
        :filters='@json($filters)'
        search-placeholder="{{ $searchPlaceholder }}"
        :show-create-button="{{ $showCreateButton ? 'true' : 'false' }}"
        :show-edit-button="{{ $showEditButton ? 'true' : 'false' }}"
        :show-delete-button="{{ $showDeleteButton ? 'true' : 'false' }}"
        module-id="{{ $moduleId }}"
        context="{{ $context }}"
    >
        {{-- Slots personalizados si son necesarios --}}
        {{ $slot }}
    </table-with-filter-bridge>
</div>

@once
    @push('stack-scripts')
        <script type="module">
            import { createApp } from 'vue';
            import TableWithFilterBridge from '@/components/shared/TableWithFilterBridge.vue';

            // Crear aplicación Vue para la tabla
            const app = createApp({
                components: {
                    TableWithFilterBridge
                }
            });

            app.mount('#table-{{ $tableId }}-vue-wrapper');

            // Integración con el sistema existente
            window.addEventListener('table:create', (e) => {
                if (e.detail.moduleId === '{{ $moduleId }}') {
                    // Trigger formCRUD existente
                    const btn = document.querySelector('.btn-{{ $moduleId }}[data-action="create"]');
                    if (btn) btn.click();
                }
            });

            window.addEventListener('table:edit', (e) => {
                if (e.detail.moduleId === '{{ $moduleId }}') {
                    // Actualizar el ID seleccionado en Alpine/tabla
                    const btn = document.querySelector('.btn-{{ $moduleId }}[data-action="edit"]');
                    if (btn) btn.click();
                }
            });

            window.addEventListener('table:delete', (e) => {
                if (e.detail.moduleId === '{{ $moduleId }}') {
                    const btn = document.querySelector('.btn-{{ $moduleId }}[data-action="delete"]');
                    if (btn) btn.click();
                }
            });

            window.addEventListener('table:selection-change', (e) => {
                if (e.detail.moduleId === '{{ $moduleId }}') {
                    // Actualizar selección global si se usa Alpine
                    if (window.Alpine) {
                        const element = document.querySelector('[data-table-id="{{ $tableId }}"]');
                        if (element && element.__x) {
                            const component = element.__x.$data;
                            if (component && component.selected) {
                                component.selected = e.detail.selectedIds;
                            }
                        }
                    }
                }
            });
        </script>
    @endpush
@endonce
