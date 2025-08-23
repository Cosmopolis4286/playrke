@extends('layouts.layout-online')
@section('title', $title)
@section('content')
    <div class="col-12">
        <div class="card">
            @include('includes.card-header')
            <div class="card-body">
                @include('includes.coin-tabs')
                @include('includes.date-filter-buttons')
                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade show active">
                        @if ($ctnsSold->count() > 1 && $pendingCtns > 1)
                            <button type="button" id="btn-notify-all-ctns" class="btn btn-sm btn-secondary mt-2">
                                Notificar Todo
                            </button>
                        @endif
                        <div class="table-responsive mt-3">
                            <table class="table table-striped table-bordered my-2 w-100">
                                <thead>
                                    <tr>
                                        <th>Sala</th>
                                        <th>Jugador</th>
                                        <th>País</th>
                                        <th>Moneda</th>
                                        <th>#CTN</th>
                                        <th>Venta</th>
                                        <th>Creación</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($ctnsSold as $ctn)
                                        <tr>
                                            <td class="align-middle">
                                                <a class="badge bg-navy" title="Ver Reporte"
                                                    href="{{ route('bingo.reportSalesByRoom', ['room' => $ctn->Room->encrypted, 'coin' => encrypt($coin), 'dateFilter' => $dateFilter]) }}">
                                                    {{ $ctn->Room->encrypted }}
                                                </a>
                                            </td>
                                            <td>{{ $ctn->PlayerLink->name }} {{ $ctn->PlayerLink->lastname }}</td>
                                            <td>{{ $ctn->PlayerLink->Country->name }}</td>
                                            <td>{{ $coinData->short_name }} ({{ $coinData->iso_code }})</td>
                                            <td>{{ $ctn->ctns }}</td>
                                            <td>{{ $coinData->iso_code }} {{ $ctn->sale_amount }}</td>
                                            <td>{{ $ctn->created_at->format('d/m/y H:i:s') }}<small
                                                    class="ml-1">GMT-4</small></td>
                                            <td>
                                                @if ($ctn->status == 0)
                                                    <button type="button"
                                                        class="btn btn-danger btn-sm btn-{{ $moduleId }}"
                                                        title="Notificar Pago" data-room="{{ $ctn->Room->encrypted }}"
                                                        data-idlote="{{ $ctn->id }}"
                                                        data-amount="{{ number_format($ctn->sale_amount, 2) }}">
                                                        Pendiente
                                                    </button>
                                                @elseif($ctn->status == 1)
                                                    <a href="{{ route('image.show-image-payment-ctns', ['filename' => $ctn->Payment->payment_proof]) }}"
                                                        title="Ver Comprobante" target="_blank"><span
                                                            class="badge bg-navy">Notificado</span></a>
                                                @elseif($ctn->status == 2)
                                                    <a href="{{ route('image.show-image-payment-ctns', ['filename' => $ctn->Payment->payment_proof]) }}"
                                                        title="Ver Comprobante" target="_blank"><span
                                                            class="badge bg-success">Confirmado</span></a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                @if (count($ctnsSold))
                                    <tfoot>
                                        <tr>
                                            <td colspan="4" class="text-right"><b>Total:</b></td>
                                            <td>{{ $ctnsSold->sum('ctns') }}</td>
                                            <td>{{ $coinData->iso_code }} {{ $ctnsSold->sum('sale_amount') }}</td>
                                            <td colspan="2"></td>
                                        </tr>
                                    </tfoot>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>
                @include('includes.modal-payment-info', [
                    'moduleId' => $moduleId,
                    'title' => 'Notificar Pago',
                    'formId' => 'ctn-payment',
                    'formRoute' => route('seller.ctnsLotPayment'),
                ])
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @vite(['resources/js/helpers/switch-payment-method.js', 'resources/js/helpers/payment-confirm-button.js'])
    <script>
        const name = '{{ $moduleId }}';
        const coin = '{{ encrypt($coin) }}';
        const coinName = '{{ $coinData->short_name }}';
        const allPendingCtnSale = '{{ $allPendingCtnSale }}';
        const commerceName = '{{ $commerceName }}';
        let allNotifyCtns = false;
        let amountSent;

        function getPaymentCommerceDetails() {
            $.getJSON(route('seller.getPaymentCommerceDetails', coin))
                .done(data => {
                    if (data.error) {
                        showToast(data.error, 'info');
                    } else {
                        if (!allNotifyCtns) {
                            $("#sent-amount").text(`${data.coin} ${amountSent}`);
                        } else {
                            $("#notify-all").show();
                            $("#sent-amount").text(`${coinName} ${allPendingCtnSale}`);
                            $("#notify-all-ctns").val(1);
                        }

                        $("#commerce-name").text(commerceName);
                        switchPaymentMethod(data.code, data);
                        $(`#modal-${name}`).modal('show');
                    }
                })
                .fail((jqXHR, textStatus, errorThrown) => {
                    console.error('Error:', textStatus, errorThrown);
                });
        }

        $(document).on("click", "#btn-notify-all-ctns", function() {
            if (allPendingCtnSale > 0) {
                allNotifyCtns = true;
                getPaymentCommerceDetails();
            }
        });

        $(document).on("click", `.btn-${name}`, function() {
            const data = $(this).data();
            $("#lot").val(data.idlote);
            amountSent = data.amount;
            getPaymentCommerceDetails();
        });
    </script>
@endsection
