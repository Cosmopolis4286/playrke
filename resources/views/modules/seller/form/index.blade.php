{{-- resources/views/seller/form/index.blade.php --}}

<x-layouts.layout-online :title="$title" :loadSelect2="true">
    {{-- Contenido principal --}}
    <div class="col-12">
        <div class="card">
            <x-shared.card-header :title="$title" />
            <div class="card-body">
                <button id="btn-{{ $viewId }}-insert" class="btn btn-sm btn-primary btn-{{ $viewId }}"
                    type="button" data-action="create">
                    <i class="fas fa-plus-square mr-1"></i>Vendedor
                </button>

                <div class="table-responsive mt-3">
                    <table class="table table-striped table-bordered my-2 w-100">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Teléfono</th>
                                <th>País</th>
                                <th>Moneda</th>
                                <th>%Comn</th>
                                <th>Status</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sellers as $seller)
                                <tr>
                                    <td>{{ $seller->name }}</td>
                                    <td>{{ $seller->phone }}</td>
                                    <td>{{ $seller->Country->name }}</td>
                                    <td>
                                        {{ $seller->Coin->short_name }} ({{ $seller->Coin->symbol }})
                                        @if ($seller->CountriesCoins->count() > 1)
                                            <a tabindex="0" class="badge btn-sm btn-primary" role="button"
                                                data-toggle="popover" data-trigger="focus" title="Otras Monedas"
                                                data-content="
                                                    @foreach ($seller->CountriesCoins as $item)
                                                        @if ($item->coin != $seller->coin)
                                                            <span>{{ $item->Coin->long_name }} ({{ $item->Coin->symbol }})</span><br>
                                                        @endif @endforeach
                                                ">
                                                <i class="fa-solid fa-coins mr-1"></i>Ver
                                            </a>
                                        @endif
                                    </td>
                                    <td>{{ $seller->commn }}%</td>
                                    <td>{{ $seller->status == 1 ? 'Activo' : 'Inactivo' }}</td>
                                    <td>
                                        @include('includes.btn-group', [
                                            'viewId' => $viewId,
                                            'dataId' => encrypt($seller->id),
                                            'buttons' => [
                                                (object) [
                                                    'action' => 'edit',
                                                    'color' => 'btn-primary',
                                                    'icon' => 'fas fa-edit',
                                                ],
                                                (object) [
                                                    'action' => 'delete',
                                                    'color' => 'btn-danger',
                                                    'icon' => 'fa-solid fa-trash-can',
                                                ],
                                                (object) [
                                                    'action' => '4',
                                                    'color' => 'btn-secondary',
                                                    'icon' => 'fa-solid fa-repeat',
                                                ],
                                                (object) [
                                                    'action' => '5',
                                                    'color' => 'bg-navy',
                                                    'icon' => 'fa-solid fa-lock',
                                                ],
                                            ],
                                        ])
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @include('includes.modal-form', [
        'viewId' => $viewId,
        'size' => 'md',
        'dataForm' => view('modules.seller.form.body')->render(),
    ])
    @if (Session::has('pawss'))
        <x-modals.copy-info name="copy-passw" title="Contraseña Vendedor" :value="Session::get('pawss')" />
    @endif
    {{-- Scripts específicos de esta vista --}}
    @push('stack-scripts')
        <script>
            window.appData = {
                coins: @json($coins),
                countries: @json($countries),
                moduleFile: 'seller/form/sellerForm.ts',
                timeZones: @json($timeZones)
            };
        </script>
        {{-- Carga principal del CRUD --}}
        @vite('resources/js/helpers/formCrud.ts')
    @endpush
</x-layout-online>
