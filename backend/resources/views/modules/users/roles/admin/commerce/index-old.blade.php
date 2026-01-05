<x-layouts.layout-auth :title="$title" loadSelect2="true">
    {{-- Tabla de comercios --}}
    <x-shared.table-with-selection :title="$title" :rows="$pageData['commerces']" :columns="['Nombre', 'País', 'Teléfono', 'Email', 'Status']" :row-keys="['name', 'Country.name', 'phone', 'User.email', 'status_label']"
        module-id="commerce-form" context="commerce" table-id="commerce-table" />
    {{-- Modal para formulario de comercio --}}
    <x-shared.modal id="commerce-form" title="Comercio" form-view="modules.users.roles.admin.commerce.form" />
    {{-- Scripts globales solo una vez --}}
    @once
        @push('stack-scripts')
            <script type="application/json" id="page-data">
                {!! json_encode($pageData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
            </script>
        @endpush
    @endonce
</x-layouts.layout-auth>
