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
        table-id="{{ $tableId }}"
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
        {{-- Incluir el script de inicialización compilado por Vite --}}
        @vite(['resources/js/components/shared/table-filter-bridge-init.ts'])

        <script>
            // Inicializar cuando el DOM esté listo
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof window.initTableFilterBridge === 'function') {
                    window.initTableFilterBridge('{{ $tableId }}', '{{ $moduleId }}');
                } else {
                    console.error('initTableFilterBridge no está disponible');
                }
            });
        </script>
    @endpush
@endonce
