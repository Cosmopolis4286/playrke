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
                                        <th>#RD</th>
                                        <th>Jugador</th>
                                        <th>Pais</th>
                                        <th>Moneda</th>
                                        <th>Premio</th>
                                        <th>Tipo</th>
                                        <th>Status</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($winners as $win)
                                        <tr>
                                            <td class="align-middle">
                                                <a class="badge bg-navy" title="Ver Reporte"
                                                    href="{{ route('bingo.reportSalesByRoomWinners', [
                                                        'room' => $win->Room->encrypted,
                                                        'currency' => encrypt($currency),
                                                    ]) }}">
                                                    {{ $win->Room->encrypted }}
                                                </a>
                                            </td>
                                            <td>{{ $win->round }}</td>
                                            <td>{{ $win->ExternalPlayers->name }}
                                                {{ $win->ExternalPlayers->lastname }}</td>
                                            <td>{{ $win->ExternalPlayers->Country->name }}</td>
                                            <td>{{ $currencyData->name }} ({{ $currencyData->iso_code }})</td>
                                            <td>{{ $currencyData->iso_code }} {{ $win->amount }}</td>
                                            <td>{{ $win->prize_type_description }}</td>
                                            <td class="align-middle">
                                                @if ($win->status === 'WT')
                                                    <span class="badge bg-secondary">Pendiente</span>
                                                @elseif($win->status === 'CS')
                                                    <a href="{{ route('image.show-image-payment-prize-ctns', ['filename' => $win->Payments[0]->payment_proof]) }}"
                                                        title="Ver Comprobante" target="_blank">
                                                        <span class="badge bg-primary">Por Pagar</span></a>
                                                @else
                                                    <a href="{{ route('image.show-image-payment-prize-ctns', ['filename' => $win->Payments[1]->payment_proof]) }}"
                                                        title="Ver Comprobante" target="_blank">
                                                        <span class="badge bg-success">Pagado</span></a>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group" aria-label="Acciones">
                                                    @if ($win->status === 'CS')
                                                        <button class="btn btn-sm bg-danger notify-ctn-payment"
                                                            title="Notificar Pago" data-currency-symbol="{{ $win->currencySymbol }}"
                                                            data-prize_amount="{{ $win->amount }}"
                                                            data-ctn='{{ encrypt($win->ctn) }}'>
                                                            <i class="fa-solid fa-circle-dollar-to-slot mr-1"></i>
                                                            Notificar
                                                        </button>
                                                    @endif
                                                    <a class="btn btn-sm bg-primary show-ctns" target="_blank"
                                                        href="{{ route('bingo.game', ['player' => $win->ExternalPlayers->link]) }}">
                                                        <i class="fa-solid fa-eye"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                @if (count($winners))
                                    <tfoot>
                                        <tr>
                                            <td colspan="5" class="text-right"><b>Total:</b></td>
                                            <td>{{ $currencyData->iso_code }} {{ $winners->sum('amount') }}</td>
                                            <td colspan="3"></td>
                                        </tr>
                                    </tfoot>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="info-player" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable" style="max-width:370px" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5>Informacion Jugador</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                </div>
                <div class="modal-footer d-flex">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
            </form>
        </div>
    </div>
    @include('includes.modal-payment-info', [
        'moduleId' => $moduleId,
        'title' => 'Notificar Pago',
        'formId' => 'ctn-payment',
        'formRoute' => route('bingo.notifyPaymentPrizeWinningCtn'),
    ])
@endsection
@section('scripts')
    @vite('resources/js/helpers/payment-confirm-button.js')
    <script>
        const name = '{{ $moduleId }}';

        $(document).on("click", ".notify-ctn-payment", function() {
            const data = $(this).data();
            const ctnId = data.ctn;
            const prizeAmount = data.prize_amount;

            $('#text-bank-data').addClass('hide-elem');
            $('#send-receive').text('Enviaste ');
            $('#ctn').val(ctnId);
            $('#sent-amount').text(`${data.currency-symbol} ${prizeAmount}`);
            $('#amount-sent').val(prizeAmount);

            $.ajax({
                url: route('seller.getWinnerInfoForCtn', ctnId),
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    $("#payment_type").val('SP');
                    $('#payment-method-1').html('Informacion de contacto:<br>');
                    $('#payment-method-2').html(
                        `Nombre y Apellido: ${data.name} ${data.lastname}<br>`);
                    $('#payment-method-3').text(`Telefono: ${data.phone}`);

                    $(`#modal-${name}`).modal('show');
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Error en la petición:', textStatus, errorThrown);
                }
            });
        });
    </script>
@endsection
