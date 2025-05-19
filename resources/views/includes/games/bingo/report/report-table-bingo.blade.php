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
            @foreach ($roomsData as $room)
                <tr>
                    <td class="align-middle">
                        <a class="badge bg-navy" title="Ver Reporte"
                            href="{{ route('bingo.reportSalesByRoom', ['room' => $room['encrypted'], 'coin' => encrypt($coin)]) }}">
                            {{ $room['encrypted'] }}
                        </a>
                    </td>
                    <td>{{ $room['roomType'] }}</td>
                    <td>{{ $room['date'] }}</td>
                    <td>{{ $room['time'] }}h</td>
                    <td>{{ $coinData->short_name }}</td>
                    <td>{{ $room['ctns'] }}</td>
                    <td>{{ $coinData->iso_code }} {{ $room['sales'] }}</td>
                    <td>{{ $coinData->iso_code }} {{ $room['sellerCommn'] }}</td>
                    <td>{{ $coinData->iso_code }} {{ $room['roomCommn'] }}</td>
                    <td>{{ $coinData->iso_code }} {{ $room['prizePool'] }}</td>
                </tr>
            @endforeach
        </tbody>
        @if (count($roomsData))
            <tfoot>
                <tr>
                    <td colspan="5" class="text-right"><b>Total:</b></td>
                    <td>{{ $totalsFormatted['ctns'] }}</td>
                    <td>{{ $coinData->iso_code }} {{ $totalsFormatted['sales'] }}</td>
                    <td>{{ $coinData->iso_code }} {{ $totalsFormatted['sellerCommn'] }}</td>
                    <td>{{ $coinData->iso_code }} {{ $totalsFormatted['roomCommn'] }}</td>
                    <td>{{ $coinData->iso_code }} {{ $totalsFormatted['prizePool'] }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>
