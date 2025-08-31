@extends('layouts.layout-online')
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
                                    @php $totalCtnsBuy =  $totalAllSale = $totalComSaleSeller = $totalFinalSale = $totalJackpot = $totalRoomCommn = 0; @endphp
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Pais</th>
                                        <th>#CTN</th>
                                        <th>Total</th>
                                        <th>%Vendedor</th>
                                        <th>Venta</th>
                                        <th>Bote</th>
                                        <th>Banca</th>
                                    </tr>
                                </thead>
                                @if (count($sellers))
                                    <tbody>
                                        @foreach ($sellers as $seller)
                                            @php
                                                $allSale = $allSalePreJackpot = 0;
                                                $coinSym = $seller->CtnsLot[0]->Coin->symbol;
                                                $allCtnsSeller = $seller->CtnsLot->sum('ctns');
                                                $totalCtnsBuy += $allCtnsSeller;

                                                $comSellerRm = $seller->ComSellerRoom->com;

                                                foreach ($seller->CtnsLot as $ctns) {
                                                    $allSale += $ctns->ctns * $ctnPrice * $ctns->exchange_rate;
                                                }
                                                $finalSale = $seller->CtnsLot->sum('sale_amount');
                                                $comSaleSeller = $allSale - $finalSale;

                                                $totalAllSale += $allSale;
                                                $totalComSaleSeller += $comSaleSeller;
                                                $totalFinalSale += $finalSale;

                                                $allSalePreJackpot = $allSale - $comSaleSeller;
                                                $roomCommn = round($allSalePreJackpot * $comCommerceRm, 2);

                                                $prizePool = $allSalePreJackpot - $roomCommn;
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
                                                <td>{{ $coinSym }} {{ $allSale }}</td>
                                                <td>{{ $coinSym }} {{ $comSaleSeller }}</td>
                                                <td>{{ $coinSym }} {{ $finalSale }}</td>
                                                <td>{{ $coinSym }} {{ $prizePool }}</td>
                                                <td>{{ $coinSym }} {{ $roomCommn }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="2" class="text-right"><b>Total:</b></td>
                                            <td>{{ $totalCtnsBuy }}</td>
                                            <td>{{ $coinSym }} {{ $totalAllSale }}</td>
                                            <td>{{ $coinSym }} {{ $totalComSaleSeller }}</td>
                                            <td>{{ $coinSym }} {{ $totalFinalSale }}</td>
                                            <td>{{ $coinSym }} {{ $totalJackpot }}</td>
                                            <td>{{ $coinSym }} {{ $totalRoomCommn }}</td>
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
@endsection
