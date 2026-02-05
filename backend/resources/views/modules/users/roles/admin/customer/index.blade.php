<x-layouts.auth-layout :title="$title" loadSelect2="true">
    {{-- Tabla de clientes --}}
    <x-shared.table-with-selection :title="$title" :rows="$pageData['customers']" :columns="['Nombre', 'País', 'Teléfono', 'Email', 'Status']" :row-keys="['name', 'Country.name', 'phone', 'User.email', 'status_badge']"
        module-id="customer-form" context="customer" table-id="customer-table" />
    {{-- Modal para formulario de cliente --}}
    <x-shared.modal id="customer-form" title="Cliente" form-view="modules.users.roles.admin.customer.form" />
    {{-- Scripts globales solo una vez --}}
    @once
        @push('stack-scripts')
            <script type="application/json" id="page-data">
                {!! json_encode($pageData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
            </script>
        @endpush
    @endonce
</x-layouts.auth-layout>
