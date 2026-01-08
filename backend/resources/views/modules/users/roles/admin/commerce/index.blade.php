<x-layouts.layout-auth :title="$title" loadSelect2="true">
    {{-- Tabla de comercios con Vue + Filtros --}}
    <x-shared.table-with-filter-vue
        :title="$title"
        :rows="$pageData['commerces']"
        :columns="['Nombre', 'País', 'Teléfono', 'Email', 'Status']"
        :row-keys="['name', 'Country.name', 'phone', 'User.email', 'status_label']"
        :filters="[
            [
                'key' => 'country_name',
                'label' => 'País',
                'placeholder' => 'Filtrar por país',
                'options' => $pageData['countries']->map(fn($c) => ['value' => $c->name, 'label' => $c->name])->unique('value')->values()->toArray()
            ],
            [
                'key' => 'status',
                'label' => 'Estado',
                'placeholder' => 'Filtrar por estado',
                'options' => [
                    ['value' => 'ACTIVE', 'label' => 'Activo'],
                    ['value' => 'INACTIVE', 'label' => 'Inactivo'],
                    ['value' => 'DELETED', 'label' => 'Eliminado']
                ]
            ]
        ]"
        module-id="commerce-form"
        context="commerce"
        table-id="commerce-table"
        search-placeholder="Buscar por nombre, email, teléfono..."
        :extra-buttons="[
            ['label' => '', 'icon' => 'fas fa-exchange-alt', 'class' => 'btn-secondary', 'show' => false, 'action' => 'changeStatus'],
            ['label' => '', 'icon' => 'fas fa-key', 'class' => 'btn-dark', 'show' => false, 'action' => 'resetPassword']
        ]"
    />

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
