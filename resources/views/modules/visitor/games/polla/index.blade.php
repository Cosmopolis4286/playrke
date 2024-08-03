@extends('layouts.app_rke')
@section('title', $title)
@section('content')
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <a href="{{ route('dashboard') }}" title="Regresar" style="position:relative;top:-1px;font-size:18px;">
                    <i class="fas fa-chevron-circle-left"></i>
                </a>
                <span style="margin-left:7px; font-size:20px;">{{ $title }}</span>
                <div id="loader-sm" class="spinner-border spinner-border-sm float-right text-primary" role="status"
                    style="display: none">
                    <span class="sr-only">Loading...</span>
                </div>
            </div>
            <div class="card-body">
                <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#mdlInsertCurrency">
                    <i class="fas fa-plus-square"></i> Añadir Moneda
                </button>
                <div class="table-responsive mt-3">
                    <table class="table table-striped table-bordered">
                        <h6>Valores en base a Un Dólar (1 USD)</h6>
                        <thead>
                            <tr>
                                <th>Pais</th>
                                <th>Moneda</th>
                                <th>Cambio</th>
                                <th>Status</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($CommerceExchangeRate as $cup)
                                <tr>
                                    <td>{{ $cup->Coin->Country->name }}</td>
                                    <td>{{ $cup->name_coin }}</td>
                                    <td>{{ $cup->Coin->name }} {{ $cup->value }}</td>
                                    <td>{{ $cup->status == 1 ? 'Activo' : 'No/Disp' }}</td>
                                    <td>
                                        <div class="btn-group btn-group-toggle">
                                            <button class="btn btn-sm btn-primary btnUpdateCommerceExchangeRate"
                                                data-coin="{{ $cup->coin }}" title="Editar CommerceExchangeRate"
                                                data-namecoin="{{ $cup->name_coin }}" data-valcoin="{{ $cup->value }}">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            @if (Auth::user()->user_type == 1)
                                                <button class="btn btn-sm btn-secondary" data-id="{{ $cup->id }}"
                                                    title="Cambiar Status">
                                                    <i class="fa-solid fa-arrows-rotate"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger btnDlt" data-id="{{ $cup->id }}"
                                                    title="Eliminar">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    {{-- Modal --}}
    <div class="modal fade" id="mdlInsertCurrency" tabindex="-1" role="dialog" aria-labelledby="mdlInsertCurrencyLabel"
        aria-hidden="true" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="mdlInsertCurrencyLabel">Añadir Moneda</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formCreateCurrencyCup" action="{{ route('commerce.createCommerceExchangeRate') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-sm-6">
                                <label class="lb_in">Divisa</label>
                                <input type="text" id="nameCoin" class="form-control" name="nameCoin" />
                            </div>
                            <div class="col-sm-6">
                                <label class="lb_in">Cambio</label>
                                <input type="text" id="valChange" class="form-control" name="valChange" />
                            </div>
                            <div class="col-12 mt-3 mb-n1">
                                <div class="alert alert-danger fade show alertCurrency text-center hide-elem" role="alert">
                                    Un Dólar (USD) es igual a:
                                    <h6 id="txValCurrency"></h6>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="button" id="btnInsertCurrency" class="btn btn-sm btn-primary">Guardar</button>
                </div>
            </div>
        </div>
    </div>
    {{-- Modal --}}
    <div class="modal fade" id="mdlUpdateCurrency" tabindex="-1" role="dialog" aria-labelledby="mdlUpdatedCurrencyLabel"
        aria-hidden="true" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="mdlUpdatedCurrencyLabel">Editar Moneda</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formUpdateCurrencyCup" action="{{ route('commerce.updateCommerceExchangeRate') }}" method="POST">
                        @csrf @method('PUT')
                        <div class="row">
                            <div class="col-sm-7">
                                <label class="lb_in">Divisa</label>
                                <div class="form-control" id="txNameCoinEdit"></div>
                                <input type="hidden" name="coin" id="coinEdit">
                            </div>
                            <div class="col-sm-5">
                                <label class="lb_in">Cambio</label>
                                <input type="text" id="valChangeEdit" class="form-control" name="valChange" />
                            </div>
                            <div class="col-12 mt-3 mb-n1">
                                <div class="alert alert-danger fade show alertCurrency text-center hide-elem" role="alert">
                                    Un Dólar (USD) es igual a:
                                    <h6 id="txValCurrencyEdit"></h6>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="button" id="btnUpdatedCurrency" class="btn btn-sm btn-primary">Guardar</button>
                </div>
            </div>
        </div>
    </div>
    <form id="delete" action="{{ route('commerce.deleteCommerceExchangeRate') }}" method="post">@csrf @method('PUT')
        <input type="hidden" name="id" id="idDlt">
    </form>
@endsection
@section('scripts')
    <script>
        $(document).on("change", "#valChange", function() {
            const nameCoin = $("#nameCoin").val(),
                valChange = $("#valChange").val();

            if (nameCoin) {
                $("#txValCurrency").text(`${valChange} ${nameCoin}`);
                $(".alertCurrency").show();
            }
        });

        $(document).on("change", "#valChangeEdit", function() {
            const nameCoin = $("#txNameCoinEdit").text(),
                valChange = $("#valChangeEdit").val();

            if (valChange) {
                $("#txValCurrencyEdit").text(`${valChange} ${nameCoin}`);
                $(".alertCurrency").show();
            }
        });

        $(document).on("click", "#btnInsertCurrency", function() {
            if ($("#nameCoin").val() && $("#valChange").val()) {
                $("#formCreateCurrencyCup").submit();
            } else {
                showToast('warning', 'Ingresa la informacion', '');
            }
        });

        $(document).on("click", "#btnUpdatedCurrency", function() {
            if ($("#coinEdit").val() && $("#valChangeEdit").val()) {
                $("#formUpdateCurrencyCup").submit()
            }
        });

        $(document).on("click", ".btnUpdateCommerceExchangeRate", function() {
            const data = $(this).data(),
                nameCoin = data.namecoin,
                valChange = data.valcoin;

            $("#txNameCoinEdit").text(nameCoin);
            $("#coinEdit").val(data.coin);
            $("#valChangeEdit").val(valChange);
            $("#txValCurrencyEdit").text(`${valChange} ${nameCoin}`);
            $(".alertCurrency").show();
            $("#mdlUpdateCurrency").modal('show');
        });

        $(document).on("click", ".btnDlt", function() {
            $("#idDlt").val($(this).data('id'));
            Swal.fire({
                title: '¿Confirmas está acción?',
                text: "Esta Moneda será eliminada definitivamente",
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#delete').submit();
                } else if (result.isDenied) {
                    Swal.fire('Los cambios no se guardaron', '', 'info')
                }
            })
        });
    </script>
@endsection
