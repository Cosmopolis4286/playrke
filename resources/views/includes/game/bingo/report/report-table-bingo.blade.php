<!-- resources/views/includes/report-table.blade.php -->
<div class="table-responsive mt-2">
    <table class="table table-striped table-bordered my-2 w-100">
        <thead>
            <tr>
                <th>Sala</th>
                <th>Fecha</th>
                <th>Hora</th>
                <th>Moneda</th>
                <th>Ctns</th>
                <th>Total</th>
                <th>%Vendedor</th>
                <th>Venta</th>
                <th>%Bote</th>
                <th>%Banca</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($roomData as $room)
                <tr>
                    <td class="align-middle">
                        <a class="badge bg-navy" title="Ver Reporte"
                            href="{{ route('commerce.reportSalesByRoom', ['room' => $room['encrypted'], 'coin' => encrypt($wallet->Coin->id)]) }}">
                            {{ $room['encrypted'] }}
                        </a>
                    </td>
                    <td>{{ \Carbon\Carbon::parse($room['date'])->format('d/m/y') }}</td>
                    <td>{{ $room['time'] }}h</td>
                    <td>{{ $coinName }}</td>
                    <td>{{ $room['allCtns'] }}</td>
                    <td>{{ $coinSym }} {{ $room['allSaleRoom'] }}</td>
                    <td>{{ $coinSym }} {{ $room['comSaleSellerRoom'] }}</td>
                    <td>{{ $coinSym }} {{ $room['finalSaleRoom'] }}</td>
                    <td>{{ $coinSym }} {{ $room['jackpotRoom'] }}</td>
                    <td>{{ $coinSym }} {{ $room['comBancaRoom'] }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-right"><b>Total:</b></td>
                <td>{{ $totals['allCtns'] }}</td>
                <td>{{ $coinSym }} {{ $totals['allSales'] }}</td>
                <td>{{ $coinSym }} {{ $totals['comSaleSeller'] }}</td>
                <td>{{ $coinSym }} {{ $totals['finalSale'] }}</td>
                <td>{{ $coinSym }} {{ $totals['jackpot'] }}</td>
                <td>{{ $coinSym }} {{ $totals['comBanca'] }}</td>
            </tr>
        </tfoot>
    </table>
</div>
