@extends('layouts.app_rke')
<title>Cryptos - PlayRKE</title>
@section('content')
    <div class="row">
        <div class="col-12" style="max-width:565px; margin:0 auto;">
            <div class="card bg-rke">
                <div class="card-header">
                    <a href="{{ route('dashboard') }}" title="Regresar" style="position:relative;top:-1px;font-size:18px;">
                        <i class="fas fa-chevron-circle-left"></i>
                    </a>
                    <span style="font-size:20px;">Wallet Tether USDT&nbsp <a href="" data-target="#b_pay"
                            data-toggle="modal" style="font-size:16px;">Mi&nbspBinance&nbspPay</a></span>
                    @if (Session::has('mensaje'))
                        <div class="alert alert-alert-info justify-content-start text-start" role="alert">
                            {!! Session::get('mensaje') !!}
                        </div>
                    @endif
                </div>
                <div class="card-body mt-n1">
                    <div class="row">
                        <div class="col-12">
                           <div class="table-responsive mt-3">
                    <table class="table table-striped table-bordered my-2 w-100">
                                    style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Moneda</th>
                                            <th>Red</th>
                                            <th>Dirección</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($wallets as $wallet)
                                            <tr>
                                                <td>{{ $wallet->crypto_mon }}</td>
                                                <td>{{ $wallet->crypto_red }}</td>
                                                <td>{{ $wallet->crypto_direcc }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @if (Count($list_wallet) != 0)
                    @else
                        <div class="mt-2 text-center">
                            <a href="{{ route('wallet.create') }}" title="Añadir Pago Móvil">
                                <button id="savePago" class="btn btn-primary profile-button" type="submit"><i
                                        class="fas fa-plus-square"></i>&nbspWallet USDT</button></a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="b_pay" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog  modal-sm" role="document"
            style="max-width:245px; margin-top:5.6% !important; margin:0 auto;">
            <div class="modal-content bg-rke1">
                <div class="modal-header">
                    <img class="mt-1 mr-1" src="{{ asset('imgSystem/bpay.png') }}" style="width:15px;">
                    <b>Binance Pay ID</b>
                    <span class="sub"></span>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                @if (Count($BPay) == 0)
                    <div class="modal-body mb-n3">
                        <form action="{{ route('wallet.storeBPay') }}" method="POST" id="form_bpay">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <input type="text" class="form-control" placeholder="Ingrese su Pay ID" id="pay_id"
                                name="pay_id">
                            <span data-valmsg-for="pay_id" data-valmsg-replace="true"
                                style="margin-left: 75px; color: red;">
                                <div class="mb-n2 modal-footer">
                                    <button type="submit" class="mt-n1 btn btn-primary profile-button" id="bt_canje">
                                        <i style="font-size:15px;" class="mb-1 mr-1 fa-solid fa-floppy-disk"></i>
                                    </button>
                                </div>
                        </form>
                    </div>
                @else
                    <div class="modal-body mb-n1">
                        <input type="text" class="form-control_d" value="{{ $BPay[0]->amount_id }}" disabled>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class=" btn btn-secondary profile-button" id="bt_canje"
                            data-dismiss="modal">Cerrar</button>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        $().ready(function() {
            $("#pay_id").on('input', function(evt) {
                $(this).val($(this).val().replace(/[^0-9]+/g, ""));
                if (this.value.length > 9)
                    this.value = this.value.slice(0, 9);
            });
            $("#form_bpay").validate({
                rules: {
                    pay_id: {
                        required: true,
                        minlength: 9,
                        maxlength: 15
                    },

                },
                messages: {
                    pay_id: "Se requieren 9 digitos.",

                }
            });
        });
    </script>
@endsection
