@extends('layouts.layout-online')
@section('title', $title)
@section('content')
    <div class="col-12">
        <div class="card">
            <x-shared.card-header :title="$title" />
            <div class="card-body">
                <button id="btn-{{ $name }}-insert" class="btn btn-sm btn-primary btn-{{ $name }}"
                    type="button" data-action="create">
                    <i class="fas fa-plus-square mr-1"></i>Vendedor
                </button>
                <div class="table-responsive mt-3">
                    <table class="table table-striped table-bordered my-2 w-100">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Telefono</th>
                                <th>Pais</th>
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
                                                    @endif @endforeach">
                                                <i class="fa-solid fa-coins mr-1"></i>Ver
                                            </a>
                                        @endif
                                    </td>
                                    <td>{{ $seller->commn }}%</td>
                                    <td>{{ $seller->status == 1 ? 'Activo' : 'Inactivo' }}</td>
                                    <td>
                                        @include('includes.btn-group', [
                                            'name' => $name,
                                            'dataId' => encrypt($seller->id),
                                            'buttons' => [
                                                (object) [
                                                    'dataType' => '2',
                                                    'color' => 'btn-primary',
                                                    'icon' => 'fas fa-edit',
                                                ],
                                                (object) [
                                                    'dataType' => '3',
                                                    'color' => 'btn-danger',
                                                    'icon' => 'fa-solid fa-trash-can',
                                                ],
                                                (object) [
                                                    'dataType' => '4',
                                                    'color' => 'btn-secondary',
                                                    'icon' => 'fa-solid fa-repeat',
                                                ],
                                                (object) [
                                                    'dataType' => '5',
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
    <input type="hidden" id="module-file" value="seller/form/sellerForm.ts">
    <input type="hidden" id="elem-id" value="{{ $name }}">
    @include('includes.modal-form', [
        'name' => $name,
        'size' => 'md',
        'dataForm' => view('modules.seller.form.body', ['countriesCommerce' => $countriesCommerce])->render(),
    ])
    @if (Session::has('message'))
        <x-modals.copy-info name="copy-passw" title="Contraseña Vendedor" :value="Session::get('message')" />
    @endif
@endsection
@section('scripts')
    <script src="{{ asset('js/select2.min.js') }}"></script>

    <script id="app-data" type="application/json">
        {!! json_encode([
            'countriesCommerce' => $countriesCommerce,
            'timeZones' => $timeZones,
            'coinsCommerce' => $coinsCommerce,
        ]) !!}
    </script>
    <script id="session-data" type="application/json">
        {
            "message": @json(Session::has('message')),
            "passw": @json(Session::get('passw'))
            "error": @json(Session::get('error')),
        }
    </script>
    @vite(['resources/js/helpers/formCrud', 'resources/js/views/modules/seller/form/sellerForm'])
@endsection
