@extends('layouts.app_rke')
@section('title', $title)
@section('content')
    <div class="col-12">
        <div class="card">
            @include('includes.card-header')
            <div class="card-body">
                @include('includes.coin-tabs')
                @include('includes.date-filter-buttons', ['routeWithCoin' => 'seller.ctnsAwarded'])
                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade show active">
                        <div class="table-responsive mt-3">
                            <table class="table table-striped table-bordered my-2 w-100">
                                <thead>
                                    <tr>
                                        <th>Sala</th>
                                        <th>Horario</th>
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
                                                    href="{{ route('seller.reportSalesByRoom', [
                                                        'room' => $win->Room->encrypted,
                                                        'coin' => encrypt($win->Coin->id),
                                                    ]) }}">
                                                    {{ $win->Room->encrypted }}
                                                </a>
                                            </td>
                                            <td class="align-middle">
                                                {{ \Carbon\Carbon::parse($win->Room->date)->format('d/m/y') }}<br>
                                                {{ $win->Room->time }} <small>GMT-4</small>
                                            </td>
                                            <td>{{ $win->PlayerLink->name }}
                                                {{ $win->PlayerLink->lastname }}</td>
                                            <td>{{ $win->PlayerLink->Country->name }}</td>
                                            <td>{{ $coinISOCode }} ({{ $coinSym }})</td>
                                            <td>{{ $coinSym }} {{ $win->amount }}</td>
                                            <td>{{ $win->Prize->description }}</td>
                                            <td class="align-middle">
                                                @if ($win->status == 0)
                                                    <span class="badge bg-secondary">Pendiente</span>
                                                @elseif($win->status == 1)
                                                    <a href="{{ route('image.show-image-payment-prize-ctns', ['filename' => $win->Payment[0]->payment_proof]) }}"
                                                        title="Ver Comprobante" target="_blank">
                                                        <span class="badge bg-primary">Por Pagar</span></a>
                                                @else
                                                    <a href="{{ route('image.show-image-payment-prize-ctns', ['filename' => $win->Payment[1]->payment_proof]) }}"
                                                        title="Ver Comprobante" target="_blank">
                                                        <span class="badge bg-success">Pagado</span></a>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group" aria-label="Acciones">
                                                    @if ($win->status == 1)
                                                        <button class="btn btn-sm bg-danger notify-ctn-payment"
                                                            title="Notificar Pago" data-coin_sym="{{ $win->coinSym }}"
                                                            data-prize_amount="{{ $win->amount }}"
                                                            data-ctn='{{ encrypt($win->ctn) }}'>
                                                            <i class="fa-solid fa-circle-dollar-to-slot mr-1"></i>
                                                            Notificar
                                                        </button>
                                                    @endif
                                                    <a class="btn btn-sm bg-primary show-ctns" target="_blank"
                                                        href="{{ route('bingocash.in-game', ['player' => $win->PlayerLink->link]) }}">
                                                        <i class="mr-1 fa-solid fa-eye"></i>CTNs
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="5" class="text-right"><b>Total:</b></td>
                                        <td>{{ $coinSym }} {{ $winners->sum('amount') }}</td>
                                        <td colspan="3"></td>
                                    </tr>
                                </tfoot>
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
            <div class="modal-content bg-rke1">
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
        'name' => $name,
        'title' => 'Notificar Pago',
        'formId' => 'ctn-payment',
        'formRoute' => route('bingo.notifyPaymentPrizeWinningCtn'),
    ])
@endsection
@section('scripts')
    @vite('resources/js/rke/payment-confirm-button.js')
    <script>
        const name = '{{ $name }}';

        $(document).on("click", ".notify-ctn-payment", function() {
            const data = $(this).data();
            const ctnId = data.ctn;
            const prizeAmount = data.prize_amount;

            $('#text-bank-data').addClass('hide-elem');
            $('#send-receive').text('Enviaste ');
            $('#ctn').val(ctnId);
            $('#sent-amount').text(`${data.coin_sym} ${prizeAmount}`);
            $('#amount-sent').val(prizeAmount);

            $.ajax({
                url: `{{ route('seller.getWinnerInfoForCtn', ['ctnId' => ':ctnId']) }}`.replace(
                    ':ctnId', ctnId),
                method: 'GET',
                dataType: 'json',
                success: function(data) {
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
