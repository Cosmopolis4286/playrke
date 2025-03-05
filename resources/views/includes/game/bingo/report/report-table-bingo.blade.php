<!-- resources/views/includes/report-table.blade.php -->
<div class="table-responsive mt-2">
    <table class="table table-striped table-bordered my-2 w-100">
        <thead>
            <tr>
                <th>Sala</th>
                <th>Tipo</th>
                <th>Fecha</th>
                <th>Hora</th>
                <th>Moneda</th>
                <th>Ctns</th>
                <th>Venta</th>
                <th>Vendedor</th>
                <th>Banca</th>
                <th>Bote</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($roomData as $room)
                <tr>
                    <td class="align-middle">
                        <a class="badge bg-navy" title="Ver Reporte"
                            href="{{ route('commerce.reportSalesByRoom', ['room' => $room['encrypted'], 'coin' => encrypt($coin)]) }}">
                            {{ $room['encrypted'] }}
                        </a>
                    </td>
                    <td>{{ $room['roomType'] }}</td>
                    <td>{{ \Carbon\Carbon::parse($room['date'])->format('d/m/y') }}</td>
                    <td>{{ $room['time'] }}h</td>
                    <td>{{ $coinName }}</td>
                    <td>{{ $room['allCtns'] }}</td>
                    <td>{{ $coinSym }} {{ $room['allSaleRoom'] }}</td>
                    <td>{{ $coinSym }} {{ $room['comSaleSellerRoom'] }}</td>
                    <td>{{ $coinSym }} {{ $room['comBancaRoom'] }}</td>
                    <td>{{ $coinSym }} {{ $room['jackpotRoom'] }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-right"><b>Total:</b></td>
                @if (count($roomData))
                    <td>{{ $totals['allCtns'] }}</td>
                    <td>{{ $coinSym }} {{ number_format($totals['allSales'], 2, '.') }}</td>
                    <td>{{ $coinSym }} {{ number_format($totals['comSaleSeller'], 2, '.') }}</td>
                    <td>{{ $coinSym }} {{ number_format($totals['comBanca'], 2, '.') }}</td>
                    <td>{{ $coinSym }} {{ number_format($totals['jackpot'], 2, '.') }}</td>
                @endif
            </tr>
        </tfoot>
    </table>
</div>
