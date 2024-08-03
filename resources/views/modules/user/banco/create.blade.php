@extends('layouts.app_rke')
<title>PlayRKE - Añadir Cuenta Bancaria</title>
@section('content')
    <div class="row">
        <div class="col-md-5" style="margin:0 auto;">
            <form action="{{ route('banco.store') }}" method="POST">@csrf
                <div class="card bg-rke">
                    <div class="card-header" id="headingOne">
                        <div class="sub_ti-tle">
                            <a href="{{ route('banco.index') }}" title="Regresar"
                                style="position:relative;top:-1px;font-size:18px;">
                                <i class="fas fa-chevron-circle-left"></i>
                            </a>
                            <span style="margin-left:7px; font-size:20px;">Añadir Cuenta Bancaria</span>
                        </div>
                    </div>
                    <div class="card-body mt-n3">
                        <div class="row">
                            @if ($account != null)
                                <div class="col-6">
                                    <label class="lb_in">RUT</label>
                                    <input id="rut" type="text" value="{{ $account }}" name="rut"
                                        class="form-control_d">
                                </div>
                            @else
                                <div class="col-6">
                                    <label class="lb_in">RUT</label>
                                    <input id="rut" type="text" name="rut" class="form-control" required>
                                </div>
                            @endif
                            <div class="col-6">
                                <label class="lb_in">Banco</label>
                                <select name="bank" id="bank" class="form-control" required>
                                    <option value="">Seleccione el banco</option>
                                    @foreach ($banks as $bank)
                                        <option value="{{ $bank->id }}">{{ $bank->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="lb_in">Tipo de Cuenta</label>
                                <select name="type_account" id="type_account" class="form-control" required>
                                    <option value="">Seleccione tipo</option>
                                    <option value="1">Cuenta Corriente</option>
                                    <option value="2">Cuenta Vista</option>
                                    <option value="3">Cuenta Ahorro</option>
                                    <option value="4" id="cnt_rut" style="display: none">Cuenta RUT</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="lb_in">Numero de Cuenta</label>
                                <input id="num_account" type="text" name="num_account" class="form-control"
                                    value="" required>
                            </div>
                            <div class="col-6">
                                <label class="lb_in">Email</label>
                                <input id="cedula" type="email" name="email" class="form-control"
                                    placeholder="Opcional">
                            </div>
                            <div class="save_pf col-6" style="margin:0 auto">
                                <label class="lb_in">&nbsp</label>
                                <button id="guardar" class="form-control btn btn-primary profile-button"
                                    type="submit">GUARDAR</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        $(document).ready(function() {
            $("#rut").mask({
                mask: "999999999-[9|K|k]"
            });

            $("#bank").change(function() {
                var bank = $("#bank").val();
                if (bank == 1) {
                    $("#cnt_rut").show();
                } else {
                    $("#cnt_rut").hide();
                }
            });

            $("#num_account").on('input', function() {
                $(this).val($(this).val().replace(/[^0-9]/g, ''));
                if (this.value.length > 20)
                    this.value = this.value.slice(0, 20);
            });
        });
    </script>
@endsection
