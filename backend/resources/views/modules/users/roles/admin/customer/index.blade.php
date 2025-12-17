<x-layouts.layout-online :title="$title" loadSelect2="true">
    {{-- Tabla de comercios --}}
    <x-shared.table-with-selection :title="$title" :rows="$pageData['commerces']"
        :columns="['Nombre', 'País', 'Teléfono', 'Email', 'Status']"
        :row-keys="['name', 'Country.name', 'phone', 'User.email', 'status_label']"
        module-id="commerce-form" context="commerce"
    />
    {{-- Modal para formulario de comercio --}}
    <x-shared.modal id="commerce-form" :form-view="'modules.users.roles.admin.commerce.form'" />
    {{-- Modal de contraseña (si aplica) --}}
    @if (session('pawss'))
        <x-shared.modal title="Contraseña Vendedor" :value="session('pawss')" />
    @endif
    {{-- Scripts globales solo una vez --}}
    @once
        @push('stack-scripts')
            <script type="application/json" id="page-data">
                {!! json_encode($pageData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
            </script>
        @endpush
    @endonce
</x-layouts.layout-online>
