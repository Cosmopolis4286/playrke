@extends('layouts.app_rke')
@section('title', $title)
@section('content')
    <div class="col-12">
        <div class="card">
            @include('includes.card-header')
            <div class="card-body">
                @include('includes.coin-tabs')
                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade show active">
                        <div class="table-responsive mt-2">
                            <h5>Mis Vendedores</h5>
                            <table class="table table-striped table-bordered my-2 w-100">
                                <thead>
                                    @php
                                        $totalCtnsBuy = $totalAllSale = $totalComSaleSeller = $totalJackpot = $totalRoomCommn = 0;
                                    @endphp
                                    <tr>
                                        <th>Nombre</th>
                                        <th>País</th>
                                        <th>#CTN</th>
                                        <th>Venta</th>
                                        <th>%Com</th>
                                        <th>Banca</th>
                                        <th>Bote</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($sellers as $seller)
                                        @php
                                            // Cálculos de totales por vendedor
                                            $allCtnsSeller = $seller->CtnsLot->sum('ctns');
                                            $saleAmountSeller = $seller->CtnsLot->sum('sale_amount');
                                            $finalSaleAmountSeller = $seller->CtnsLot->sum('final_sale_amount');
                                            $comSaleSeller = $saleAmountSeller - $finalSaleAmountSeller;

                                            $prizePool = $saleAmountSeller - $comSaleSeller;
                                            $roomCommn = round($saleAmountSeller * $roomCommn, 2);
                                            $prizePool -= $roomCommn;

                                            // Acumular totales
                                            $totalCtnsBuy += $allCtnsSeller;
                                            $totalAllSale += $saleAmountSeller;
                                            $totalComSaleSeller += $comSaleSeller;
                                            $totalJackpot += $prizePool;
                                            $totalRoomCommn += $roomCommn;
                                        @endphp
                                        <tr>
                                            <td class="align-middle">
                                                <a class="badge bg-navy" title="Ver Vendedor" href="#">
                                                    {{ $seller->name }}
                                                </a>
                                            </td>
                                            <td>{{ $seller->Country->name }}</td>
                                            <td>{{ $allCtnsSeller }}</td>
                                            <td>{{ $coinData->iso_code }} {{ number_format($saleAmountSeller, 2) }}</td>
                                            <td>{{ $coinData->iso_code }} {{ number_format($comSaleSeller, 2) }}</td>
                                            <td>{{ $coinData->iso_code }} {{ number_format($roomCommn, 2) }}</td>
                                            <td>{{ $coinData->iso_code }} {{ number_format($prizePool, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="2" class="text-right"><b>Total:</b></td>
                                        <td>{{ $totalCtnsBuy }}</td>
                                        <td>{{ $coinData->iso_code }} {{ number_format($totalAllSale, 2) }}</td>
                                        <td>{{ $coinData->iso_code }} {{ number_format($totalComSaleSeller, 2) }}</td>
                                        <td>{{ $coinData->iso_code }} {{ number_format($totalRoomCommn, 2) }}</td>
                                        <td>{{ $coinData->iso_code }} {{ number_format($totalJackpot, 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
