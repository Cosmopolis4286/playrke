{{-- // backend/resources/views/components/shared/table-with-filter-vue.blade.php --}}
<div id="table-{{ $tableId }}-vue-wrapper">
    <table-with-filter-bridge table-id="{{ $tableId }}" title="{{ $title }}"
        :columns='@json($vueColumns)' :rows='@json($vueRows)'
        :filters='@json($filters)' search-placeholder="{{ $searchPlaceholder }}"
        :show-create-button="{{ $showCreateButton ? 'true' : 'false' }}"
        :show-edit-button="{{ $showEditButton ? 'true' : 'false' }}"
        :show-delete-button="{{ $showDeleteButton ? 'true' : 'false' }}" module-id="{{ $moduleId }}"
        context="{{ $context }}" :extra-buttons='@json($extraButtons)'>
        {{ $slot }}
    </table-with-filter-bridge>
</div>

@once
    @push('stack-scripts')
        @vite(['resources/js/helpers/table/table-filter-bridge-init.ts'])

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                window.initTableFilterBridge?.(
                    '{{ $tableId }}',
                    '{{ $moduleId }}'
                );
            });
        </script>
    @endpush
@endonce
