{{-- resources/views/components/my-winners-report-table.blade.php  --}}
<div class="table-responsive mt-3">
    <table class="table table-striped table-bordered my-2 w-100">
        <thead>
            <tr>
                <th>Sala</th>
                <th>Horario</th>
                <th>Vendedor</th>
                <th>Jugador</th>
                <th>País</th>
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
                            href="{{ route('commerce.reportSalesByRoom', ['room' => $win->Room->encrypted, 'coin' => encrypt($wallet->Coin->id)]) }}">
                            {{ $win->Room->encrypted }}
                        </a>
                    </td>
                    <td class="align-middle">
                        {{ \Carbon\Carbon::parse($win->Room->date)->format('d/m/y') }}<br>
                        {{ $win->Room->time }} <small>GMT-4</small>
                    </td>
                    <td>{{ $win->Seller->name }}</td>
                    <td>{{ $win->PlayerLink->name }} {{ strtoupper(substr($win->PlayerLink->lastname, 0, 1)) }}</td>
                    <td>{{ $win->PlayerLink->Country->name }}</td>
                    <td>{{ $win->Coin->long_name }}</td>
                    <td>{{ $win->Coin->symbol }}&nbsp{{ $win->amount }}</td>
                    <td>{{ $win->Prize->description }}</td>
                    <td>
                        @if ($win->status == 0)
                            <span class="badge bg-dark">Pendiente</span>
                        @elseif($win->status == 1)
                            <a href="{{ route('image.show-image-payment-prize-ctns', ['filename' => $win->Payment[0]->payment_proof]) }}"
                                title="Ver Comprobante" target="_blank">
                                <span class="badge bg-primary">Liberado</span></a>
                        @elseif($win->status == 2)
                            <a href="{{ route('image.show-image-payment-prize-ctns', ['filename' => $win->Payment[1]->payment_proof]) }}"
                                title="Ver Comprobante" target="_blank">
                                <span class="badge bg-success">Pagado</span></a>
                        @endif
                    </td>
                    <td>
                        <div class="btn-group" role="group" aria-label="Acciones">
                            @if ($win->status == 0)
                                <button type="button" class="btn btn-danger btn-sm notify-ctn-payment"
                                    title="Notificar Pago" data-coin_sym="{{ $win->Coin->symbol }}"
                                    data-amount="{{ $win->amount }}" data-ctn='{{ encrypt($win->ctn) }}'>
                                    Notificar
                                </button>
                            @endif
                            <button class="show-ctns btn btn-sm bg-secondary"
                                data-player_link="{{ $win->PlayerLink->link }}">
                                <i class="mr-1 fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
