@extends('layouts.layout-online')
<title>PlayRKE - Límites</title>
@section('fnd-content')
    <div class="content-wrapper">
    @endsection
    @section('content')
        <div class="row">
            <div class="col-md-3">
                <div class="card bg-rke" align=center>
                    <div class="card-header" title="Depósitos">
                        <h5 class="my-n2">DEPÓSITOS</h5>
                    </div>
                    <form action="{{ route('limits.store_dp') }}" method="POST">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <div class="card-body" style="margin-top: -15px;">
                            <div class="row">
                                <div class="col-9 col-md-6">
                                    <label class="lb_in">Mín Bs/$</label>
                                    <input id="in" type="number" name="min_dp_bs" class="form-control"
                                        step="0.25" value="{{ $limits[1]->valor }}" max="20" min="10">
                                </div>
                                <div class="col-9 col-md-6">
                                    <label class="lb_in">Mín. USDT</label>
                                    <input id="in" type="number" name="min_dp_usdt" class="form-control"
                                        step="0.25" value="{{ $limits[3]->valor }}" max="3" min="2">
                                </div>
                                <div class="col-3 col-md-12"><br>
                                    <button id="save_dp" class="form-control btn btn-primary" type="submit">
                                        <i class="fa-solid fa-floppy-disk"></i></button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card bg-rke" align=center>
                    <div class="card-header" title="Retiros">
                        <h5 class="my-n2">RETIROS</h5>
                    </div>
                    <form action="{{ route('limits.store_rt') }}" method="POST">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <div class="card-body" style="margin-top: -15px;">
                            <div class="row">
                                <div class="col-4 col-md-4">
                                    <label class="lb_in">Mín.</label>
                                    <input id="in" type="number" name="min_rt" class="form-control" step="1"
                                        value="{{ $limits[5]->valor }}" max="5" min="3">
                                </div>
                                <div class="col-4 col-md-4">
                                    <label class="lb_in">Máx.</label>
                                    <input id="in" type="number" name="max_rt" class="form-control"
                                        value="{{ $limits[6]->valor }}" max="500" min="500">
                                </div>
                                <div class="col-5 col-md-4">
                                    <label class="lb_in">%Com.</label>
                                    <input id="in" type="number" name="com_rt" class="form-control" step="0.05"
                                        value="{{ $limits[4]->valor }}" max="2" min="1.5">
                                </div>
                                <div class="col-3 col-md-12"><br>
                                    <button id="save_rt" class="form-control btn btn-primary" type="submit">
                                        <i class="fa-solid fa-floppy-disk"></i></button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-md-5">
                <div class="card bg-rke" align=center>
                    <div class="card-header" title="Transferencias">
                        <h5 class="my-n2">TRANSFERENCIAS</h5>
                    </div>
                    <form action="{{ route('limits.store_transfer') }}" method="POST">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <div class="card-body" style="margin-top: -15px;">
                            <div class="row">
                                <div class="col-3 col-md-4">
                                    <label class="lb_in">Mín.</label>
                                    <input id="in" type="number" name="min_transfer" class="form-control"
                                        value="{{ $limits[9]->valor }}" max="3" min="1.25">
                                </div>
                                <div class="col-3 col-md-4 col-lg-4">
                                    <label class="lb_in">%Com.</label>
                                    <input id="in" type="number" name="com_transfer" class="form-control"
                                        step="0.05" value="{{ $limits[8]->valor }}" max="2" min="1.5">
                                </div>
                                <div class="col-3 col-md-4 col-lg-4">
                                    <label class="lb_in">No Transfer</label>
                                    <input id="in" type="number" name="no_transfer" class="form-control"
                                        value="{{ $limits[10]->valor }}" max="5" min="2.5">
                                </div>
                                <div class="col-3 col-md-12"><br>
                                    <button id="save_transfer" class="form-control btn btn-primary" type="submit">
                                        <i class="fa-solid fa-floppy-disk"></i></button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endsection
