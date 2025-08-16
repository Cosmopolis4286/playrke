@extends('layouts.layout-online')
<title>Cryptos - PlayRKE</title>
@section('content')
    <div class="row">
        <div class="col-12" style="max-width:535px; margin:0 auto;">
            <div class="card bg-rke">
                <div class="card-header">
                    <a href="{{ route('dashboard') }}" title="Regresar" style="position:relative;top:-1px;font-size:18px;">
                        <i class="fas fa-chevron-circle-left"></i>
                    </a>
                    <span style="font-size:20px;">Mi&nbspBinance&nbspPay</span>
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
                                            <th>Pay ID</th>
                                            <th>Email</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            @if ($BPay == null)
                                                <td></td>
                                                <td></td>
                                            @else
                                                <td>{{ $BPay->amount_id }}</td>
                                                <td>{{ $BPay->email }}</td>
                                            @endif
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @if ($BPay == null)
                        <div class="mt-2 text-center">
                            <a href="#" data-target="#b_pay" data-toggle="modal">
                                <button id="savePago" class="btn btn-primary profile-button" type="submit">
                                    <i class="fas fa-plus-square"></i>&nbspBinance Pay</button>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="b_pay" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog  modal-sm" role="document"
            style="max-width:300px; margin-top:5.6% !important; margin:0 auto;">
            <div class="modal-content">
                <div class="modal-header">
                    <img class="mt-1 mr-1" src="{{ asset('imgSystem/bpay.png') }}" style="width:15px;">
                    <b>Binance Pay ID</b>
                    <span class="sub"></span>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                @if ($BPay == null)
                    <div class="modal-body mb-n3">
                        <form action="{{ route('wallet.storeBPay') }}" method="POST" id="form_bpay">@csrf
                            <div class="row">
                                <div class="col-12">
                                    <input type="text" class="form-control" placeholder="Ingrese su Pay ID"
                                        id="pay_id" name="pay_id" required>
                                </div>
                                <div class="col-12 mt-3">
                                    <input name="email" id="email" type="email" class="form-control"
                                        placeholder="Ingrese email asociado">
                                </div>
                            </div>
                            <div class="mt-3 mb-n2 modal-footer">
                                <button type="submit" class="btn btn-primary profile-button" id="bt_canje">
                                    <i style="font-size:15px;" class="mr-1 fa-solid fa-floppy-disk"></i>
                                </button>
                            </div>
                        </form>
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
                        digits: true,
                        minlength: 9,
                    },
                    email: {
                        required: true,
                        email: true
                    },

                },
                messages: {
                    pay_id: "Se requieren 9 digitos.",
                    email: "Ingrese un email valido",
                }
            });
        });
    </script>
@endsection
