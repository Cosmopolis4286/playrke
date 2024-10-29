@extends('layouts.app_rke')
@section('title', $title)
{{-- @section('content')
    <div class="col-md-8 m-auto">
        <div class="card">
            @include('includes.card-header')
            <div class="card-body">
                <button type="button" class="btn-{{ $name }} btn btn-sm btn-success" data-type="1">
                    <i class="fas fa-plus-square mr-1"></i>{{ $paymentType }}</button>
                <div class="table-responsive mt-3">
                    <table class="table table-striped table-bordered my-2 w-100">
                        <thead>
                            <tr>
                                <th>Banco</th>
                                <th>Tipo</th>
                                <th>Chave</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pixs as $pix)
                                <tr>
                                    <td>{{ $pix->bank }}</td>
                                    <td>{{ $pix->ChavePix->value }}</td>
                                    <td>{{ $pix->pix_key_value }}</td>
                                    <td>
                                        @include('includes.btn-group', [
                                            'name' => $name,
                                            'dataId' => encrypt($pix->id),
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
        'name' => $name,
        'dataForm' => view('modules.all.directory.br.form')->render(),
    ])
    @include('includes.form-other-options')
@endsection --}}
@section('scripts')
    <script>
        const name = '{{ $name }}';
        const paymentType = '{{ $paymentType }}';
    </script>
@endsection
