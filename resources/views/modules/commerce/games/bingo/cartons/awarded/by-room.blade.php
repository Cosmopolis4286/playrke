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
                            <h5>Mis Ganadores</h5>
                            <table class="table table-striped table-bordered my-2 w-100">
                                <thead>
                                    <tr>
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
                                            <td>{{ $win->Seller->name ?? 'No Aplica' }}</td>
                                            <td>
                                                @if ($win->PlayerLink)
                                                    {{ $win->PlayerLink->name }}
                                                    {{ strtoupper(substr($win->PlayerLink->lastname, 0, 1)) }}
                                                @endif
                                            </td>
                                            <td>
                                                @if ($win->PlayerLink)
                                                    {{ $win->PlayerLink->Country->name }}
                                                @endif
                                            </td>
                                            <td>{{ $win->Coin->long_name }}</td>
                                            <td>{{ $win->Coin->symbol }}&nbsp{{ $win->amount }}</td>
                                            <td>{{ $win->prize_type_description }}</td>
                                            <td>
                                                @if ($win->status === 'WT')
                                                    <span class="badge bg-dark">Pendiente</span>
                                                @elseif($win->status === 'CS')
                                                    <a href="{{ route('image.show-image-payment-prize-ctns', ['filename' => $win->Payments[0]->payment_proof]) }}"
                                                        title="Ver Comprobante" target="_blank">
                                                        <span class="badge bg-primary">Liberado</span></a>
                                                @elseif($win->status === 'SP')
                                                    <a href="{{ route('image.show-image-payment-prize-ctns', ['filename' => $win->Payments[1]->payment_proof]) }}"
                                                        title="Ver Comprobante" target="_blank">
                                                        <span class="badge bg-success">Pagado</span></a>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group" aria-label="Acciones">
                                                    @if ($win->status == null)
                                                        <button type="button"
                                                            class="btn btn-danger btn-sm notify-ctn-payment"
                                                            title="Notificar Pago" data-coin_sym="{{ $win->Coin->symbol }}"
                                                            data-amount="{{ $win->amount }}"
                                                            data-ctn='{{ encrypt($win->ctn) }}'>
                                                            <i class="fa-solid fa-bell"></i>
                                                        </button>
                                                    @endif
                                                    @if ($win->PlayerLink)
                                                        <a class="btn btn-sm bg-primary show-ctns" target="_blank"
                                                            href="{{ route('bingo.in-game', ['player' => $win->PlayerLink->link]) }}">
                                                            <i class="fa-solid fa-eye"></i>
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
