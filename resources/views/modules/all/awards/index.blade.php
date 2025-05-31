@extends('layouts.app_rke')
@section('title', $title)
@section('content')
    <div class="col-12">
        <div class="card">
            @include('includes.card-header')
            <div class="card-body">
                <button type="button" class="btn-{{ $name }} btn btn-sm btn-success" data-type="1"><i
                        class="fas fa-plus-square"></i> Añadir</button>
                <div class="table-responsive mt-2">
                    <table class="table table-striped table-bordered my-2 w-100">
                        <thead>
                            <tr>
                                <th>Moneda</th>
                                <th>Cambio</th>
                                <th>%</th>
                                <th>Status</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($awards as $item)
                                <tr></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @include('includes.modal-form', [
        'name' => $name,
        'dataForm' => view('modules.all.awards.form', ['creator' => $creator])->render(),
    ])
    @include('includes.form-other-options')
@endsection
@section('scripts')
    <script>
        const name = '{{ $name }}';

        $(document).on("click", `.btn-${name}`, function() {
            formCRUD($(this), name, 'Tasa de cambio', otherCasesMap)
        });

        $(document).on("click", "#btn-confirm", function() {
            submitForm(name, [coin.attr('id'), exchangeRate.attr('id')]);
        });

        const otherCasesMap = {
            4: function(encryptedId) {
                showToastOptionBtns(
                    'Cambiar el status del item',
                    'question',
                    'Sí, cambiar',
                    '#form-other-options',
                    route(`${name}.change-status`, {
                        id: encryptedId
                    })
                );
            }
        };
    </script>
@endsection
