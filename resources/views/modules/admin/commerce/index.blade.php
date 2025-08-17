<x-layouts.layout-online :title="$title" :loadSelect2="true">
    <div class="col-12">
        <div class="card">
            <x-shared.card-header :title="$title" />
            <div class="card-body">
                <button id="btn-{{ $viewId }}-insert" class="btn btn-sm btn-primary btn-{{ $viewId }}"
                    type="button" data-action="create">
                    <i class="fas fa-plus-square mr-1"></i>Comercio
                </button>
                <div class="table-responsive mt-3">
                    <table class="table table-striped table-bordered my-2 w-100">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Pais</th>
                                <th>Telefono</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Accion</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($commerces as $item)
                                <tr>
                                    <td>{{ $item->name }}</td>
                                    <td>{{ $item->Country->name }}</td>
                                    <td>{{ $item->phone }}</td>
                                    <td>{{ $item->User->email }}</td>
                                    <td>{{ $item->status == 1 ? 'Activo' : 'Inactivo' }}</td>
                                    <td>
                                        <x-btn-group :btnGroupId="$viewId" :dataId="encrypt($item->id)" :buttons="$item->extraButtons" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <x-shared.modal :id="$viewId" :formView="'modules.admin.commerce.form'" />
    @if (Session::has('pawss'))
        <x-shared.modal title="Contraseña Vendedor" :value="Session::get('pawss')" />
    @endif
    @push('stack-scripts')
        <script>
            window.appData = {
                coins: @json($coins),
                countries: @json($countries),
                moduleFile: 'commerce/form/commerceForm.ts',
                timeZones: @json($timeZones)
            };
        </script>
        @vite('resources/js/helpers/formCrud.ts')
    @endpush
    </x-layout-online>
