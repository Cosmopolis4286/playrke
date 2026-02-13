@extends('layouts.auth-layout')
@section('title', $title)
@section('content')
    <div class="col-12">
        <div class="card">
            @include('includes.card-header')
            <div class="card-body">
                @include('includes.currency-tabs')
                @include('includes.date-filter-buttons')
                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade show active">
                        <div class="table-responsive mt-3">
                            <table class="table table-striped table-bordered my-2 w-100">
                                <thead>
                                    <tr>
                                        <th>Sala</th>
                                        <th>Vendedor</th>
                                        <th>Moneda</th>
                                        <th>#CTN</th>
                                        <th>Venta</th>
                                        <th>Creación</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $totalNotPaid = 0; @endphp
                                    @foreach ($ctnsSold as $ctn)
                                        @php
                                            $totalCtns = $ctn->sum('cards_purchased');
                                            if ($ctn->status != 2) {
                                                $totalNotPaid += $ctn->ctns * $ctn->sale_amount;
                                            }
                                            $saleReal = $ctn->Room->price * $ctn->ctns * $ctn->exchange_rate;
                                        @endphp
                                        <tr>
                                            <td class="align-middle">
                                                <a class="badge bg-navy" title="Ver Reporte"
                                                    href="{{ route('bingo.reportSalesByRoom', [
                                                        'room' => $ctn->Room->encrypted,
                                                        'currency' => encrypt($currency),
                                                        'dateFilter' => $dateFilter,
                                                    ]) }}">
                                                    {{ $ctn->Room->encrypted }}
                                                </a>
                                            </td>
                                            <td>{{ $ctn->Seller->name }}</td>
                                            <td>{{ $ctn->Currency->description }} ({{ $currencySym }})</td>
                                            <td>{{ $ctn->ctns }}</td>
                                            <td>{{ $currencySym }} {{ $ctn->sale_amount }}</td>
                                            <td>
                                                {{ $ctn->created_at->format('d/m/y H:i:s') }}
                                                <small>GMT-4</small>
                                            </td>
                                            <td>
                                                @if ($ctn->status == 0)
                                                    <span class="badge bg-danger">Pendiente</span>
                                                @elseif($ctn->status == 1)
                                                    <button class="btn btn-primary btn-sm btn-confirm-payment"
                                                        data-seller="{{ $ctn->Seller->name }}"
                                                        data-lot="{{ encrypt($ctn->id) }}"
                                                        data-currency-symbol="{{ $currencySym }}"
                                                        data-sale_ctns="{{ $ctn->sale_amount }}"
                                                        data-proof="{{ $ctn->Payment->payment_proof }}">
                                                        Confirmar Pago
                                                    </button>
                                                @elseif($ctn->status == 2)
                                                    <a href="{{ route('image.show-image-payment-ctns', ['filename' => $ctn->Payment->payment_proof]) }}"
                                                        title="Ver Comprobante" target="_blank">
                                                        <span class="badge bg-success">Confirmado</span>
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-right"><b>Total:</b></td>
                                        <td>{{ $ctnsSold->sum('cards_purchased') }}</td>
                                        <td>{{ $currencySym }} {{ $ctnsSold->sum('sale_amount') }}</td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-payment" tabindex="-1" role="dialog" aria-labelledby="modal-paymentLabel"
        aria-hidden="true" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-paymentLabel">Confirmar Pago</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="cont-payment-method" class="mt-n1 d-flex flex-column">
                        <div id="seller-name"></div>
                        <span id="sale-ctns" class="sale-ctns"></span>
                    </div>
                    <a id="show-payment-proof" target="_blank">
                        <h6 class="mt-2 btn btn-sm bg-navy"><i class="mr-1 fa-solid fa-eye"></i>Comprobante</h6>
                    </a>
                    <form action="{{ route('commerce.ctnsLotPayment') }}" id="form-confirm-payment" method="POST">
                        @csrf @method('PUT')
                        <input type="hidden" id="lot" name="lot">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-sm btn-primary" id="btn-confirm">Confirmar</button>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        $(document).on("click", ".btn-confirm-payment", function() {
            const data = $(this).data();
            $('#lot').val(data.lot);
            $('#seller-name').html(`<b>Vendedor: </b>${data.seller}<br>`);
            $('#sale-ctns').html(`<b>Venta: </b>${data.currency-symbol} ${data.sale_ctns}`);

            $('#show-payment-proof').attr('href', route('image.show-image-payment-ctns', filename: data.proof));

            $('#modal-payment').modal('show');
        });

        $(document).on("click", "#btn-confirm", function() {
            $('#form-confirm-payment').submit();
        });
    </script>
@endsection
