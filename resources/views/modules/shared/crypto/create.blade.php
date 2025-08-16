@extends('layouts.layout-online')
<title>Añadir Wallet - PlayRKE</title>
@section('content')
    <div class="row">
        <div class="col-sm-7 col-md-6 col-lg-5" style="margin:0 auto;">
            <form action="{{ route('wallet.store') }}" method="POST">@csrf
                <div class="card bg-rke">
                    <div class="card-header" id="headingOne">
                        <div class="sub_ti-tle">
                            <a href="{{ route('wallet.index') }}" title="Regresar"
                                style="position:relative;top:-1px;font-size:18px;">
                                <i class="fas fa-chevron-circle-left"></i>
                            </a>
                            <span style="margin-left:7px; font-size:20px;">Añadir Wallet USDT</span>
                        </div>
                    </div>
                    <div class="card-body mt-n3">
                        <div class="row">
                            <div class="col-sm-12 col-lg-6">
                                <label class="lb_in">Moneda</label>
                                <input id="crypto_mon" type="text" name="crypto_mon" class="form-control_d"
                                    value="Tether (USDT)">
                            </div>
                            <div class="col-sm-12 col-lg-6">
                                <label class="lb_in">Red de retiro</label>
                                <input id="crypto_red" type="text" name="crypto_red" class="form-control_d"
                                    value="Tron (TRC20)">
                            </div>
                            <div class="col-sm-12">
                                <label class="lb_in">Dirección</label>
                                <input id="crypto_direcc" type="text" placeholder="Introduce la dirección"
                                    name="crypto_direcc" class="form-control" value="">
                            </div>
                            <div class="mt-n2 save_pf col-sm-12">
                                <label class="lb_in">&nbsp</label>
                                <div>
                                    @if (Count($list_wallet) != 0)
                                        <div class="col-12 mb-1 alert alert-danger text-center">
                                            <strong>Ya registró una Wallet</strong>
                                        </div>
                                    @else
                                        <button id="guardar" class="btn btn-primary profile-button"
                                            type="submit">Guardar</button>
                                    @endif
                                </div>
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
            $("#crypto_direcc").on('input', function(evt) {
                $(this).val($(this).val().replace(/[^A-Za-z0-9]+/g, ""));
                if (this.value.length > 34)
                    this.value = this.value.slice(0, 34);
            });
        });
    </script>
@endsection
