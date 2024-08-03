@extends('layouts.app_rke')
<title>PlayRKE - Congelar Saldo</title>
@section('fnd-content')
    <div class="content-wrapper">
    @endsection
    @section('content')
        <div class="row">
            <div class="col-sm-9 col-md-5" style="margin: 0 auto">
                <form action="{{ route('recarga.run_freeze_bpay') }}" method="POST">@csrf
                    <div class="card bg-rke">
                        <div class="card-header">
                            <a href="{{ route('recarga.transk_BPay') }}" title="Regresar"
                                style="position:relative;top:-1px;font-size:18px;">
                                <i class="fas fa-chevron-circle-left"></i>
                            </a>
                            <span style="margin-left:5px; font-size:20px;">Congelar&nbspDepósito</span>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <label class="lb_in">Fecha Recarga</label>
                                    <input type="hidden" name="id_transk" value="{{ $recarga->id }}">
                                    <span class="form-control">{{ $recarga->fecha }}</span>
                                </div>
                                <div class="col-6">
                                    <label class="lb_in">Usuario</label>
                                    <span class="form-control">{{ $recarga->User->username }}</span>
                                </div>
                                <div class="col-6">
                                    <label class="lb_in">Monto</label>
                                    <div style="display: flex">
                                        <div class="form-control col-md-4" style="max-width: 66px;">USDT</div>
                                        <input id="monto" type="text" name="monto" class="form-control"
                                            value="{{ $recarga->monto }}">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <label class="lb_in">ID de la Orden</label>
                                    <span class="form-control">{{ $recarga->id_orden }}</span>
                                </div>
                                <div class="col-6">
                                    <label class="lb_in">Motivo/Razón</label>
                                    <select class="form-control" name="motivo" required>
                                        <option value="">Indicar Motivo:</option>
                                        <option value="3">Monto Inferior al Mín.</option>
                                        <option value="4">Monto no coincide</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mt-3 text-center">
                                <button id="save_profile" class="btn btn-primary profile-button" type="submit">Congelar
                                    Saldo</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endsection
