<x-layouts.layout-online :title="$title" :loadSelect2="true">
    <x-shared.table-with-selection 
        title="Commerces" 
        :rows="$commerces" 
        :columns="['Nombre', 'País', 'Teléfono', 'Email', 'Status']"
        :row-keys="['name', 'Country.name', 'phone', 'User.email', 'status_label']"
        view-id="commerce-btns"
        context="commerce" 
    />
    @once
        @push('stack-scripts')
            <script type="module">
                window.appData = {
                    coins: @json($coins),
                    countries: @json($countries),
                    games: @json($games),
                    moduleFile: 'views/modules/commerce/form/commerceForm.ts',
                    timeZones: @json($timeZones)
                };
            </script>
        @endpush
    @endonce
</x-layouts.layout-online>
